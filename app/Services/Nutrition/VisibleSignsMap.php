<?php

namespace App\Services\Nutrition;

/**
 * VisibleSignsMap
 *
 * Maps the new (C7) Q26 option labels that are shown to parents onto the EXACT
 * deficiency_rules.sign strings seeded by DeficiencyRulesSeeder.
 *
 * Rules:
 *  - DO NOT change deficiency_rules or DeficiencyRulesSeeder.
 *  - Old labels (pre-C7) must still resolve so already-saved answers work.
 *  - "None of the above", "None", and "Not sure" produce no flag.
 *
 * Max file upload size for Q31 blood reports.
 * PLACEHOLDER — unverified, owner to confirm.
 */
class VisibleSignsMap
{
    /** Max allowed file size for blood-report uploads (Q31), in bytes. */
    // PLACEHOLDER — unverified, owner to confirm.
    public const BLOOD_REPORT_MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    /**
     * Maps every accepted label (new + legacy) to its canonical
     * deficiency_rules.sign value.
     *
     * Keys: what may appear as Q26 answer_value (new labels from C7 seeder,
     *       and old labels from pre-C7 seeder).
     * Values: the EXACT deficiency_rules.sign string – never changed.
     *
     * @var array<string, string>
     */
    private const LABEL_TO_SIGN = [
        // ── New labels (C7) ───────────────────────────────────────────────
        'Pale skin or eyes'          => 'Pale eyes/skin',
        'Tiredness or low energy'    => 'Tiredness',
        'Hair fall'                  => 'Hair fall',
        'Brittle nails'              => 'Brittle nails',
        'Mouth ulcers or cracks'     => 'Mouth-corner cracks/ulcers',
        'Poor night vision'          => 'Night-vision trouble',
        'Delayed teeth or bowed legs' => 'Delayed teeth/bowed legs',
        'Poor concentration'         => 'Poor focus',

        // ── Legacy labels (pre-C7) — kept so saved answers still work ─────
        'Pale eyes/skin'             => 'Pale eyes/skin',
        'Tiredness'                  => 'Tiredness',
        'Mouth-corner cracks/ulcers' => 'Mouth-corner cracks/ulcers',
        'Night-vision trouble'       => 'Night-vision trouble',
        'Delayed teeth/bowed legs'   => 'Delayed teeth/bowed legs',
        'Poor focus'                 => 'Poor focus',
    ];

    /**
     * Labels that carry no clinical meaning — resolved to null and
     * must NOT create a ClinicalFlag or trigger professional_review_required.
     *
     * @var list<string>
     */
    private const NO_FLAG_LABELS = [
        'None of the above',
        'Not sure',
        'None',
    ];

    /**
     * Translate an array of raw Q26 answer labels (new or legacy) into the
     * exact deficiency_rules.sign strings the engine expects.
     *
     * - Drops no-flag labels.
     * - Drops empty / blank items.
     * - Maps via LABEL_TO_SIGN; unrecognised labels fall through unchanged
     *   (backward-compat: a future label not yet in the map still reaches
     *    DeficiencyRule::whereIn and misses harmlessly rather than crashing).
     *
     * @param  list<string>  $labels
     * @return list<string>  de-duplicated sign strings ready for whereIn
     */
    public static function toSigns(array $labels): array
    {
        $noFlag = array_map('strtolower', self::NO_FLAG_LABELS);

        return collect($labels)
            ->map(fn (string $label) => trim($label))
            ->filter(fn (string $label) => $label !== '')
            ->reject(fn (string $label) => in_array(strtolower($label), $noFlag, true))
            ->map(fn (string $label) => self::LABEL_TO_SIGN[$label] ?? $label)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Whether a single label is a no-flag sentinel (None / Not sure / None of the above).
     */
    public static function isNoFlag(string $label): bool
    {
        $noFlag = array_map('strtolower', self::NO_FLAG_LABELS);

        return in_array(strtolower(trim($label)), $noFlag, true);
    }
}
