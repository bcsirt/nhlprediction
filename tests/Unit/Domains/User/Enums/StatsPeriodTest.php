<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Enums;

use App\Domains\User\Enums\StatsPeriod;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class StatsPeriodTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = StatsPeriod::cases();

        $this->assertCount(5, $cases);
        $this->assertEquals('daily', StatsPeriod::DAILY->value);
        $this->assertEquals('weekly', StatsPeriod::WEEKLY->value);
        $this->assertEquals('monthly', StatsPeriod::MONTHLY->value);
        $this->assertEquals('yearly', StatsPeriod::YEARLY->value);
        $this->assertEquals('all_time', StatsPeriod::ALL_TIME->value);
    }

    public function test_label_returns_french_labels(): void
    {
        $this->assertEquals('Quotidien', StatsPeriod::DAILY->label());
        $this->assertEquals('Hebdomadaire', StatsPeriod::WEEKLY->label());
        $this->assertEquals('Mensuel', StatsPeriod::MONTHLY->label());
        $this->assertEquals('Annuel', StatsPeriod::YEARLY->label());
        $this->assertEquals('Tout le temps', StatsPeriod::ALL_TIME->label());
    }

    public function test_get_current_period_returns_array_with_start_and_end(): void
    {
        foreach (StatsPeriod::cases() as $period) {
            $dates = $period->getCurrentPeriod();

            $this->assertArrayHasKey('start', $dates);
            $this->assertArrayHasKey('end', $dates);
            $this->assertInstanceOf(Carbon::class, $dates['start']);
            $this->assertInstanceOf(Carbon::class, $dates['end']);
            $this->assertLessThanOrEqual($dates['end'], $dates['start']);
        }
    }

    public function test_get_current_period_daily(): void
    {
        $dates = StatsPeriod::DAILY->getCurrentPeriod();

        $this->assertEquals(Carbon::now()->startOfDay()->toDateString(), $dates['start']->toDateString());
        $this->assertEquals(Carbon::now()->endOfDay()->toDateString(), $dates['end']->toDateString());
    }

    public function test_get_current_period_weekly(): void
    {
        $dates = StatsPeriod::WEEKLY->getCurrentPeriod();

        $this->assertEquals(Carbon::now()->startOfWeek()->toDateString(), $dates['start']->toDateString());
        $this->assertEquals(Carbon::now()->endOfWeek()->toDateString(), $dates['end']->toDateString());
    }

    public function test_get_current_period_monthly(): void
    {
        $dates = StatsPeriod::MONTHLY->getCurrentPeriod();

        $this->assertEquals(Carbon::now()->startOfMonth()->toDateString(), $dates['start']->toDateString());
        $this->assertEquals(Carbon::now()->endOfMonth()->toDateString(), $dates['end']->toDateString());
    }

    public function test_get_previous_period_returns_earlier_dates(): void
    {
        $current = StatsPeriod::WEEKLY->getCurrentPeriod();
        $previous = StatsPeriod::WEEKLY->getPreviousPeriod();

        $this->assertLessThan($current['start'], $previous['start']);
        $this->assertLessThan($current['end'], $previous['end']);
    }

    public function test_days_in_period(): void
    {
        $this->assertEquals(1, StatsPeriod::DAILY->daysInPeriod());
        $this->assertEquals(7, StatsPeriod::WEEKLY->daysInPeriod());
        $this->assertEquals(30, StatsPeriod::MONTHLY->daysInPeriod());
        $this->assertEquals(365, StatsPeriod::YEARLY->daysInPeriod());
    }

    public function test_date_format(): void
    {
        $this->assertEquals('d/m/Y', StatsPeriod::DAILY->dateFormat());
        $this->assertEquals('\SW Y', StatsPeriod::WEEKLY->dateFormat());
        $this->assertEquals('F Y', StatsPeriod::MONTHLY->dateFormat());
        $this->assertEquals('Y', StatsPeriod::YEARLY->dateFormat());
    }

    public function test_all_time_returns_same_for_previous(): void
    {
        $current = StatsPeriod::ALL_TIME->getCurrentPeriod();
        $previous = StatsPeriod::ALL_TIME->getPreviousPeriod();

        $this->assertEquals($current['start']->toDateString(), $previous['start']->toDateString());
    }
}
