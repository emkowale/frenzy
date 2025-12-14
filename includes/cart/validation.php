<?php
if (!defined('ABSPATH')) exit;

add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity) {
    if (!function_exists('frenzy_is_enabled') || !frenzy_is_enabled($product_id)) {
        return $passed;
    }
    $mockup = isset($_POST['frenzy_mockup_url']) ? trim($_POST['frenzy_mockup_url']) : '';
    $transform = isset($_POST['frenzy_transform']) ? trim($_POST['frenzy_transform']) : '';
    if ($mockup && $transform) {
        return $passed;
    }
    wc_add_notice(__('Please upload and position your artwork before adding this product to the cart.', 'frenzy'), 'error');
    return false;
}, 10, 3);
