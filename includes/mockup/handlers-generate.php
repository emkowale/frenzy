<?php
if (!defined('ABSPATH')) exit;

function frenzy_handle_generate_mockup() {
    if (empty($_FILES['image']['tmp_name']) && empty($_POST['original_url'])) {
        return;
    }

    $nonce = $_POST['_ajax_nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'frenzy_nonce')) {
        wp_send_json_error(['message' => 'Security check failed'], 403);
    }

    $previous_original_url = !empty($_POST['previous_original_url']) ? esc_url_raw($_POST['previous_original_url']) : '';
    $previous_mockup_url = !empty($_POST['previous_mockup_url']) ? esc_url_raw($_POST['previous_mockup_url']) : '';
    if ($previous_original_url) {
        frenzy_delete_mockup_file($previous_original_url);
    }
    if ($previous_mockup_url && $previous_mockup_url !== $previous_original_url) {
        frenzy_delete_mockup_file($previous_mockup_url);
    }

    $transform = [];
    if (!empty($_POST['transform'])) {
        $decoded = json_decode(stripslashes($_POST['transform']), true);
        if (is_array($decoded) && isset($decoded['x'], $decoded['y'], $decoded['w'], $decoded['h'])) {
            $transform = [
                'x' => (int)$decoded['x'],
                'y' => (int)$decoded['y'],
                'w' => max(1, (int)$decoded['w']),
                'h' => max(1, (int)$decoded['h']),
            ];
        }
    }

    $original_path = '';
    $original_url  = '';
    $previous_mockup = [];
    $is_file_upload = !empty($_FILES['image']['tmp_name']);
    if ($is_file_upload && function_exists('WC') && WC()->session) {
        $previous_mockup = WC()->session->get('frenzy_last_mockup') ?: [];
    }

    if (!empty($_FILES['image']['tmp_name'])) {
        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($file['type'], $allowed, true)) {
            wp_send_json_error(['message' => 'Only JPG, PNG, or WebP images are allowed'], 400);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($file, ['test_form' => false, 'mimes' => ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp']]);
        if (!empty($upload['error'])) {
            wp_send_json_error(['message' => 'Upload failed: ' . $upload['error']], 500);
        }
        $moved = frenzy_move_to_mockup_dir($upload['file'], 'upload_');
        if (is_wp_error($moved)) {
            wp_send_json_error(['message' => 'Upload move failed: ' . $moved->get_error_message()], 500);
        }
        $original_path = $moved['path'];
        $original_url  = $moved['url'];
    } elseif (!empty($_POST['original_url'])) {
        $original_url = esc_url_raw($_POST['original_url']);
        $original_path = frenzy_path_from_url($original_url);
        if (!$original_path || !file_exists($original_path)) {
            $tmp = download_url($original_url);
            if (is_wp_error($tmp)) {
                wp_send_json_error(['message' => 'Failed to fetch original image'], 500);
            }
            $original_path = $tmp;
        }
    } else {
        wp_send_json_error(['message' => 'No image received'], 400);
    }

    $template_path = plugin_dir_path(__DIR__) . '../assets/img/mockup-base.png';
    if (!file_exists($template_path)) {
        wp_send_json_error(['message' => 'Mockup template missing at assets/img/mockup-base.png'], 500);
    }

    $mockup = frenzy_build_mockup($original_path, $template_path, $transform);
    if (is_wp_error($mockup)) {
        wp_send_json_error(['message' => $mockup->get_error_message()], 500);
    }

    if (function_exists('WC') && WC()->session) {
        if (!empty($previous_mockup)) {
            frenzy_delete_mockup_file($previous_mockup['original_url'] ?? '');
            frenzy_delete_mockup_file($previous_mockup['mockup_url'] ?? '');
        }
        WC()->session->set('frenzy_last_mockup', [
            'mockup_url'   => esc_url_raw($mockup['url']),
            'original_url' => esc_url_raw($original_url),
            'transform'    => !empty($transform) ? $transform : null,
        ]);
    }

    wp_send_json_success([
        'mockup_url'   => esc_url_raw($mockup['url']),
        'original_url' => esc_url_raw($original_url),
        'transform'    => !empty($transform) ? $transform : null,
    ]);
}
