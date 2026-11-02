<?php

namespace App\Services\Access;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Coordinates address changes that must stay consistent for one customer. */
class CustomerAddressService
{
    public function setDefault(int $userId, int $addressId): void
    {
        DB::transaction(function () use ($userId, $addressId): void {
            User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $address = CustomerAddress::query()
                ->where('user_id', $userId)
                ->whereKey($addressId)
                ->lockForUpdate()
                ->firstOrFail();

            CustomerAddress::query()
                ->where('user_id', $userId)
                ->update(['is_default_shipping' => false, 'is_default_billing' => false]);

            $address->update(['is_default_shipping' => true, 'is_default_billing' => true]);
        });
    }
}
