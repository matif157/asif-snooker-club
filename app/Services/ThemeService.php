<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\User;

/**
 * Theme & branding service.
 *
 * The club accent color is stored in settings (accent_color). We map it onto
 * the Tailwind `emerald` palette via CSS custom properties so every emerald-*
 * utility, button, badge, nav state and chart follows the club's chosen colour.
 */
class ThemeService
{
    public const DEFAULT_ACCENT = '#10b981';

    /**
     * Shades derived from the base accent hex.
     * @return array<string,string> e.g. ['300'=>'#…', '400'=>'#…', '500'=>'#…', '600'=>'#…']
     */
    public static function palette(?string $hex = null): array
    {
        $hex = self::normalizeHex($hex ?? SettingsService::get('accent_color', self::DEFAULT_ACCENT));
        [$r, $g, $b] = self::hexToRgb($hex);

        $shade = static fn (float $towardWhite, float $towardBlack): string => self::rgbToHex(
            (int) round($r + (255 - $r) * $towardWhite - $r * $towardBlack),
            (int) round($g + (255 - $g) * $towardWhite - $g * $towardBlack),
            (int) round($b + (255 - $b) * $towardWhite - $b * $towardBlack)
        );

        return [
            '300' => $shade(0.30, 0.00),
            '400' => $shade(0.15, 0.00),
            '500' => $hex,
            '600' => $shade(0.00, 0.14),
        ];
    }

    /**
     * `<style>` block declaring hex + rgb dash-* variables for the accent.
     */
    public static function cssVars(?string $hex = null): string
    {
        $p = self::palette($hex);
        $vars = [];
        foreach (['300', '400', '500', '600'] as $step) {
            $vars[] = "--a-{$step}: {$p[$step]};";
            $vars[] = "--a-rgb-{$step}: " . implode(' ', self::hexToRgb($p[$step])) . ';';
        }
        return "<style>:root{" . implode('', $vars) . "}</style>" . PHP_EOL;
    }

    /**
     * JavaScript block that overrides Tailwind's `emerald` palette with the
     * accent CSS variables. Append inside an existing
     * `tailwind.config = { theme: { extend: { colors: { emerald: {...} } } } }`
     * (our layouts inline this mapping for deterministic behaviour).
     */
    public static function emeraldMapping(): string
    {
        return "'300':'rgb(var(--a-rgb-300) / <alpha-value>)','400':'rgb(var(--a-rgb-400) / <alpha-value>)','500':'rgb(var(--a-rgb-500) / <alpha-value>)','600':'rgb(var(--a-rgb-600) / <alpha-value>)'";
    }

    /**
     * Boot script that applies the current user's stored theme before paint.
     */
    public static function themeBoot(string $theme = 'dark'): string
    {
        $json = json_encode($theme);
        return <<<HTML
<script>
(function(){
    var t = {$json};
    if (t === 'auto') {
        t = window.matchMedia && matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }
    document.documentElement.classList.toggle('dark', t !== 'light');
    document.documentElement.dataset.theme = t;
})();
</script>
HTML;
    }

    /**
     * The current user's theme preference (falls back to 'dark').
     */
    public static function userTheme(): string
    {
        $u = Auth::user();
        if (!$u) {
            return 'dark';
        }
        $theme = (string) ($u->theme ?? 'dark');
        return in_array($theme, ['dark', 'light', 'auto'], true) ? $theme : 'dark';
    }

    /** Active club accent colour. */
    public static function accentHex(): string
    {
        return self::normalizeHex((string) SettingsService::get('accent_color', self::DEFAULT_ACCENT));
    }

    private static function normalizeHex(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return preg_match('/^[0-9a-fA-F]{6}$/', $hex) ? ('#' . strtolower($hex)) : self::DEFAULT_ACCENT;
    }

    /** @return array{0:int,1:int,2:int} */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }
}