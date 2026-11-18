<?php

namespace Database\Seeders\Support;

use App\Support\FileUpload;

/**
 * Draws simple illustrative photos for demo data with GD: building
 * exteriors against green hills and a few room interiors. Every image is
 * stamped "Demo image" so nobody mistakes it for a real listing photo.
 */
class DemoImages
{
    private const W = 1200;
    private const H = 760;

    /** Images are written into public/ (e.g. public/image/properties/), like real uploads. */
    public function __construct()
    {
    }

    /** Building exterior. $style: apartments | villa | commercial */
    public function exterior(string $path, int $seed, string $style = 'apartments', int $floors = 4): string
    {
        mt_srand($seed);
        $img = imagecreatetruecolor(self::W, self::H);

        // Sky gradient.
        $this->gradient($img, 0, 0, self::W, 460, [96, 160, 222], [205, 228, 247]);
        // Clouds.
        for ($i = 0; $i < 5; $i++) {
            $cx = mt_rand(60, self::W - 60); $cy = mt_rand(40, 200);
            $white = imagecolorallocatealpha($img, 255, 255, 255, 35);
            for ($k = 0; $k < 4; $k++) {
                imagefilledellipse($img, $cx + $k * 38 - 60, $cy + mt_rand(-8, 8), mt_rand(90, 140), mt_rand(40, 60), $white);
            }
        }
        // Rolling hills (land of a thousand hills).
        $this->hill($img, 300, [92, 150, 92], 0.9);
        $this->hill($img, 360, [70, 128, 70], 1.4);
        // Ground.
        $this->gradient($img, 0, 560, self::W, self::H, [104, 158, 74], [78, 128, 56]);
        // Driveway.
        $road = imagecolorallocate($img, 150, 150, 156);
        imagefilledpolygon($img, [470, self::H, 730, self::H, 650, 600, 550, 600], $road);

        $wallPalettes = [[238, 232, 222], [226, 214, 196], [214, 224, 232], [242, 236, 228], [222, 206, 186]];
        $accentPalettes = [[120, 128, 140], [176, 96, 60], [64, 88, 120], [150, 110, 80], [90, 110, 90]];
        $wall = $wallPalettes[mt_rand(0, count($wallPalettes) - 1)];
        $accent = $accentPalettes[mt_rand(0, count($accentPalettes) - 1)];

        if ($style === 'villa') {
            $this->villa($img, 300, 360, 600, 240, $wall, $accent);
        } else {
            $floors = max(2, min(7, $floors));
            $fh = (int) min(70, 300 / $floors);
            $bw = $style === 'commercial' ? 700 : 560;
            $x = (int) ((self::W - $bw) / 2) + mt_rand(-80, 80);
            $top = 600 - $floors * $fh;
            $this->block($img, $x, $top, $bw, $floors * $fh, $floors, $wall, $accent, $style === 'commercial');
            // A second, set-back wing for larger apartments.
            if ($style === 'apartments' && $floors >= 3) {
                $this->block($img, $x + $bw - 40, $top + $fh, 240, ($floors - 1) * $fh, $floors - 1, $this->shade($wall, -14), $accent, false);
            }
        }

        // Trees and shrubs.
        for ($i = 0; $i < 7; $i++) {
            $tx = [mt_rand(30, 260), mt_rand(940, 1170)][mt_rand(0, 1)];
            $this->tree($img, $tx, mt_rand(560, 640), mt_rand(14, 25) * 2);
        }
        for ($i = 0; $i < 14; $i++) {
            $g = imagecolorallocate($img, 52 + mt_rand(0, 30), 110 + mt_rand(0, 40), 50);
            imagefilledellipse($img, mt_rand(200, 1000), mt_rand(600, 625), mt_rand(36, 60), mt_rand(22, 30), $g);
        }

        return $this->save($img, $path);
    }

