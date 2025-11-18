<?php

declare(strict_types=1);

namespace App\Domains\User\Enums;

use Illuminate\Support\Carbon;

enum StatsPeriod: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case ALL_TIME = 'all_time';

    /**
     * Obtenir le libellé.
     */
    public function label(): string
    {
        return match($this) {
            self::DAILY => 'Quotidien',
            self::WEEKLY => 'Hebdomadaire',
            self::MONTHLY => 'Mensuel',
            self::YEARLY => 'Annuel',
            self::ALL_TIME => 'Tout le temps',
        };
    }

    /**
     * Obtenir les dates de début et fin pour la période actuelle.
     */
    public function getCurrentPeriod(): array
    {
        $now = Carbon::now();

        return match($this) {
            self::DAILY => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            self::WEEKLY => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            self::MONTHLY => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            self::YEARLY => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            self::ALL_TIME => [
                'start' => Carbon::create(2020, 1, 1),
                'end' => $now->copy()->endOfDay(),
            ],
        };
    }

    /**
     * Obtenir la période précédente.
     */
    public function getPreviousPeriod(): array
    {
        $now = Carbon::now();

        return match($this) {
            self::DAILY => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
            ],
            self::WEEKLY => [
                'start' => $now->copy()->subWeek()->startOfWeek(),
                'end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            self::MONTHLY => [
                'start' => $now->copy()->subMonth()->startOfMonth(),
                'end' => $now->copy()->subMonth()->endOfMonth(),
            ],
            self::YEARLY => [
                'start' => $now->copy()->subYear()->startOfYear(),
                'end' => $now->copy()->subYear()->endOfYear(),
            ],
            self::ALL_TIME => $this->getCurrentPeriod(),
        };
    }

    /**
     * Nombre de jours dans la période.
     */
    public function daysInPeriod(): int
    {
        return match($this) {
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::MONTHLY => 30,
            self::YEARLY => 365,
            self::ALL_TIME => 9999,
        };
    }

    /**
     * Format d'affichage de la date pour cette période.
     */
    public function dateFormat(): string
    {
        return match($this) {
            self::DAILY => 'd/m/Y',
            self::WEEKLY => '\SW Y',
            self::MONTHLY => 'F Y',
            self::YEARLY => 'Y',
            self::ALL_TIME => 'Y',
        };
    }
}
