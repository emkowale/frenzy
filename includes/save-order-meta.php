<?php
if (!defined('ABSPATH')) exit;

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (!empty($values['frenzy_mockup_url'])) {
        $item->add_meta_data('_mockup_image_url', esc_url_raw($values['frenzy_mockup_url']));
    }
    if (!empty($values['frenzy_original_url'])) {
        $item->add_meta_data('_original_art_url', esc_url_raw($values['frenzy_original_url']));
    }
    if (!empty($values['frenzy_transform'])) {
        $item->add_meta_data('_mockup_transform', $values['frenzy_transform']);
    }
    if (isset($values['frenzy_color_count'])) {
        $item->add_meta_data('Color Count', $values['frenzy_color_count']);
    }
    if (isset($values['frenzy_color_hexes'])) {
        $item->add_meta_data('Hex Colors', $values['frenzy_color_hexes']);
    }
}, 10, 4);
