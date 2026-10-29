<?php

namespace App\Support\Images;

use RuntimeException;

/**
 * Mengubah foto Shopee (sekitar 0,9 MB per file) menjadi WebP 800 px dan 1600 px.
 * Memakai GD bawaan PHP supaya tidak butuh Imagick di shared hosting.
 */
class WebpConverter
{
    public const WIDTHS = [800, 1600];

    public function __construct(private int $quality = 80) {}

    /**
     * Menulis <destBase>-800.webp dan <destBase>-1600.webp. File yang sudah ada
     * dan lebih baru dari sumbernya dilewati, jadi impor bisa dilanjutkan bila terhenti.
     *
     * @return array{width: int, height: int, converted: bool} ukuran versi 800 px
     */
    public function convert(string $source, string $destBase): array
    {
        [$origWidth, $origHeight, $type] = getimagesize($source) ?: throw new RuntimeException("Bukan gambar: {$source}");
        [$width, $height] = $this->fit($origWidth, $origHeight, self::WIDTHS[0]);

        $pending = array_filter(self::WIDTHS, function (int $w) use ($source, $destBase) {
            $dest = "{$destBase}-{$w}.webp";

            return ! is_file($dest) || filemtime($dest) < filemtime($source);
        });

        if ($pending === []) {
            return ['width' => $width, 'height' => $height, 'converted' => false];
        }

        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            default => throw new RuntimeException("Format gambar tidak didukung: {$source}"),
        };

        if (! is_dir(dirname($destBase))) {
            mkdir(dirname($destBase), 0755, true);
        }

        foreach ($pending as $target) {
            [$w, $h] = $this->fit($origWidth, $origHeight, $target);
            $resized = ($w === $origWidth) ? $image : imagescale($image, $w, $h, IMG_BICUBIC);
            imagepalettetotruecolor($resized);
            imagewebp($resized, "{$destBase}-{$target}.webp", $this->quality);
            if ($resized !== $image) {
                imagedestroy($resized);
            }
        }

        imagedestroy($image);

        return ['width' => $width, 'height' => $height, 'converted' => true];
    }

    /** Tidak memperbesar foto yang lebih kecil dari target */
    private function fit(int $width, int $height, int $target): array
    {
        if ($width <= $target) {
            return [$width, $height];
        }

        return [$target, (int) round($height * $target / $width)];
    }
}
