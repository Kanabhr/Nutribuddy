<?php

namespace Tests\Unit\Nutrition;

use App\Services\Nutrition\ChildProfileService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ChildProfileService::allergies() (accessed via the public
 * normalize() path is complex, so we test the private method through reflection).
 *
 * The goal is to confirm that soy / sesame / honey text is tokenised correctly
 * alongside the pre-existing tokens (lactose, dairy, nuts, gluten, egg).
 */
class AllergyMappingTest extends TestCase
{
    private ChildProfileService $service;

    /** @var \ReflectionMethod */
    private \ReflectionMethod $allergiesMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChildProfileService;

        $reflection = new \ReflectionClass(ChildProfileService::class);
        $this->allergiesMethod = $reflection->getMethod('allergies');
        $this->allergiesMethod->setAccessible(true);
    }

    private function allergies(mixed $value): array
    {
        return $this->allergiesMethod->invoke($this->service, $value);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // New tokens: soy / sesame / honey
    // ──────────────────────────────────────────────────────────────────────────

    public function test_soy_text_maps_to_soy_token(): void
    {
        $this->assertSame(['soy'], $this->allergies('soy'));
        $this->assertSame(['soy'], $this->allergies('soy allergy'));
        $this->assertSame(['soy'], $this->allergies('Soya'));     // contains 'soy'
    }

    public function test_sesame_text_maps_to_sesame_token(): void
    {
        $this->assertSame(['sesame'], $this->allergies('sesame'));
        $this->assertSame(['sesame'], $this->allergies('Sesame seeds'));
    }

    public function test_honey_text_maps_to_honey_token(): void
    {
        $this->assertSame(['honey'], $this->allergies('honey'));
        $this->assertSame(['honey'], $this->allergies('Honey allergy'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Pre-existing tokens are unchanged
    // ──────────────────────────────────────────────────────────────────────────

    /** "Nuts & seeds" (the exact questionnaire option text) must still map to 'nuts'. */
    public function test_nuts_and_seeds_option_maps_to_nuts(): void
    {
        $this->assertSame(['nuts'], $this->allergies('Nuts & seeds'));
        $this->assertSame(['nuts'], $this->allergies('tree nuts'));
        $this->assertSame(['nuts'], $this->allergies('pumpkin seed'));
    }

    public function test_existing_mappings_still_work(): void
    {
        $this->assertSame(['lactose'], $this->allergies('lactose intolerance'));
        $this->assertSame(['dairy'],   $this->allergies('dairy'));
        $this->assertSame(['nuts'],    $this->allergies('tree nut'));
        $this->assertSame(['nuts'],    $this->allergies('pumpkin seed'));
        $this->assertSame(['gluten'],  $this->allergies('gluten'));
        $this->assertSame(['gluten'],  $this->allergies('wheat'));
        $this->assertSame(['egg'],     $this->allergies('egg'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Multiple allergies in a comma/pipe separated string
    // ──────────────────────────────────────────────────────────────────────────

    public function test_multiple_allergies_are_all_tokenised(): void
    {
        $result = $this->allergies('dairy, soy, sesame, honey');
        sort($result);
        $this->assertSame(['dairy', 'honey', 'sesame', 'soy'], $result);
    }

    public function test_pipe_separated_allergies(): void
    {
        $result = $this->allergies('gluten|soy|egg');
        sort($result);
        $this->assertSame(['egg', 'gluten', 'soy'], $result);
    }

    /** An array input (e.g. from a multi-select) is handled the same way. */
    public function test_array_input_is_tokenised(): void
    {
        $result = $this->allergies(['Dairy/Lactose', 'Soy', 'Honey']);
        sort($result);
        $this->assertSame(['honey', 'lactose', 'soy'], $result);
    }

    /** Unknown values produce no tokens (they are silently dropped). */
    public function test_unknown_allergy_text_is_ignored(): void
    {
        $this->assertSame([], $this->allergies('none'));
        $this->assertSame([], $this->allergies(''));
        $this->assertSame([], $this->allergies('shellfish'));
    }

    /** Duplicates are collapsed to a single token. */
    public function test_duplicates_are_deduplicated(): void
    {
        $result = $this->allergies('soy, soy allergy, soya');
        $this->assertSame(['soy'], $result);
    }
}
