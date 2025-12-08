<?php
if (!defined('ABSPATH')) exit;

add_filter('woocommerce_cart_item_thumbnail', function ($thumbnail, $cart_item, $cart_item_key) {
    $mockup = !empty($cart_item['frenzy_mockup_url']) ? esc_url($cart_item['frenzy_mockup_url']) : '';
    if ($mockup) {
        return '<img src="' . $mockup . '" alt="Custom Mockup" style="max-width: 100px; height: auto;" />';
    }
    return $thumbnail;
}, 10, 3);
