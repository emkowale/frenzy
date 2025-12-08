<?php
if (!defined('ABSPATH')) exit;

function frenzy_path_from_url(string $url): string {
    $uploads = wp_upload_dir();
    $baseurl = rtrim($uploads['baseurl'], '/');
    $basedir = rtrim($uploads['basedir'], '/');

    if (strpos($url, $baseurl) === 0) {
        return $basedir . substr($url, strlen($baseurl));
    }

    $normalized = preg_replace('#^https?://#', '', $url);
    $normalized_base = preg_replace('#^https?://#', '', $baseurl);
    if (strpos($normalized, $normalized_base) === 0) {
        return $basedir . substr($normalized, strlen($normalized_base));
    }

    $url_path = parse_url($url, PHP_URL_PATH);
    if ($url_path && strpos($url_path, '/wp-content/uploads/') !== false) {
        $relative = strstr($url_path, '/wp-content/uploads/');
        return $basedir . substr($relative, strlen('/wp-content/uploads'));
    }
    return '';
}

/**
 * Copy a provided image path into uploads/frenzy-mockups as-is (no GD), returning the public URL or false on failure.
 */
function frenzy_store_mockup_passthru(string $source_path) {
    if (!$source_path || !file_exists($source_path)) return false;

    $uploads = wp_upload_dir();
    $out_dir = trailingslashit($uploads['basedir']) . 'frenzy-mockups/';
    if (!is_dir($out_dir) && !wp_mkdir_p($out_dir)) {
        return false;
    }

    $ext = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
        $ext = 'png';
    }

    $filename = 'mockup_passthru_' . uniqid('', true) . '.' . $ext;
    $dest_path = $out_dir . $filename;

    if (!@copy($source_path, $dest_path)) {
        return false;
    }

    return trailingslashit($uploads['baseurl']) . 'frenzy-mockups/' . $filename;
}
