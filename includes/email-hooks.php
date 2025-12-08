<?php
/*
 * File: includes/email-hooks.php
 * Description: Adds rendered image thumbnail to WooCommerce customer emails
 * Plugin: Frenzy
 * Author: Eric Kowalewski
 * Last Updated: May 28, 2025 16:03 EDT
 */

add_filter('woocommerce_email_order_items', 'frenzy_add_rendered_image_to_customer_email', 10, 4);

function frenzy_add_rendered_image_to_customer_email($html, $order, $is_admin_email, $plain_text) {
    // Only modify customer email (not admin)
    if ($is_admin_email || $plain_text) {
        return $html;
    }

    foreach ($order->get_items() as $item_id => $item) {
        $rendered_url = wc_get_order_item_meta($item_id, '_frenzy_rendered_image_url', true);
        if ($rendered_url) {
            // Inject image HTML after product name
            $pattern = '/(<td class="woocommerce-table__product-name.*?>.*?' . preg_quote($item->get_name(), '/') . '.*?<\/td>)/si';

            $replacement = '$1<br><img src="' . esc_url($rendered_url) . '" alt="Your Custom Image" style="max-width: 100px; margin-top: 6px;">';

            $html = preg_replace($pattern, $replacement, $html, 1);
        }
    }

    return $html;
}
