<?php
if (!defined('ABSPATH')) exit;

require_once dirname(__DIR__) . '/mockup/helpers-path.php';
require_once dirname(__DIR__) . '/mockup/builder-core.php';
require_once dirname(__DIR__) . '/mockup/builder-scale.php';
require_once dirname(__DIR__) . '/mockup/builder-write.php';

function frenzy_cart_ensure_mockup(array $cart_item): array {
    if (!empty($cart_item['frenzy_mockup_url'])) {
        return $cart_item; // already set
    }

    // Try session copy first
    if (function_exists('WC') && WC()->session) {
        $last = WC()->session->get('frenzy_last_mockup');
        if (!empty($last['mockup_url'])) {
            $cart_item['frenzy_mockup_url'] = esc_url_raw($last['mockup_url']);
            if (empty($cart_item['frenzy_original_url']) && !empty($last['original_url'])) {
                $cart_item['frenzy_original_url'] = esc_url_raw($last['original_url']);
            }
            if (empty($cart_item['frenzy_transform']) && !empty($last['transform'])) {
                $cart_item['frenzy_transform'] = $last['transform'];
            }
            return $cart_item;
        }
    }

    // Rebuild server-side if we have the original + transform
    if (empty($cart_item['frenzy_original_url']) || empty($cart_item['frenzy_transform'])) {
        return $cart_item;
    }

    $original_path = frenzy_path_from_url($cart_item['frenzy_original_url']);
    if (!$original_path || !file_exists($original_path)) {
        return $cart_item;
    }

    $tf_raw = $cart_item['frenzy_transform'];
    $transform = is_array($tf_raw) ? $tf_raw : json_decode($tf_raw, true);
    if (!is_array($transform) || !isset($transform['x'], $transform['y'], $transform['w'], $transform['h'])) {
        return $cart_item;
    }

    $template_path = dirname(__DIR__, 2) . '/assets/img/mockup-base.png';
    if (!file_exists($template_path)) {
        return $cart_item;
    }

    $result = frenzy_build_mockup($original_path, $template_path, $transform);
    if (is_wp_error($result) || !is_array($result) || empty($result['url'])) {
        return $cart_item;
    }

    $cart_item['frenzy_mockup_url'] = esc_url_raw($result['url']);
    // cache it in the session for subsequent cart loads
    if (function_exists('WC') && WC()->session) {
        WC()->session->set('frenzy_last_mockup', [
            'mockup_url'   => esc_url_raw($result['url']),
            'original_url' => esc_url_raw($cart_item['frenzy_original_url']),
            'transform'    => $transform,
        ]);
    }

    return $cart_item;
}

add_filter('woocommerce_add_cart_item', 'frenzy_cart_ensure_mockup', 10, 1);
add_filter('woocommerce_get_cart_item_from_session', 'frenzy_cart_ensure_mockup', 15, 1);
