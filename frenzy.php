<?php
/*
 * Plugin Name: Frenzy
 * Version: 1.9.7
 * Description: Integrates Dynamic Mockups API with WooCommerce for live previews and featured image setting.
 * Author: Eric Kowalewski
 * Last Updated: 2025-08-11 19:45 EDT
 */

if (!defined('ABSPATH')) exit;



define('BUMBLEBEE_VERSION', '1.9.7');
// --- Admin ---
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';

// --- Product Meta (mockup + smart objects) ---
require_once plugin_dir_path(__FILE__) . 'includes/product-meta-box.php';

// --- Frontend UI (markup only; no handlers) ---
require_once plugin_dir_path(__FILE__) . 'includes/frontend-ui.php';

// --- AJAX: Python vectorizer (single source of truth for optimization) ---
require_once plugin_dir_path(__FILE__) . 'includes/ajax-vectorize.php';

// --- AJAX: Render to Dynamic Mockups ---
require_once plugin_dir_path(__FILE__) . 'includes/ajax-render.php';

// --- Enqueue modular JS/CSS (spinner, popup, uploader, etc.) ---
require_once plugin_dir_path(__FILE__) . 'includes/enqueue-scripts.php';

// --- Business logic still in use ---
require_once plugin_dir_path(__FILE__) . 'includes/validate-product-price.php';
require_once plugin_dir_path(__FILE__) . 'includes/cart-thumbnail.php';
require_once plugin_dir_path(__FILE__) . 'includes/test-email-preview.php';
require_once plugin_dir_path(__FILE__) . 'includes/save-order-meta.php';
require_once plugin_dir_path(__FILE__) . 'includes/email-render-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/remove-email-product-image.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-order-meta.php';
require_once plugin_dir_path(__FILE__) . 'includes/cart-meta-handler.php';

// --- Removed legacy/GD paths ---
// require_once plugin_dir_path(__FILE__) . 'includes/upload-handler.php';  // ❌ replaced by Python-only vectorize
// require_once plugin_dir_path(__FILE__) . 'includes/color-quantize.php';  // ❌ unused now that Python handles quantization
