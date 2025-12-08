<?php
/*
 * File: includes/color-quantize.php
 * Description: Exact-K color quantization with GD (no dithering, no accidental shades). Returns PNG path and palette.
 * Plugin: Frenzy
 * Author: Eric Kowalewski
 * Version: 1.9.6
 * Last Updated: 2025-08-11 15:45 EDT
 */

if (!function_exists('frenzy_quantize_exact_gd')) {
    /**
     * Quantize an image to exactly $k colors with no dithering and no smoothing.
     * Returns ['ok'=>bool, 'out'=>string, 'colors'=>array(hex), 'err'=>string].
     *
     * Notes:
     * - Uses imagetruecolortopalette($dither=false).
     * - Rebuilds a truecolor image by mapping each pixel to the exact palette color (eliminates semi-transparency).
     * - Optionally pre-resizes with nearest-neighbor to avoid resample blends.
     */
    function frenzy_quantize_exact_gd(string $srcPath, int $k, ?string $destPath = null, int $maxW = 0, int $maxH = 0): array
    {
        if (!file_exists($srcPath)) {
            return ['ok'=>false, 'err'=>'Source file not found'];
        }
        if ($k < 1) $k = 1;
        if ($k > 256) $k = 256; // GD limit

        // Load
        $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'png':  $src = imagecreatefrompng($srcPath); break;
            case 'jpg':
            case 'jpeg': $src = imagecreatefromjpeg($srcPath); break;
            default:     return ['ok'=>false, 'err'=>'Unsupported format'];
        }
        if (!$src) return ['ok'=>false, 'err'=>'Could not load source'];

        imagesavealpha($src, true);
        imagealphablending($src, false);

        $w = imagesx($src);
        $h = imagesy($src);

        // Optional pre-resize WITHOUT interpolation (prevents new shades)
        if ($maxW > 0 || $maxH > 0) {
            $scale = 1.0;
            if ($maxW > 0 && $w > $maxW) $scale = min($scale, $maxW / $w);
            if ($maxH > 0 && $h > $maxH) $scale = min($scale, $maxH / $h);
            if ($scale < 1.0) {
                $nw = max(1, (int)floor($w * $scale));
                $nh = max(1, (int)floor($h * $scale));
                $nn = imagecreatetruecolor($nw, $nh);
                imagesavealpha($nn, true);
                imagealphablending($nn, false);
                // nearest-neighbor: use imagecopyresized (NOT resampled)
                imagecopyresized($nn, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($src);
                $src = $nn; $w = $nw; $h = $nh;
            }
        }

        // Create a temp copy in palette mode with EXACTLY $k colors, no dithering
        $pal = imagecreatetruecolor($w, $h);
        imagesavealpha($pal, true);
        imagealphablending($pal, false);
        imagecopy($pal, $src, 0, 0, 0, 0, $w, $h);

        // false => NO dithering; limits to $k colors
        imagetruecolortopalette($pal, false, $k);

        // Extract the palette (GD stores it in indexed colors)
        $palette = [];
        $numColors = imagecolorstotal($pal);
        for ($i = 0; $i < $numColors; $i++) {
            $c = imagecolorsforindex($pal, $i);
            // Normalize alpha: if mostly opaque, treat as opaque
            $palette[] = sprintf("#%02x%02x%02x", $c['red'], $c['green'], $c['blue']);
        }

        // Remap every pixel to the EXACT palette color (eliminates any semi-transparency & stray shades)
        $final = imagecreatetruecolor($w, $h);
        imagesavealpha($final, true);
        imagealphablending($final, false);

        // Build quick lookup of palette colors as RGB ints
        $palRgb = [];
        foreach ($palette as $hex) {
            $palRgb[] = [
                'hex' => $hex,
                'r'   => hexdec(substr($hex, 1, 2)),
                'g'   => hexdec(substr($hex, 3, 2)),
                'b'   => hexdec(substr($hex, 5, 2)),
            ];
        }

        // Map function: nearest palette color (L2)
        $mapNearest = function ($r, $g, $b) use ($palRgb) {
            $best = 0; $bestD = PHP_INT_MAX;
            foreach ($palRgb as $idx => $pc) {
                $dr = $r - $pc['r']; $dg = $g - $pc['g']; $db = $b - $pc['b'];
                $d = $dr*$dr + $dg*$dg + $db*$db;
                if ($d < $bestD) { $bestD = $d; $best = $idx; }
            }
            return $palRgb[$best];
        };

        // Paint
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8)  & 0xFF;
                $b = ($rgba)       & 0xFF;

                $pc = $mapNearest($r, $g, $b);
                $col = imagecolorallocatealpha($final, $pc['r'], $pc['g'], $pc['b'], 0);
                imagesetpixel($final, $x, $y, $col);
            }
        }

        // Verify unique colors (should be <= $k)
        $seen = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($final, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8)  & 0xFF;
                $b = ($rgb)       & 0xFF;
                $seen[sprintf("#%02x%02x%02x", $r, $g, $b)] = true;
            }
        }
        $finalColors = array_keys($seen);
        if (count($finalColors) > $k) {
            // Shouldn’t happen, but guard rails:
            $finalColors = array_slice($finalColors, 0, $k);
        }

        // Save PNG
        if (!$destPath) {
            $dir = dirname($srcPath);
            $base = pathinfo($srcPath, PATHINFO_FILENAME);
            $destPath = $dir . '/' . $base . "_k{$k}.png";
        }
        imagepng($final, $destPath);

        imagedestroy($src);
        imagedestroy($pal);
        imagedestroy($final);

        return ['ok'=>true, 'out'=>$destPath, 'colors'=>$finalColors, 'err'=>''];
    }
}
