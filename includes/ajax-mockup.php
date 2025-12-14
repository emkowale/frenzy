<?php
if (!defined('ABSPATH')) exit;

// Central loader for Frenzy AJAX mockup handlers
require_once __DIR__ . '/mockup/helpers-path.php';
require_once __DIR__ . '/mockup/builder-core.php';
require_once __DIR__ . '/mockup/builder-scale.php';
require_once __DIR__ . '/mockup/builder-write.php';
require_once __DIR__ . '/mockup/handlers-generate.php';
require_once __DIR__ . '/mockup/handlers-save-canvas.php';
require_once __DIR__ . '/mockup/hooks.php';
