<?php

namespace App\Support\Shopee;

use App\Models\Category;

/**
 * Membersihkan nama listing Shopee, mis.
 * "[ DECANT / SHARE ] Zataru Signature All Variant | Size 0,5mL, 1mL" menjadi "Zataru Signature".
 */
class ProductNameParser
{
    // Listing "EXTRA DUS / BUBBLE WRAP" bukan produk, melainkan opsi checkout
    public const PACKAGING = 'kemasan';

    private const CONCENTRATIONS = [
        'Extrait de Parfum' => '/\bExtrait\s+de\s+Parfume?\b/i',
        'Eau de Parfum' => '/\bEau\s+de\s+Parfum\b|\bEDP\b/i',
        'Eau de Toilette' => '/\bEau\s+de\s+Toilette\b|\bEDT\b/i',
    ];

    private const NOISE = [
        '/\ball\s+variants?\b/i',
        '/\bvarian\s+lengkap\b/i',
        '/\bready\s+stock\b/i',
        '/\bstok\s+terbatas\b/i',
        '/\(\s*wajib\s+baca\s+deskripsi\s*\)/i',
        '/\(\s*new\s+formulation\s*\)/i',
        '/\bminiature\s+collection\b/i',
        '/-?\s*\bPART\s*\d+\b/i',
    ];

    public function parse(string $original): ParsedName
    {
        $category = $this->category($original);

        $name = preg_replace('/\[[^\]]*\]/u', ' ', $original);
        // "| Size 0,5mL..." dan "| Free Tester 3,5ml" dibuang
        $name = explode('|', $name)[0];
        $name = preg_replace('/-?\s*Angelia\s+Perfume/i', ' ', $name);

        $concentration = null;
        $found = [];
        foreach (self::CONCENTRATIONS as $label => $pattern) {
            if (preg_match($pattern, $name)) {
                $found[] = $label;
                $name = preg_replace($pattern, ' ', $name);
            }
        }
        // Listing yang mencampur beberapa konsentrasi tidak diberi label
        if (count($found) === 1) {
            $concentration = $found[0];
        }

        $packagingNote = null;
        if (preg_match('/\bold\s+packaging\b/i', $name)) {
            $packagingNote = 'Kemasan lama';
            $name = preg_replace('/\bold\s+packaging\b/i', ' ', $name);
        }

        foreach (self::NOISE as $pattern) {
            $name = preg_replace($pattern, ' ', $name);
        }

        $sizes = [];
        if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*ml\b/i', $name, $m)) {
            $sizes = array_map(fn ($v) => (float) str_replace(',', '.', $v), $m[1]);
        }
        // "50ML & 100ML", "15 ml", "Near Exp 50ML" -> ukuran dibuang dari nama
        $name = preg_replace('/(\s*&?\s*\d+(?:[.,]\d+)?\s*ml\b)+/i', ' ', $name);

        $name = preg_replace('/\s+/u', ' ', $name);
        $name = trim($name, " \t-&,/");
        $name = preg_replace('/\s*&\s*$/', '', $name);

        return new ParsedName($category, $name, $concentration, $sizes, $packagingNote);
    }

    public function category(string $original): string
    {
        return match (true) {
            (bool) preg_match('/\bGWP\b|\bFREEBIES\b|\bFREE\s+RANDOM\b/i', $original) => Category::HADIAH,
            (bool) preg_match('/^\s*EXTRA\s+DUS/i', $original) => self::PACKAGING,
            (bool) preg_match('/^\s*\[\s*DECANT/i', $original) => Category::DECANT,
            (bool) preg_match('/FULL\s+SIZE\s*\]/i', $original) => Category::FULL_SIZE,
            default => Category::AKSESORI,
        };
    }
}
