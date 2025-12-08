<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('frenzy_quantize_exact_gd')) {
    function frenzy_quantize_exact_gd(string $srcPath, int $k, ?string $destPath = null, int $maxW = 0, int $maxH = 0): array {
        if (!file_exists($srcPath)) return ['error' => 'Source file does not exist'];
        $info = @getimagesize($srcPath);
        if (!$info) return ['error' => 'Invalid image'];

        [$width, $height, $type] = $info;
        if ($maxW > 0 && $maxH > 0 && ($width > $maxW || $height > $maxH)) {
            $ratio = min($maxW / $width, $maxH / $height);
            $newW = (int) floor($width * $ratio);
            $newH = (int) floor($height * $ratio);
        } else {
            $newW = $width;
            $newH = $height;
        }

        switch ($type) {
            case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($srcPath); break;
            case IMAGETYPE_PNG:  $src = @imagecreatefrompng($srcPath);  break;
            case IMAGETYPE_GIF:  $src = @imagecreatefromgif($srcPath);  break;
            default: return ['error' => 'Unsupported image type'];
        }
        if (!$src) return ['error' => 'Unable to read image'];

        if ($newW !== $width || $newH !== $height) {
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($src);
            $src = $resized;
        }

        $pal = imagecreatetruecolor($newW, $newH);
        imagecopy($pal, $src, 0, 0, 0, 0, $newW, $newH);
        imagetruecolortopalette($pal, false, max(1, $k));
        imagetruecolormatch($pal, $src);

        $colors = [];
        $total = $newW * $newH;
        $seen = [];
        for ($x = 0; $x < $newW; $x++) {
            for ($y = 0; $y < $newH; $y++) {
                $idx = imagecolorat($pal, $x, $y);
                $c = imagecolorsforindex($pal, $idx);
                $hex = sprintf('#%02X%02X%02X', $c['red'], $c['green'], $c['blue']);
                if (!isset($seen[$hex])) $seen[$hex] = 0;
                $seen[$hex]++;
            }
        }
        arsort($seen);
        $colors = array_slice(array_keys($seen), 0, $k);

        $outPath = $destPath;
        if ($destPath) {
            imagepng($pal, $destPath, 6);
        }
        imagedestroy($pal);
        imagedestroy($src);

        return [
            'path' => $outPath ?: '',
            'colors' => $colors,
            'color_count' => count($colors),
            'pixels' => $total,
        ];
    }
}
