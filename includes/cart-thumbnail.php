<?php
if (!defined('ABSPATH')) exit;

function frenzy_get_mockup_url_from_cart_item($cart_item) {
    return !empty($cart_item['frenzy_mockup_url']) ? esc_url($cart_item['frenzy_mockup_url']) : '';
}

function frenzy_get_mockup_url_from_order_item($item) {
    if (!is_a($item, 'WC_Order_Item')) return '';
    $url = $item->get_meta('Mockup', true);
    if (!$url) $url = $item->get_meta('_mockup_image_url', true); // fallback for older entries
    return $url ? esc_url($url) : '';
}

add_filter('woocommerce_cart_item_thumbnail', function ($thumbnail, $cart_item, $cart_item_key) {
    $mockup = frenzy_get_mockup_url_from_cart_item($cart_item);
    if ($mockup) {
        return '<img src="' . $mockup . '" alt="Custom Mockup" style="max-width: 100px; height: auto;" />';
    }
    return $thumbnail;
}, 10, 3);

add_filter('woocommerce_order_item_thumbnail', function ($image, $item) {
    if (!frenzy_is_email_thumbnail_context()) {
        return $image;
    }

    $mockup = frenzy_get_mockup_url_from_order_item($item);
    if (!$mockup) {
        return $image;
    }

    $alt_text = $item->get_name();
    if (!$alt_text) {
        $alt_text = __('Custom Mockup', 'frenzy');
    }

    return sprintf(
        '<img src="%s" alt="%s" style="max-width: 100px; height: auto;" />',
        esc_url($mockup),
        esc_attr($alt_text)
    );
}, 20, 2);

function frenzy_is_email_thumbnail_context(): bool {
    if (!function_exists('did_action')) {
        return false;
    }
    return did_action('woocommerce_email_before_order_table') > 0
        || did_action('woocommerce_email_after_order_table') > 0
        || did_action('woocommerce_email_header') > 0;
}
