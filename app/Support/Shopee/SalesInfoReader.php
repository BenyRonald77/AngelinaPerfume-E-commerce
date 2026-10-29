<?php

namespace App\Support\Shopee;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Membaca batas beli per variasi dari ekspor "Informasi Penjualan" Seller Centre.
 * File xlsx Shopee dibaca langsung dari XML-nya karena pustaka xlsx biasa gagal membukanya.
 */
class SalesInfoReader
{
    /**
     * @return array<string, int> kode variasi => maks. jumlah pembelian (hanya yang diisi)
     */
    public function maxPurchase(string $xlsxPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($xlsxPath) !== true) {
            throw new RuntimeException("Tidak bisa membuka {$xlsxPath}");
        }

        $strings = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach ((new SimpleXMLElement($xml))->si as $si) {
                $strings[] = $this->text($si);
            }
        }

        $sheet = new SimpleXMLElement($zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();

        $variationCol = null;
        $maxCol = null;
        $result = [];

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $col = preg_replace('/\d+/', '', (string) $c['r']);
                $value = match ((string) $c['t']) {
                    's' => $strings[(int) $c->v] ?? '',
                    'inlineStr' => $this->text($c->is),
                    default => (string) $c->v,
                };
                $cells[$col] = trim($value);
            }

            if ($variationCol === null) {
                $variationCol = array_search('Kode Variasi', $cells, true) ?: null;
                $maxCol = array_search('Maks. Jumlah Pembelian', $cells, true) ?: null;

                continue;
            }

            $variation = $cells[$variationCol] ?? '';
            $max = (int) ($cells[$maxCol] ?? 0);
            if (ctype_digit($variation) && $max > 0) {
                $result[$variation] = $max;
            }
        }

        if ($variationCol === null || $maxCol === null) {
            throw new RuntimeException('Kolom "Kode Variasi" atau "Maks. Jumlah Pembelian" tidak ditemukan.');
        }

        return $result;
    }

    private function text(SimpleXMLElement $node): string
    {
        if (isset($node->t)) {
            return (string) $node->t;
        }

        $text = '';
        foreach ($node->r as $run) {
            $text .= (string) $run->t;
        }

        return $text;
    }
}
