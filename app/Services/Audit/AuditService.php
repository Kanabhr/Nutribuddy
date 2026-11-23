<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Fields that must never appear in audit values regardless of model.
     * This covers passwords, OTPs, payment secrets, 2FA secrets/codes, and
     * any gateway credential stored in the settings table.
     */
    private const MASKED_KEYS = [
        'password',
        'otp',
        'otp_expires_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'gateway_signature',
        'gateway_payload',
        // Setting keys that hold secrets (matched by substring on the key column value)
        // — handled separately in maskSettingRow().
    ];

    /**
     * Setting key substrings whose value must be masked when auditing the settings table.
     */
    private const MASKED_SETTING_KEYS = [
        'secret',
        'key',
        'token',
        'password',
        'api_id',
        'app_id',
        'webhook',
        'whatsapp',
        'aisensy',
        'twilio',
    ];

    /**
     * Record a create/update/delete event for a model.
     *
     * @param  array<string,mixed>  $old
     * @param  array<string,mixed>  $new
     */
    public function record(
        Model $model,
        string $action,
        array $old = [],
        array $new = []
    ): void {
        [$actorId, $actorType] = $this->resolveActor();

        $isSettings = $model instanceof Setting;

        AuditLog::create([
            'actor_id' => $actorId,
            'actor_type' => $actorType,
            'action' => $action,
            'auditable_type' => class_basename($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $this->mask($old, $isSettings ? $model : null),
            'new_values' => $this->mask($new, $isSettings ? $model : null),
            'ip_address' => Request::ip(),
        ]);
    }

    /** Determine the current actor from the active guards. */
    private function resolveActor(): array
    {
        if ($admin = auth('admin')->user()) {
            return [$admin->id, 'admin'];
        }
        if ($user = auth('web')->user()) {
            return [$user->id, 'customer'];
        }

        return [null, 'system'];
    }

    /**
     * Remove or redact secret fields from a value map.
     * When $settingModel is passed, also redact by the setting key name.
     */
    private function mask(array $values, ?Setting $settingModel = null): array
    {
        $filtered = array_diff_key($values, array_flip(self::MASKED_KEYS));

        if ($settingModel !== null && isset($filtered['value'])) {
            $keyName = strtolower((string) ($settingModel->key ?? ''));
            foreach (self::MASKED_SETTING_KEYS as $needle) {
                if (str_contains($keyName, $needle)) {
                    $filtered['value'] = '[redacted]';
                    break;
                }
            }
        }

        return $filtered;
    }
}
