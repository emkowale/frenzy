<?php
if (!defined('ABSPATH')) exit;

add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id, $variation_id) {
    if (!empty($_POST['frenzy_color_count'])) {
        $cart_item_data['frenzy_color_count'] = sanitize_text_field($_POST['frenzy_color_count']);
    }
    if (!empty($_POST['frenzy_color_hexes'])) {
        $cart_item_data['frenzy_color_hexes'] = sanitize_text_field($_POST['frenzy_color_hexes']);
    }
    if (!empty($_POST['frenzy_mockup_url'])) {
        $cart_item_data['frenzy_mockup_url'] = esc_url_raw($_POST['frenzy_mockup_url']);
    }
    if (!empty($_POST['frenzy_original_url'])) {
        $cart_item_data['frenzy_original_url'] = esc_url_raw($_POST['frenzy_original_url']);
    }
    if (!empty($_POST['frenzy_transform'])) {
        $cart_item_data['frenzy_transform'] = sanitize_text_field($_POST['frenzy_transform']);
    }
    return $cart_item_data;
}, 10, 3);
