<?php
/*
 * Version: 1.9.10
 * Plugin Name: Frenzy
 * Description: WooCommerce helper for Frenzy mockups and cart metadata.
 * Author: Eric Kowalewski
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;
define('FRENZY_VERSION','1.9.10');
// Core includes (defensive to avoid fatal if a file is missing on the server)
function frenzy_safe_include($relative) {
    $path = plugin_dir_path(__FILE__) . ltrim($relative, '/');
    if (file_exists($path)) {
        require_once $path;
    } else {
        error_log('[Frenzy] Missing include: ' . $path);
    }
}

frenzy_safe_include('includes/product-meta-box.php');
frenzy_safe_include('includes/frontend-ui.php');
frenzy_safe_include('includes/ajax-mockup.php');
frenzy_safe_include('includes/cart-meta-handler.php');
frenzy_safe_include('includes/cart-thumbnail.php');
frenzy_safe_include('includes/enqueue-scripts.php');
frenzy_safe_include('includes/save-order-meta.php');
