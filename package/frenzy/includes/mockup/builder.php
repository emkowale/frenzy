<?php
if (!defined('ABSPATH')) exit;
function frenzy_load_image_any(string $path) {
    if (!file_exists($path)) return false;
    $data = @file_get_contents($path);
    if ($data === false) return false;
    return @imagecreatefromstring($data);
}
function frenzy_build_mockup(string $user_path, string $template_path, array $transform = []) {
    if (!extension_loaded('gd')) return new WP_Error('mockup_no_gd', 'GD is required to build mockups.');

    $template = frenzy_load_image_any($template_path);
    if (!$template) return new WP_Error('mockup_template_load', 'Failed to load mockup template.');

    $user_data = @file_get_contents($user_path);
    $user_img  = $user_data ? @imagecreatefromstring($user_data) : false;
    if (!$user_img) {
        imagedestroy($template);
        return new WP_Error('mockup_user_load', 'Could not read uploaded image.');
    }

    $template_w = imagesx($template);
    $template_h = imagesy($template);
    if ($template_w <= 0 || $template_h <= 0) {
        imagedestroy($template);
        imagedestroy($user_img);
        return new WP_Error('mockup_template_dims', 'Template image has invalid dimensions.');
    }

    $region = [
        'x' => (int) round(320 * ($template_w / 1200)),
        'y' => (int) round(250 * ($template_h / 1200)),
        'w' => (int) round(560 * ($template_w / 1200)),
        'h' => (int) round(650 * ($template_h / 1200)),
    ];

    $user_w = imagesx($user_img);
    $user_h = imagesy($user_img);
    if ($user_w === 0 || $user_h === 0) {
        imagedestroy($template);
        imagedestroy($user_img);
        return new WP_Error('mockup_bad_dims', 'Uploaded image has invalid dimensions.');
    }

    if (!empty($transform)) {
        $scaled_w = max(1, min($transform['w'], $template_w));
        $scaled_h = max(1, min($transform['h'], $template_h));
        $dest_x   = max(0, min($transform['x'], $template_w - $scaled_w));
        $dest_y   = max(0, min($transform['y'], $template_h - $scaled_h));
    } else {
        $scale = min($region['w'] / $user_w, $region['h'] / $user_h);
        $scaled_w = max(1, (int) floor($user_w * $scale));
        $scaled_h = max(1, (int) floor($user_h * $scale));
        $dest_x = (int) ($region['x'] + floor(($region['w'] - $scaled_w) / 2));
        $dest_y = (int) ($region['y'] + floor(($region['h'] - $scaled_h) / 2));
    }

    $scaled = imagecreatetruecolor($scaled_w, $scaled_h);
    imagealphablending($scaled, false);
    imagesavealpha($scaled, true);
    imagecopyresampled($scaled, $user_img, 0, 0, 0, 0, $scaled_w, $scaled_h, $user_w, $user_h);
    imagealphablending($template, false);
    imagesavealpha($template, true);

    for ($y = 0; $y < $scaled_h; $y++) {
        for ($x = 0; $x < $scaled_w; $x++) {
            if (($dest_x + $x) >= $template_w || ($dest_y + $y) >= $template_h) continue;
            $base_idx = imagecolorat($template, $dest_x + $x, $dest_y + $y);
            $art_idx  = imagecolorat($scaled, $x, $y);
            $base = imagecolorsforindex($template, $base_idx);
            $art  = imagecolorsforindex($scaled, $art_idx);
            $alpha = 1 - ($art['alpha'] / 127);
            if ($alpha <= 0) continue;
            $r = (int) (($art['red'] * $alpha) + ($base['red'] * (1 - $alpha)));
            $g = (int) (($art['green'] * $alpha) + ($base['green'] * (1 - $alpha)));
            $b = (int) (($art['blue'] * $alpha) + ($base['blue'] * (1 - $alpha)));
            imagesetpixel($template, $dest_x + $x, $dest_y + $y, imagecolorallocatealpha($template, $r, $g, $b, 0));
        }
    }

    $uploads = wp_upload_dir();
    $out_dir = trailingslashit($uploads['basedir']) . 'frenzy-mockups/';
    if (!is_dir($out_dir) && !wp_mkdir_p($out_dir)) {
        imagedestroy($template);
        imagedestroy($scaled);
        imagedestroy($user_img);
        return new WP_Error('mockup_write_dir', 'Failed to create mockup output directory.');
    }

    $out_name = 'mockup_' . uniqid('', true) . '.png';
    $out_path = $out_dir . $out_name;
    $saved = imagepng($template, $out_path, 6);
    imagedestroy($template);
    imagedestroy($scaled);
    imagedestroy($user_img);
    if (!$saved) return new WP_Error('mockup_save_failed', 'Could not save mockup.');

    $out_url = trailingslashit($uploads['baseurl']) . 'frenzy-mockups/' . $out_name;
    return ['path' => $out_path, 'url' => $out_url];
}