    /** Room interior. $room: living | bedroom | kitchen | bathroom */
    public function interior(string $path, int $seed, string $room = 'living'): string
    {
        mt_srand($seed);
        $img = imagecreatetruecolor(self::W, self::H);
        $walls = [[238, 232, 220], [228, 224, 214], [232, 236, 238], [242, 234, 224]];
        $wall = $walls[mt_rand(0, 3)];

        $this->gradient($img, 0, 0, self::W, 520, $this->shade($wall, 8), $this->shade($wall, -10));
        // Floor.
        $floor = $room === 'bathroom' ? [214, 216, 220] : ($room === 'kitchen' ? [196, 190, 182] : [176, 132, 92]);
        $this->gradient($img, 0, 520, self::W, self::H, $floor, $this->shade($floor, -30));
        $line = imagecolorallocatealpha($img, 0, 0, 0, 105);
        for ($x = -400; $x < self::W; $x += $room === 'bathroom' ? 80 : 120) {
            imageline($img, $x + 400, 520, $x - 100, self::H, $line);
        }
        if ($room === 'bathroom') {
            for ($y = 120; $y < 520; $y += 60) { imageline($img, 0, $y, self::W, $y, $line); }
            for ($x = 0; $x < self::W; $x += 60) { imageline($img, $x, 120, $x, 520, $line); }
        }

        // Window with hills.
        $wx = mt_rand(140, 700); $ww = 340; $wy = 110; $wh = 260;
        // The view is drawn on its own canvas so the hills stay inside the frame.
        $view = imagecreatetruecolor($ww, $wh);
        $this->gradient($view, 0, 0, $ww, $wh, [120, 178, 228], [214, 234, 248]);
        imagefilledellipse($view, 110, $wh + 20, 380, 180, imagecolorallocate($view, 84, 142, 84));
        imagefilledellipse($view, 280, $wh + 40, 320, 170, imagecolorallocate($view, 64, 120, 64));
        imagecopy($img, $view, $wx, $wy, 0, 0, $ww, $wh);
        imagedestroy($view);
        $frame = imagecolorallocate($img, 250, 250, 250);
        imagesetthickness($img, 10);
        imagerectangle($img, $wx, $wy, $wx + $ww, $wy + $wh, $frame);
        imageline($img, $wx + intdiv($ww, 2), $wy, $wx + intdiv($ww, 2), $wy + $wh, $frame);
        imagesetthickness($img, 1);

        match ($room) {
            'bedroom' => $this->bed($img),
            'kitchen' => $this->kitchen($img),
            'bathroom' => $this->bathroom($img),
            default => $this->sofa($img),
        };

        // Plant.
        $pot = imagecolorallocate($img, 190, 120, 80);
        imagefilledrectangle($img, 1040, 560, 1100, 640, $pot);
        for ($i = 0; $i < 7; $i++) {
            imagefilledellipse($img, 1070 + mt_rand(-40, 40), 520 + mt_rand(-60, 30), 50, 90, imagecolorallocate($img, 50, 120 + mt_rand(0, 40), 60));
        }

        return $this->save($img, $path);
    }

    /* ------------------------------------------------------------------ */

