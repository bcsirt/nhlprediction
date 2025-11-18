<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ValueBets\Enums;

use App\Domains\ValueBets\Enums\OddsFormat;
use PHPUnit\Framework\TestCase;

class OddsFormatTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = OddsFormat::cases();

        $this->assertCount(4, $cases);
        $this->assertEquals('decimal', OddsFormat::DECIMAL->value);
        $this->assertEquals('american', OddsFormat::AMERICAN->value);
        $this->assertEquals('fractional', OddsFormat::FRACTIONAL->value);
        $this->assertEquals('implied', OddsFormat::IMPLIED->value);
    }

    public function test_label_returns_labels(): void
    {
        $this->assertEquals('Décimales', OddsFormat::DECIMAL->label());
        $this->assertEquals('Américaines', OddsFormat::AMERICAN->label());
        $this->assertEquals('Fractionnaires', OddsFormat::FRACTIONAL->label());
        $this->assertEquals('Probabilité implicite', OddsFormat::IMPLIED->label());
    }

    public function test_example_returns_non_empty_string(): void
    {
        foreach (OddsFormat::cases() as $format) {
            $this->assertNotEmpty($format->example());
        }
    }

    public function test_from_decimal_to_decimal(): void
    {
        $result = OddsFormat::DECIMAL->fromDecimal(2.50);
        $this->assertEquals('2.50', $result);
    }

    public function test_from_decimal_to_american_positive(): void
    {
        // 2.50 décimal = +150 américain
        $result = OddsFormat::AMERICAN->fromDecimal(2.50);
        $this->assertEquals('+150', $result);
    }

    public function test_from_decimal_to_american_negative(): void
    {
        // 1.50 décimal = -200 américain
        $result = OddsFormat::AMERICAN->fromDecimal(1.50);
        $this->assertEquals('-200', $result);
    }

    public function test_from_decimal_to_fractional(): void
    {
        // 2.50 décimal = 3/2 fractionnaire
        $result = OddsFormat::FRACTIONAL->fromDecimal(2.50);
        $this->assertEquals('3/2', $result);
    }

    public function test_from_decimal_to_implied(): void
    {
        // 2.00 décimal = 50% probabilité implicite
        $result = OddsFormat::IMPLIED->fromDecimal(2.00);
        $this->assertEquals('50%', $result);
    }

    public function test_to_decimal_from_decimal(): void
    {
        $result = OddsFormat::toDecimal('2.50', OddsFormat::DECIMAL);
        $this->assertEquals(2.50, $result);
    }

    public function test_to_decimal_from_american_positive(): void
    {
        $result = OddsFormat::toDecimal('150', OddsFormat::AMERICAN);
        $this->assertEquals(2.50, $result);
    }

    public function test_to_decimal_from_american_negative(): void
    {
        $result = OddsFormat::toDecimal('-200', OddsFormat::AMERICAN);
        $this->assertEquals(1.50, $result);
    }

    public function test_to_decimal_from_fractional(): void
    {
        $result = OddsFormat::toDecimal('3/2', OddsFormat::FRACTIONAL);
        $this->assertEquals(2.50, $result);
    }

    public function test_to_decimal_from_implied(): void
    {
        $result = OddsFormat::toDecimal('50%', OddsFormat::IMPLIED);
        $this->assertEquals(2.0, $result);
    }

    public function test_conversion_round_trip(): void
    {
        $original = 2.50;

        // Decimal -> American -> Decimal
        $american = OddsFormat::AMERICAN->fromDecimal($original);
        $backToDecimal = OddsFormat::toDecimal(str_replace('+', '', $american), OddsFormat::AMERICAN);
        $this->assertEquals($original, $backToDecimal);

        // Decimal -> Implied -> Decimal
        $implied = OddsFormat::IMPLIED->fromDecimal($original);
        $backToDecimal = OddsFormat::toDecimal($implied, OddsFormat::IMPLIED);
        $this->assertEquals($original, $backToDecimal);
    }
}
