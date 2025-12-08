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

add_filter('woocommerce_email_order_item_thumbnail', function ($image, $item, $sent_to_admin, $plain_text, $email) {
    $mockup = frenzy_get_mockup_url_from_order_item($item);
    if ($mockup) {
        $img = '<img src="' . $mockup . '" alt="Custom Mockup" style="width: 100px; height: auto;" />';
        return $img;
    }
    return $image;
}, 10, 5);
