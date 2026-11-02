<?php

namespace Tests\Unit\Nutrition;

use App\Services\Nutrition\ChildProfileService;
use PHPUnit\Framework\TestCase;

class ChildProfileServiceTest extends TestCase
{
    public function test_it_normalizes_questionnaire_height_formats_to_centimeters(): void
    {
        $profiles = new ChildProfileService();

        $this->assertSame(173.0, $profiles->heightInCentimeters('5ft 8in (173cm)'));
        $this->assertSame(173.0, $profiles->heightInCentimeters('173 cm'));
        $this->assertSame(172.72, $profiles->heightInCentimeters('5ft 8in'));
    }
}