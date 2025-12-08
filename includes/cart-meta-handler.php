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
    // Fallback to last built mockup stored in session if fields missing
    if (empty($cart_item_data['frenzy_mockup_url']) && function_exists('WC') && WC()->session) {
        $last = WC()->session->get('frenzy_last_mockup');
        if (!empty($last['mockup_url'])) {
            $cart_item_data['frenzy_mockup_url'] = esc_url_raw($last['mockup_url']);
        }
        if (empty($cart_item_data['frenzy_original_url']) && !empty($last['original_url'])) {
            $cart_item_data['frenzy_original_url'] = esc_url_raw($last['original_url']);
        }
        if (empty($cart_item_data['frenzy_transform']) && !empty($last['transform'])) {
            $cart_item_data['frenzy_transform'] = wp_json_encode($last['transform']);
        }
    }
    return $cart_item_data;
}, 10, 3);

/**
 * Restore our custom fields from session so thumbnails/order data keep the mockup.
 */
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

/**
 * Server-side fallback: if mockup URL is missing but we have original art + transform,
 * build the mockup now so the cart thumbnail and order get the composed image.
 */
function frenzy_cart_ensure_mockup(array $cart_item): array {
    if (!empty($cart_item['frenzy_mockup_url']) || empty($cart_item['frenzy_original_url']) || empty($cart_item['frenzy_transform'])) {
        return $cart_item;
    }

    if (!function_exists('frenzy_build_mockup')) {
        return $cart_item;
    }

    $template_path = plugin_dir_path(__FILE__) . '../assets/img/mockup-base.png';
    if (!file_exists($template_path)) {
        return $cart_item;
    }

    $transform = json_decode(stripslashes($cart_item['frenzy_transform']), true);
    if (!is_array($transform) || !isset($transform['x'], $transform['y'], $transform['w'], $transform['h'])) {
        return $cart_item;
    }

    $original_url = esc_url_raw($cart_item['frenzy_original_url']);
    $original_path = function_exists('frenzy_path_from_url') ? frenzy_path_from_url($original_url) : '';
    if (!$original_path || !file_exists($original_path)) {
        $tmp = download_url($original_url);
        if (is_wp_error($tmp)) {
            return $cart_item;
        }
        $original_path = $tmp;
    }

    $built = frenzy_build_mockup($original_path, $template_path, [
        'x' => (int) $transform['x'],
        'y' => (int) $transform['y'],
        'w' => (int) $transform['w'],
        'h' => (int) $transform['h'],
    ]);

    if (!is_wp_error($built) && !empty($built['url'])) {
        $cart_item['frenzy_mockup_url'] = esc_url_raw($built['url']);
    }

    return $cart_item;
}

add_filter('woocommerce_add_cart_item', 'frenzy_cart_ensure_mockup', 10, 1);
add_filter('woocommerce_get_cart_item_from_session', 'frenzy_cart_ensure_mockup', 15, 1);

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (isset($values['frenzy_color_count'])) $item->add_meta_data('Color Count', $values['frenzy_color_count']);
    if (isset($values['frenzy_color_hexes'])) $item->add_meta_data('Hex Colors', $values['frenzy_color_hexes']);
    if (!empty($values['frenzy_mockup_url'])) $item->add_meta_data('_mockup_image_url', esc_url_raw($values['frenzy_mockup_url']));
    if (!empty($values['frenzy_original_url'])) $item->add_meta_data('_original_art_url', esc_url_raw($values['frenzy_original_url']));
    if (!empty($values['frenzy_transform'])) $item->add_meta_data('_mockup_transform', $values['frenzy_transform']);
}, 10, 4);
