<?php
if (!defined('ABSPATH')) exit;

function frenzy_enqueue_modular_scripts() {
    $base_url = plugin_dir_url(__DIR__);
    $version = defined('FRENZY_VERSION') ? FRENZY_VERSION : '1.9.12';

    // CSS on product, cart, checkout
    if (is_product() || is_cart() || is_checkout()) {
        wp_enqueue_style('frenzy-frontend-style', $base_url . 'assets/css/frontend.css', [], $version);
    }

    // JS only on single product where Frenzy is enabled
    if (is_product()) {
        if (function_exists('frenzy_is_enabled') && !frenzy_is_enabled()) return;
        $deps = ['jquery'];
        $scripts = [
            'frenzy-core'      => 'assets/js/frenzy-core.js',
            'frenzy-display'   => 'assets/js/frenzy-display.js',
            'frenzy-printbox'  => 'assets/js/frenzy-printbox.js',
            'frenzy-overlay-core' => 'assets/js/frenzy-overlay-core.js',
            'frenzy-overlay-interactions' => 'assets/js/frenzy-overlay-interactions.js',
            'frenzy-api'       => 'assets/js/frenzy-api.js',
            'frenzy-canvas'    => 'assets/js/frenzy-canvas.js',
            'frenzy-grid'      => 'assets/js/frenzy-grid.js',
            'frenzy-grid-drag' => 'assets/js/frenzy-grid-drag.js',
            'frenzy-upload'    => 'assets/js/frenzy-upload.js',
            'frenzy-submit'    => 'assets/js/frenzy-submit.js',
            'frenzy-init'      => 'assets/js/frenzy-init.js',
        ];
        $order = [
            'frenzy-core' => ['jquery'],
            'frenzy-display' => ['frenzy-core'],
            'frenzy-printbox' => ['frenzy-display'],
            'frenzy-overlay-core' => ['frenzy-printbox'],
            'frenzy-overlay-interactions' => ['frenzy-overlay-core'],
            'frenzy-api' => ['frenzy-core'],
            'frenzy-canvas' => ['frenzy-overlay-core', 'frenzy-overlay-interactions', 'frenzy-api'],
            'frenzy-grid' => ['frenzy-printbox'],
            'frenzy-grid-drag' => ['frenzy-grid'],
            'frenzy-upload' => ['frenzy-overlay-core', 'frenzy-overlay-interactions', 'frenzy-api'],
            'frenzy-submit' => ['frenzy-upload', 'frenzy-canvas', 'frenzy-api'],
            'frenzy-init' => ['frenzy-submit', 'frenzy-grid-drag'],
        ];
        foreach ($scripts as $handle => $path) {
            $deps = isset($order[$handle]) ? $order[$handle] : ['jquery'];
            wp_enqueue_script($handle, $base_url . $path, $deps, $version, true);
        }
        $grid = function_exists('frenzy_get_grid_box') ? frenzy_get_grid_box(get_the_ID()) : ['x'=>320,'y'=>250,'w'=>560,'h'=>650];
        $can_manage_store = current_user_can('manage_woocommerce') || current_user_can('manage_options');
        $localized_data = [
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('frenzy_nonce'),
            'product_id' => get_the_ID(),
            'region'     => $grid,
            'can_edit_grid' => $can_manage_store,
            'can_save_mockup' => $can_manage_store,
        ];
        wp_localize_script('frenzy-core', 'frenzy_ajax', $localized_data);
    }
}
add_action('wp_enqueue_scripts', 'frenzy_enqueue_modular_scripts');
