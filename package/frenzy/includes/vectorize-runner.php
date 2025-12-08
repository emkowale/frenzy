<?php
/*
 * File: includes/vectorize-runner.php
 * Description: Runs vectorize-image.py and returns PNG path + extracted hex colors
 * Plugin: Frenzy
 * Author: Eric Kowalewski
 * Last Updated: 2025-08-06 03:29 EDT
 */

defined('ABSPATH') || exit;

function frenzy_vectorize_image($image_path, $color_count = 2) {
    if (!file_exists($image_path)) return false;

    $plugin_dir = plugin_dir_path(__FILE__);
    $script_path = escapeshellarg($plugin_dir . 'vectorize-image.py');
    $image_path_escaped = escapeshellarg($image_path);
    $count = escapeshellarg((int) $color_count);

    $cmd = "python3 $script_path $image_path_escaped $count";
    $json = shell_exec($cmd);

    $result = json_decode($json, true);
    if (!$result || !isset($result['path']) || !isset($result['colors'])) {
        error_log("❌ Vectorization failed or returned invalid JSON: $json");
        return false;
    }

    return [
        'path'         => $result['path'],
        'color_count'  => count($result['colors']),
        'color_hexes'  => $result['colors']
    ];
}
