<?php
if (!defined('ABSPATH')) exit;

function frenzy_is_enabled($product = null): bool {
    $product_id = 0;
    if ($product instanceof WC_Product) {
        $product_id = $product->get_id();
    } elseif (is_numeric($product)) {
        $product_id = (int) $product;
    } elseif (!empty($GLOBALS['product']) && $GLOBALS['product'] instanceof WC_Product) {
        $product_id = $GLOBALS['product']->get_id();
    } elseif (is_singular('product')) {
        $product_id = get_the_ID();
    }
    if (!$product_id) return false;
    return get_post_meta($product_id, '_frenzy_enabled', true) === 'yes';
}

function frenzy_output_upload_block($context = 'primary') {
    global $product;
    if (!is_singular('product') || !$product || !is_a($product, 'WC_Product')) return;
    if (!frenzy_is_enabled($product)) return;

    $type_class = $product->is_type('variable') ? 'frenzy-variable' : 'frenzy-simple';

    echo '<div class="frenzy-cart-block form-row form-row-wide ' . esc_attr($type_class) . '">';
    echo '<div id="frenzy-upload-container">';
    echo '<input type="file" id="frenzy-upload" accept="image/png, image/jpeg, image/webp" style="display:none;">';
    echo '<button type="button" id="frenzy-upload-button" class="button alt frenzy-upload-button">Upload your own image</button>';
    echo '<div id="frenzy-upload-preview" style="display:none;"></div>';
    echo '</div>';
    echo '<div id="frenzy-spinner-overlay" style="display:none;"><div class="frenzy-spinner"></div></div>';
    echo '<input type="hidden" id="frenzy_color_count" name="frenzy_color_count" value="">';
    echo '<input type="hidden" id="frenzy_color_hexes" name="frenzy_color_hexes" value="">';
    echo '<input type="hidden" id="frenzy_mockup_url" name="frenzy_mockup_url" value="">';
    echo '<input type="hidden" id="frenzy_original_url" name="frenzy_original_url" value="">';
    echo '<input type="hidden" id="frenzy_transform" name="frenzy_transform" value="">';
    echo '</div>';
}

// Place uploader above attributes (inside the variations form) for variable products
add_action('woocommerce_before_variations_form', function () {
    global $product;
    if ($product && $product->is_type('variable')) {
        frenzy_output_upload_block();
    }
}, 5);

// Ensure simple products still show the uploader inside the form
add_action('woocommerce_before_add_to_cart_button', function () {
    global $product;
    if ($product && !$product->is_type('variable')) {
        frenzy_output_upload_block();
    }
}, 5);