    private function block($img, int $x, int $y, int $w, int $h, int $floors, array $wall, array $accent, bool $commercial): void
    {
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 90);
        imagefilledrectangle($img, $x + 12, $y + 12, $x + $w + 12, $y + $h, $shadow);
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, imagecolorallocate($img, ...$wall));
        $acc = imagecolorallocate($img, ...$accent);
        imagefilledrectangle($img, $x - 8, $y - 14, $x + $w + 8, $y, $acc); // roof slab
        $glass = imagecolorallocate($img, 70, 104, 140);
        $glassHi = imagecolorallocate($img, 150, 190, 222);
        $rail = imagecolorallocate($img, 60, 60, 66);
        $fh = (int) ($h / $floors);
        $cols = (int) floor($w / 90);
        for ($f = 0; $f < $floors; $f++) {
            $fy = $y + $f * $fh;
            imageline($img, $x, $fy, $x + $w, $fy, imagecolorallocate($img, ...$this->shade($wall, -25)));
            for ($c = 0; $c < $cols; $c++) {
                $wx = $x + 18 + $c * ($w - 36) / $cols;
                $winW = (int) (($w - 36) / $cols) - 22;
                if ($commercial && $f === $floors - 1) {
                    imagefilledrectangle($img, (int) $wx, $fy + 8, (int) $wx + $winW, $fy + $fh - 4, $glass);
                    continue;
                }
                imagefilledrectangle($img, (int) $wx, $fy + 12, (int) $wx + $winW, $fy + $fh - 14, $glass);
                imagefilledrectangle($img, (int) $wx + 4, $fy + 16, (int) $wx + (int) ($winW / 3), $fy + $fh - 18, $glassHi);
                if (! $commercial && $f < $floors - 1 && $c % 2 === 0) {
                    imagefilledrectangle($img, (int) $wx - 6, $fy + $fh - 18, (int) $wx + $winW + 6, $fy + $fh - 14, $acc);
                    for ($r = (int) $wx - 6; $r < $wx + $winW + 6; $r += 9) {
                        imageline($img, $r, $fy + $fh - 34, $r, $fy + $fh - 18, $rail);
                    }
                    imageline($img, (int) $wx - 6, $fy + $fh - 34, (int) $wx + $winW + 6, $fy + $fh - 34, $rail);
                }
            }
        }
        // Entrance.
        $door = imagecolorallocate($img, 90, 64, 44);
        imagefilledrectangle($img, $x + (int) ($w / 2) - 34, $y + $h - 70, $x + (int) ($w / 2) + 34, $y + $h, $door);
    }

    private function villa($img, int $x, int $y, int $w, int $h, array $wall, array $accent): void
    {
        imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, imagecolorallocate($img, ...$wall));
        $roof = imagecolorallocate($img, ...$accent);
        imagefilledpolygon($img, [$x - 30, $y, $x + $w + 30, $y, $x + $w - 80, $y - 110, $x + 80, $y - 110], $roof);
        $glass = imagecolorallocate($img, 70, 104, 140);
        foreach ([[30, 40, 130, 110], [200, 40, 300, 110], [380, 40, 560, 140]] as [$a, $b, $c, $d]) {
            imagefilledrectangle($img, $x + $a, $y + $b, $x + $c, $y + $d, $glass);
        }
        imagefilledrectangle($img, $x + 230, $y + 140, $x + 290, $y + $h, imagecolorallocate($img, 100, 70, 46));
        // Garden wall and gate.
        $wallc = imagecolorallocate($img, ...$this->shade($wall, -30));
        imagefilledrectangle($img, 60, 590, 1140, 620, $wallc);
        imagefilledrectangle($img, 540, 570, 660, 620, imagecolorallocate($img, 40, 40, 44));
    }

    private function sofa($img): void
    {
        $c = imagecolorallocate($img, 120, 128, 140);
        $d = imagecolorallocate($img, 96, 104, 116);
        imagefilledrectangle($img, 160, 470, 760, 600, $c);
        imagefilledrectangle($img, 160, 420, 760, 480, $d);
        imagefilledrectangle($img, 130, 450, 190, 610, $d);
        imagefilledrectangle($img, 730, 450, 790, 610, $d);
        imagefilledellipse($img, 470, 680, 300, 70, imagecolorallocate($img, 120, 84, 54)); // table
        imagefilledrectangle($img, 850, 330, 980, 600, imagecolorallocate($img, 110, 76, 50)); // shelf
        imagefilledellipse($img, 470, 680, 80, 30, imagecolorallocate($img, 230, 230, 230));
    }

    private function bed($img): void
    {
        imagefilledrectangle($img, 260, 360, 820, 440, imagecolorallocate($img, 120, 84, 54)); // headboard
        imagefilledrectangle($img, 240, 440, 840, 640, imagecolorallocate($img, 245, 245, 245));
        imagefilledrectangle($img, 240, 520, 840, 640, imagecolorallocate($img, 90, 110, 140));
        imagefilledellipse($img, 380, 470, 180, 60, imagecolorallocate($img, 230, 230, 236));
        imagefilledellipse($img, 700, 470, 180, 60, imagecolorallocate($img, 230, 230, 236));
        imagefilledrectangle($img, 880, 520, 980, 640, imagecolorallocate($img, 140, 100, 70));
    }

    private function kitchen($img): void
    {
        $cab = imagecolorallocate($img, 236, 236, 232);
        $line = imagecolorallocate($img, 180, 180, 176);
        imagefilledrectangle($img, 80, 470, 1000, 640, $cab);
        imagefilledrectangle($img, 70, 455, 1010, 475, imagecolorallocate($img, 70, 70, 76)); // counter
        for ($x = 80; $x < 1000; $x += 115) { imageline($img, $x, 480, $x, 640, $line); }
        imagefilledrectangle($img, 80, 70, 520, 200, $cab);
        imagefilledrectangle($img, 1020, 300, 1150, 640, imagecolorallocate($img, 200, 202, 206)); // fridge
        imagefilledrectangle($img, 600, 470, 760, 520, imagecolorallocate($img, 40, 40, 44)); // hob
    }

    private function bathroom($img): void
    {
        imagefilledrectangle($img, 120, 420, 520, 640, imagecolorallocate($img, 250, 250, 252)); // bath
        imagefilledrectangle($img, 700, 470, 900, 560, imagecolorallocate($img, 245, 245, 247)); // basin unit
        imagefilledrectangle($img, 700, 560, 900, 650, imagecolorallocate($img, 140, 104, 72));
        imagefilledrectangle($img, 730, 260, 870, 440, imagecolorallocate($img, 190, 214, 230)); // mirror
    }

    private function tree($img, int $x, int $y, int $r): void
    {
        imagefilledrectangle($img, $x - 5, $y - 10, $x + 5, $y + 40, imagecolorallocate($img, 100, 70, 46));
        for ($i = 0; $i < 5; $i++) {
            imagefilledellipse($img, $x + mt_rand(-intdiv($r, 2), intdiv($r, 2)), $y - mt_rand(10, $r), $r * 2, $r * 2, imagecolorallocate($img, 40 + mt_rand(0, 30), 100 + mt_rand(0, 50), 44));
        }
    }

    private function hill($img, int $base, array $rgb, float $freq): void
    {
        $c = imagecolorallocate($img, ...$rgb);
        $pts = [0, self::H];
        $phase = mt_rand(0, 100) / 10;
        for ($x = 0; $x <= self::W; $x += 20) {
            $pts[] = $x;
            $pts[] = (int) ($base + sin($x / 160 * $freq + $phase) * 40 + sin($x / 70 + $phase) * 12);
        }
        $pts[] = self::W; $pts[] = self::H;
        imagefilledpolygon($img, $pts, $c);
    }

    private function gradient($img, int $x1, int $y1, int $x2, int $y2, array $from, array $to): void
    {
        $h = max(1, $y2 - $y1);
        for ($i = 0; $i <= $h; $i++) {
            $t = $i / $h;
            $c = imagecolorallocate($img, ...array_map(fn ($a, $b) => (int) ($a + ($b - $a) * $t), $from, $to));
            imageline($img, $x1, $y1 + $i, $x2, $y1 + $i, $c);
        }
    }

    private function shade(array $rgb, int $d): array
    {
        return array_map(fn ($v) => max(0, min(255, $v + $d)), $rgb);
    }

    private function save($img, string $path): string
    {
        // Small, honest watermark.
        $label = 'Demo image';
        $bg = imagecolorallocatealpha($img, 0, 0, 0, 70);
        imagefilledrectangle($img, self::W - 120, self::H - 30, self::W - 8, self::H - 8, $bg);
        imagestring($img, 3, self::W - 108, self::H - 26, $label, imagecolorallocate($img, 255, 255, 255));

        ob_start();
        imagejpeg($img, null, 82);
        $bytes = ob_get_clean();
        imagedestroy($img);

        // Same layout as real uploads: the relative path under public/ is what gets saved.
        return FileUpload::put(dirname($path), basename($path), $bytes);
    }
}
