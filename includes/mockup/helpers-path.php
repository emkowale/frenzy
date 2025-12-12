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

/**
 * Move an uploaded file into uploads/frenzy-mockups and return [path, url] or WP_Error.
 */
function frenzy_move_to_mockup_dir(string $source_path, string $prefix = 'upload_') {
    if (!$source_path || !file_exists($source_path)) {
        return new WP_Error('mockup_move_missing', 'Source file missing for mockup move.');
    }
    $uploads = wp_upload_dir();
    $out_dir = trailingslashit($uploads['basedir']) . 'frenzy-mockups/';
    if (!is_dir($out_dir) && !wp_mkdir_p($out_dir)) {
        return new WP_Error('mockup_move_dir', 'Failed to create frenzy-mockups directory.');
    }
    $ext = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
    if (!$ext) $ext = 'png';
    $dest = $out_dir . $prefix . uniqid('', true) . '.' . $ext;
    if (!@rename($source_path, $dest)) {
        if (!@copy($source_path, $dest)) {
            return new WP_Error('mockup_move_copy', 'Failed to move uploaded file into frenzy-mockups.');
        }
        @unlink($source_path);
    }
    $url = trailingslashit($uploads['baseurl']) . 'frenzy-mockups/' . basename($dest);
    return ['path' => $dest, 'url' => $url];
}

function frenzy_mockup_output_dir_path(): string {
    $uploads = wp_upload_dir();
    return trailingslashit($uploads['basedir']) . 'frenzy-mockups/';
}

function frenzy_resolve_mockup_file_path(string $value): string {
    $value = trim($value);
    if (!$value) {
        return '';
    }
    $mockup_dir = frenzy_mockup_output_dir_path();
    $normalized_dir = wp_normalize_path($mockup_dir);
    $candidate = wp_normalize_path($value);
    if (strpos($candidate, $normalized_dir) !== 0) {
        $from_url = frenzy_path_from_url($value);
        if (!$from_url) {
            return '';
        }
        $candidate = wp_normalize_path($from_url);
        if (strpos($candidate, $normalized_dir) !== 0) {
            return '';
        }
        $value = $from_url;
    } else {
        $value = $candidate;
    }
    if (!file_exists($value)) {
        return '';
    }
    return $value;
}

function frenzy_delete_mockup_file(string $value): bool {
    $path = frenzy_resolve_mockup_file_path($value);
    if (!$path) {
        return false;
    }
    return (bool) @unlink($path);
}
