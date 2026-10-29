<?php

namespace App\Support\Shopee;

class DescriptionParser
{
    /**
     * Deskripsi decant di Shopee umumnya hanya aturan toko yang diulang;
     * teks itu dipindah ke halaman Bantuan, tidak tampil per produk.
     */
    public function isStoreBoilerplate(string $text): bool
    {
        $text = trim($text);

        return $text === ''
            || str_contains($text, 'MEMBELI=SETUJU')
            || preg_match('/^(welcome to angelia perfume|harap membaca detail produk|mengapa kami)/i', $text) === 1;
    }

    /** Bagian cerita parfum saja; aturan pengiriman dan promo Shopee di bawahnya dibuang */
    public function displayText(string $text): ?string
    {
        if ($this->isStoreBoilerplate($text)) {
            return null;
        }

        $kept = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '***'
                || preg_match('/^\[?\s*ketentuan/i', $trimmed)
                || preg_match('/free\s+tester\s+minimum/i', $trimmed)
                || preg_match('/^(welcome to angelia perfume|mengapa kami|klaim garansi)/i', $trimmed)) {
                break;
            }
            $kept[] = rtrim($line);
        }

        $result = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $kept)));

        return $result === '' ? null : $result;
    }

    /**
     * Notes dari baris seperti "Top- Jasmine, Myrrh" atau "Base Notes : Musk".
     *
     * Tanpa lapisan ("Notes: grapefruit, Cedar") masuk ke general.
     *
     * @return array{top: ?string, middle: ?string, base: ?string, general: ?string}
     */
    public function notes(string $text): array
    {
        $notes = ['top' => null, 'middle' => null, 'base' => null, 'general' => null];
        $layers = ['top' => 'top', 'middle' => 'middle', 'heart' => 'middle', 'base' => 'base', 'bottom' => 'base'];

        foreach (preg_split('/\R/u', $text) as $line) {
            if (preg_match('/^\s*[-•*]?\s*(top|middle|heart|base|bottom)(?:\s+notes?)?\s*[:\-–]\s*(.+)$/iu', $line, $m)) {
                $layer = $layers[strtolower($m[1])];
                $notes[$layer] ??= $this->cleanList($m[2]);
            } elseif (preg_match('/^\s*(?:main\s+)?notes?\s*:\s*(.+)$/iu', $line, $m)) {
                $notes['general'] ??= $this->cleanList($m[1]);
            }
        }

        // Notes umum hanya dipakai bila tidak ada lapisan sama sekali
        if ($notes['top'] || $notes['middle'] || $notes['base']) {
            $notes['general'] = null;
        }

        return $notes;
    }

    private function cleanList(string $value): ?string
    {
        $items = array_filter(array_map(
            fn ($item) => ucwords(trim($item, " \t.")),
            preg_split('/\s*,\s*/u', trim($value))
        ));

        return $items ? implode(', ', $items) : null;
    }
}
