<?php
if (!defined('ABSPATH')) exit;

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (isset($values['frenzy_color_count'])) $item->add_meta_data('Color Count', $values['frenzy_color_count']);
    if (isset($values['frenzy_color_hexes'])) $item->add_meta_data('Hex Colors', $values['frenzy_color_hexes']);
    if (!empty($values['frenzy_mockup_url'])) $item->add_meta_data('Mockup', esc_url_raw($values['frenzy_mockup_url']));
    if (!empty($values['frenzy_original_url'])) $item->add_meta_data('Original Art Front', esc_url_raw($values['frenzy_original_url']));
    if (!$item->get_meta('Vendor Code')) {
        $vendor_code = frenzy_get_order_item_vendor_code($item);
        if ($vendor_code !== '') {
            $item->add_meta_data('Vendor Code', $vendor_code);
        }
    }
}, 10, 4);

// Force new orders to land in "on-hold" so artwork can be reviewed before processing
add_action('woocommerce_checkout_order_created', function ($order) {
    if (!$order || !is_a($order, 'WC_Order')) return;
    if ($order->has_status('on-hold')) return;
    $order->set_status('on-hold');
    $order->add_order_note(__('Set to on-hold by Frenzy to review artwork.', 'frenzy'));
    $order->save();
}, 5);

// Render “View” buttons instead of raw URLs for our Frenzy fields
add_filter('woocommerce_order_item_get_formatted_meta_data', function ($formatted_meta, $item) {
    foreach ($formatted_meta as $key => $meta) {
        $label = $meta->display_key;
        $val = $meta->value;
        $isMockup = ($label === 'Mockup');
        $isOriginal = ($label === 'Original Art Front');
        $isHiddenKey = ($label === '_mockup_image_url' || $label === '_original_art_url' || strpos($label, '_mockup_transform') !== false || $label === '_mockup_transform');

        if (($isMockup || $isOriginal) && filter_var($val, FILTER_VALIDATE_URL)) {
            $text = $isMockup ? 'View Mockup' : 'View Original Art Front';
            $formatted_meta[$key]->display_value = '<a href="' . esc_url($val) . '" target="_blank" rel="noopener noreferrer" class="button button-small frenzy-order-meta-action" style="text-decoration:none;">' . esc_html($text) . '</a>';
        }

        // Hide any legacy/raw keys we don't want to show
        if ($isHiddenKey) {
            unset($formatted_meta[$key]);
        }
    }
    return $formatted_meta;
}, 10, 2);

function frenzy_get_order_item_vendor_code($item): string {
    $product = $item->get_product();
    return frenzy_fallback_vendor_code_from_product($product);
}

function frenzy_fallback_vendor_code_from_product($product): string {
    if (!$product) return '';

    $keys = ['vendor_code', 'vendor code', 'vendorcode', 'vendor_id', 'vendor', 'quality', 'quality code'];
    $match = frenzy_match_product_meta($product, $keys);
    if ($match !== '') {
        return $match;
    }

    if (method_exists($product, 'get_parent_id')) {
        $parent_id = (int) $product->get_parent_id();
        if ($parent_id > 0) {
            $parent = wc_get_product($parent_id);
            if ($parent) {
                return frenzy_match_product_meta($parent, $keys);
            }
        }
    }

    return '';
}

function frenzy_match_product_meta($product, array $keys): string {
    $meta_map = [];
    foreach ($product->get_meta_data() as $meta) {
        $data = $meta->get_data();
        $key = strtolower((string) ($data['key'] ?? ''));
        $value = $data['value'] ?? '';
        if ($key === '' || $value === '' || is_array($value) || is_object($value)) {
            continue;
        }
        if (!isset($meta_map[$key])) {
            $meta_map[$key] = (string) $value;
        }
    }

    foreach ($keys as $key) {
        $lookup = strtolower($key);
        if (isset($meta_map[$lookup])) {
            return $meta_map[$lookup];
        }
    }

    return '';
}
