<?php
if (!defined('ABSPATH')) exit;

function frenzy_scale_transform(array $transform, int $template_w, int $template_h): array {
    $scaled_w = max(1, min($transform['w'], $template_w));
    $scaled_h = max(1, min($transform['h'], $template_h));
    $dest_x   = max(0, min($transform['x'], $template_w - $scaled_w));
    $dest_y   = max(0, min($transform['y'], $template_h - $scaled_h));
    return [$scaled_w, $scaled_h, $dest_x, $dest_y];
}

function frenzy_scale_to_region(array $region, int $user_w, int $user_h): array {
    $scale = min($region['w'] / $user_w, $region['h'] / $user_h);
    $scaled_w = max(1, (int) floor($user_w * $scale));
    $scaled_h = max(1, (int) floor($user_h * $scale));
    $dest_x = (int) ($region['x'] + floor(($region['w'] - $scaled_w) / 2));
    $dest_y = (int) ($region['y'] + floor(($region['h'] - $scaled_h) / 2));
    return [$scaled_w, $scaled_h, $dest_x, $dest_y];
}
