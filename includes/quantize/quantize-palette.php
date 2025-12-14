<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('frenzy_quantize_palette')) {
    function frenzy_quantize_palette(array $pixels, int $k): array {
        $counts = [];
        foreach ($pixels as $hex) {
            if (!isset($counts[$hex])) $counts[$hex] = 0;
            $counts[$hex]++;
        }
        arsort($counts);
        $colors = array_slice(array_keys($counts), 0, $k);
        return [
            'colors' => $colors,
            'color_count' => count($colors),
        ];
    }
}
