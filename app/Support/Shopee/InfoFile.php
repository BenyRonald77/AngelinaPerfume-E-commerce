<?php

namespace App\Support\Shopee;

/**
 * Membaca info.txt hasil skrip unduh_foto_shopee.py:
 * kode produk, SKU induk, nama listing (bisa beberapa untuk gabungan PART) dan urutan foto.
 */
final class InfoFile
{
    /**
     * @param  list<array{id: string, parent_sku: ?string, name: string}>  $listings
     * @param  list<string>  $photos  nama foto tanpa ekstensi, sesuai urutan di Shopee
     */
    public function __construct(
        public readonly array $listings,
        public readonly array $photos,
    ) {}

    public static function read(string $path): self
    {
        $listings = [];
        $photos = [];
        $current = null;
        $inPhotos = false;

        foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
            if (preg_match('/^Kode Produk:\s*(\d+)/', $line, $m)) {
                if ($current) {
                    $listings[] = $current;
                }
                $current = ['id' => $m[1], 'parent_sku' => null, 'name' => ''];
                $inPhotos = false;
            } elseif ($current && preg_match('/^SKU Induk:\s*(.*)$/', $line, $m)) {
                $current['parent_sku'] = trim($m[1]) ?: null;
            } elseif ($current && preg_match('/^Nama Produk:\s*(.*)$/', $line, $m)) {
                $current['name'] = trim($m[1]);
            } elseif (trim($line) === 'Foto:') {
                $inPhotos = true;
            } elseif ($inPhotos && preg_match('/^\s+([\w.-]+):\s*https?:/', $line, $m)) {
                $photos[] = $m[1];
            } elseif ($inPhotos && trim($line) !== '') {
                $inPhotos = false;
            }
        }

        if ($current) {
            $listings[] = $current;
        }

        return new self($listings, $photos);
    }
}
