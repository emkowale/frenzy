<?php
if (!defined('ABSPATH')) exit;

// Toggle: Use with Frenzy
add_action('woocommerce_product_options_general_product_data', function () {
    echo '<div class="options_group">';
    woocommerce_wp_checkbox([
        'id'          => '_frenzy_enabled',
        'label'       => __('Use with Frenzy', 'frenzy'),
        'desc_tip'    => true,
        'description' => __('Allow customers to upload artwork and generate a mockup.', 'frenzy'),
    ]);
    echo '</div>';
});

add_action('woocommerce_admin_process_product_object', function ($product) {
    if (!$product || !is_a($product, 'WC_Product')) return;
    $use_frenzy = isset($_POST['_frenzy_enabled']) ? 'yes' : 'no';
    $product->update_meta_data('_frenzy_enabled', $use_frenzy);
});

// Default grid box (1200x1200 reference)
function frenzy_get_grid_box(int $product_id = 0): array {
    $defaults = ['x' => 320, 'y' => 250, 'w' => 560, 'h' => 650];
    if (!$product_id) return $defaults;
    $get = function($key, $default) use ($product_id) {
        $val = get_post_meta($product_id, $key, true);
        return ($val === '' || $val === null) ? $default : (int) $val;
    };
    return [
        'x' => $get('_frenzy_grid_left', $defaults['x']),
        'y' => $get('_frenzy_grid_top', $defaults['y']),
        'w' => $get('_frenzy_grid_width', $defaults['w']),
        'h' => $get('_frenzy_grid_height', $defaults['h']),
    ];
}

// AJAX save grid
add_action('wp_ajax_frenzy_save_grid', function () {
    if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }
    $nonce = $_POST['_ajax_nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'frenzy_nonce')) {
        wp_send_json_error(['message' => 'Bad nonce'], 403);
    }
    $product_id = absint($_POST['product_id'] ?? 0);
    if (!$product_id) wp_send_json_error(['message' => 'Missing product_id'], 400);
    $x = isset($_POST['x']) ? (int) $_POST['x'] : null;
    $y = isset($_POST['y']) ? (int) $_POST['y'] : null;
    $w = isset($_POST['w']) ? (int) $_POST['w'] : null;
    $h = isset($_POST['h']) ? (int) $_POST['h'] : null;
    if ($x === null || $y === null || $w === null || $h === null || $w < 1 || $h < 1) {
        wp_send_json_error(['message' => 'Invalid grid dimensions'], 400);
    }
    update_post_meta($product_id, '_frenzy_grid_left', $x);
    update_post_meta($product_id, '_frenzy_grid_top', $y);
    update_post_meta($product_id, '_frenzy_grid_width', $w);
    update_post_meta($product_id, '_frenzy_grid_height', $h);
    wp_send_json_success(['grid' => ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h]]);
});
