<?php
if (!defined('ABSPATH')) exit;

/**
 * Accepts a ready-made mockup image (e.g., from frontend canvas) and stores it in uploads/frenzy-mockups.
 */
function frenzy_handle_save_canvas_mockup() {
    $nonce = $_POST['_ajax_nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'frenzy_nonce')) {
        wp_send_json_error(['message' => 'Security check failed'], 403);
    }

    $image_file = $_FILES['image']['tmp_name'] ?? '';
    $data_url   = $_POST['data_url'] ?? '';
    $image_url  = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
    $downloaded_tmp = '';
    if (!$image_file && !$data_url && !$image_url) {
        wp_send_json_error(['message' => 'No image provided'], 400);
    }

    $uploads = wp_upload_dir();
    $out_dir = trailingslashit($uploads['basedir']) . 'frenzy-mockups/';
    if (!is_dir($out_dir) && !wp_mkdir_p($out_dir)) {
        wp_send_json_error(['message' => 'Failed to create mockup output directory'], 500);
    }

    $extension = 'png';
    if ($image_url) {
        $url_path = parse_url($image_url, PHP_URL_PATH);
        $ext_candidate = $url_path ? strtolower(pathinfo($url_path, PATHINFO_EXTENSION)) : '';
        if (in_array($ext_candidate, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
            $extension = $ext_candidate === 'jpg' ? 'jpeg' : $ext_candidate;
        }
    }

    $filename = 'mockup_canvas_' . uniqid('', true) . '.' . $extension;
    $out_path = $out_dir . $filename;

    if ($image_file) {
        if (!@move_uploaded_file($image_file, $out_path)) {
            wp_send_json_error(['message' => 'Failed to save mockup'], 500);
        }
    } elseif ($image_url) {
        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $downloaded_tmp = download_url($image_url);
        if (is_wp_error($downloaded_tmp) || !file_exists($downloaded_tmp)) {
            wp_send_json_error(['message' => 'Failed to fetch image'], 500);
        }
        if (!@copy($downloaded_tmp, $out_path)) {
            @unlink($downloaded_tmp);
            wp_send_json_error(['message' => 'Failed to save mockup'], 500);
        }
    } else {
        if (strpos($data_url, 'base64,') !== false) {
            $parts = explode('base64,', $data_url, 2);
            $decoded = base64_decode($parts[1]);
            if ($decoded === false) {
                wp_send_json_error(['message' => 'Invalid image data'], 400);
            }
            if (!@file_put_contents($out_path, $decoded)) {
                wp_send_json_error(['message' => 'Failed to write mockup'], 500);
            }
        } else {
            wp_send_json_error(['message' => 'Invalid image data'], 400);
        }
    }

    $out_url = trailingslashit($uploads['baseurl']) . 'frenzy-mockups/' . $filename;
    if ($downloaded_tmp) {
        @unlink($downloaded_tmp);
    }

    if (function_exists('WC') && WC()->session) {
        WC()->session->set('frenzy_last_mockup', [
            'mockup_url'   => esc_url_raw($out_url),
            'original_url' => '',
            'transform'    => null,
        ]);
    }

    wp_send_json_success(['mockup_url' => esc_url_raw($out_url)]);
}
