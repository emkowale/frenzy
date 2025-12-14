<?php
if (!defined('ABSPATH')) exit;

function frenzy_write_mockup(string $template_path, string $user_path, array $transform): array {
    if (!extension_loaded('gd')) return ['error' => new WP_Error('mockup_no_gd', 'GD is required to build mockups.')];

    $template = frenzy_load_image_any($template_path);
    if (!$template) return ['error' => new WP_Error('mockup_template_load', 'Failed to load mockup template.')];

    $user_data = @file_get_contents($user_path);
    $user_img  = $user_data ? @imagecreatefromstring($user_data) : false;
    if (!$user_img) {
        imagedestroy($template);
        return ['error' => new WP_Error('mockup_user_load', 'Could not read uploaded image.')];
    }

    $template_w = imagesx($template);
    $template_h = imagesy($template);
    if ($template_w <= 0 || $template_h <= 0) {
        imagedestroy($template);
        imagedestroy($user_img);
        return ['error' => new WP_Error('mockup_template_dims', 'Template image has invalid dimensions.')];
    }

    $region = frenzy_compute_region($template_w, $template_h);
    $user_w = imagesx($user_img);
    $user_h = imagesy($user_img);
    if ($user_w === 0 || $user_h === 0) {
        imagedestroy($template);
        imagedestroy($user_img);
        return ['error' => new WP_Error('mockup_bad_dims', 'Uploaded image has invalid dimensions.')];
    }

    if (!empty($transform)) {
        [$scaled_w, $scaled_h, $dest_x, $dest_y] = frenzy_scale_transform($transform, $template_w, $template_h);
    } else {
        [$scaled_w, $scaled_h, $dest_x, $dest_y] = frenzy_scale_to_region($region, $user_w, $user_h);
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
        return ['error' => new WP_Error('mockup_write_dir', 'Failed to create mockup output directory.')];
    }

    $out_name = 'mockup_' . uniqid('', true) . '.png';
    $out_path = $out_dir . $out_name;
    $saved = imagepng($template, $out_path, 6);
    imagedestroy($template);
    imagedestroy($scaled);
    imagedestroy($user_img);

    if (!$saved) return ['error' => new WP_Error('mockup_save_failed', 'Could not save mockup.')];

    $out_url = trailingslashit($uploads['baseurl']) . 'frenzy-mockups/' . $out_name;
    return ['path' => $out_path, 'url' => $out_url];
}
