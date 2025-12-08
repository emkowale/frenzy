<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_frenzy_generate_mockup', 'frenzy_handle_generate_mockup');
add_action('wp_ajax_nopriv_frenzy_generate_mockup', 'frenzy_handle_generate_mockup');
add_action('wp_ajax_frenzy_save_canvas_mockup', 'frenzy_handle_save_canvas_mockup');
add_action('wp_ajax_nopriv_frenzy_save_canvas_mockup', 'frenzy_handle_save_canvas_mockup');
