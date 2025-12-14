<?php
if (!defined('ABSPATH')) exit;

add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values) {
    if (isset($values['frenzy_color_count'])) {
        $cart_item['frenzy_color_count'] = $values['frenzy_color_count'];
    }
    if (isset($values['frenzy_color_hexes'])) {
        $cart_item['frenzy_color_hexes'] = $values['frenzy_color_hexes'];
    }
    if (isset($values['frenzy_mockup_url'])) {
        $cart_item['frenzy_mockup_url'] = $values['frenzy_mockup_url'];
    }
    if (isset($values['frenzy_original_url'])) {
        $cart_item['frenzy_original_url'] = $values['frenzy_original_url'];
    }
    if (isset($values['frenzy_transform'])) {
        $cart_item['frenzy_transform'] = $values['frenzy_transform'];
    }
    return $cart_item;
}, 10, 2);
