<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Table;
use DateTimeInterface;

/**
 * Peak / off-peak / night rate automation.
 *
 * Bands are configured in Settings (times in 24h "H:i"):
 *   peak_start .. peak_end      -> 'peak'   (uses hourly_rate × multiplier)
 *   night_start .. night_end    -> 'night'  (uses table.night_rate when set)
 *   anything else               -> 'off_peak' (same price, labelled for reports)
 * A band that crosses midnight is supported (start > end).
 */
class RateService
{
    public static function detectBand(?DateTimeInterface $at = null, ?array $table = null): string
    {
        $at = $at ?? new \DateTimeImmutable('now', new \DateTimeZone('Asia/Karachi'));
        $hour = (int) $at->format('H');
        $minute = (int) $at->format('i');
        $minuteOfDay = $hour * 60 + $minute;

        $settings = SettingsService::all();

        // Peak hours
        $peakEnabled = (int) ($settings['peak_enabled'] ?? 1) === 1;
        if ($peakEnabled) {
            $peakStart = $settings['peak_start'] ?? '19:00';
            $peakEnd   = $settings['peak_end'] ?? '00:00';
            if (self::withinBand($minuteOfDay, $peakStart, $peakEnd)) {
                return 'peak';
            }
        }

        // Night hours (only when the table carries a night rate)
        $nightStart = $settings['night_start'] ?? '00:00';
        $nightEnd   = $settings['night_end'] ?? '06:00';
        $tableHasNight = $table !== null && (float) ($table['night_rate'] ?? 0) > 0;
        $tableHasNight = $tableHasNight || ($table === null && SettingsService::get('night_enabled', '1') === '1');
        if ($tableHasNight && self::withinBand($minuteOfDay, $nightStart, $nightEnd)) {
            return 'night';
        }

        return 'off_peak';
    }

    /**
     * Decide the effective rate_type + rate for a new session.
     * Explicit manual choices (frame/vip/custom) are respected;
     * 'hourly' / 'off_peak' / 'peak' / 'night' are auto-resolved.
     */
    public static function resolveRate(string $rateType, array $table, ?DateTimeInterface $at = null): array
    {
        $hourly = (float) ($table['hourly_rate'] ?? 0);
        $manual = ['frame', 'vip', 'custom'];

        if (in_array($rateType, $manual, true)) {
            $rate = match ($rateType) {
                'vip'   => (float) ($table['vip_rate'] ?? $hourly),
                default => $hourly,
            };
            return ['rate_type' => $rateType, 'rate' => $rate];
        }

        $band = self::detectBand($at, $table);

        $rate = $hourly;
        if ($band === 'peak') {
            $mult = (float) (SettingsService::get('peak_rate_multiplier') ?: 1);
            $rate = $hourly * ($mult > 0 ? $mult : 1);
        } elseif ($band === 'night') {
            $night = (float) ($table['night_rate'] ?? 0);
            $rate = $night > 0 ? $night : $hourly;
        }

        return ['rate_type' => $band, 'rate' => $rate];
    }

    private static function withinBand(int $minuteOfDay, string $start, string $end): bool
    {
        $startMinutes = self::toMinutes($start);
        $endMinutes   = self::toMinutes($end);

        if ($startMinutes === $endMinutes) {
            return false; // disabled / zero-length band
        }

        if ($startMinutes < $endMinutes) {
            return $minuteOfDay >= $startMinutes && $minuteOfDay < $endMinutes;
        }

        // Crosses midnight
        return $minuteOfDay >= $startMinutes || $minuteOfDay < $endMinutes;
    }

    private static function toMinutes(string $time): int
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
            return 0;
        }
        return ((int) $m[1] % 24) * 60 + (int) $m[2];
    }
}