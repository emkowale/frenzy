<?php
if (!defined('ABSPATH')) exit;

function frenzy_load_image_any(string $path) {
    if (!file_exists($path)) return false;
    $data = @file_get_contents($path);
    if ($data === false) return false;
    return @imagecreatefromstring($data);
}

function frenzy_compute_region(int $template_w, int $template_h): array {
    return [
        'x' => (int) round(320 * ($template_w / 1200)),
        'y' => (int) round(250 * ($template_h / 1200)),
        'w' => (int) round(560 * ($template_w / 1200)),
        'h' => (int) round(650 * ($template_h / 1200)),
    ];
}

/**
 * Build a mockup from user art and a template, returning array with path/url or WP_Error.
 */
function frenzy_build_mockup(string $user_path, string $template_path, array $transform = []) {
    if (!$user_path || !file_exists($user_path)) {
        return new WP_Error('mockup_missing_user', 'Uploaded image not found.');
    }
    if (!$template_path || !file_exists($template_path)) {
        return new WP_Error('mockup_missing_template', 'Mockup template not found.');
    }
    $result = frenzy_write_mockup($template_path, $user_path, $transform);
    if (is_array($result) && isset($result['error']) && is_wp_error($result['error'])) {
        return $result['error'];
    }
    if (!is_array($result) || empty($result['url'])) {
        return new WP_Error('mockup_unknown', 'Failed to build mockup.');
    }
    return $result;
}
