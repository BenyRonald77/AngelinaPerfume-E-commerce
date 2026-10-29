<?php

namespace App\Support\Shopee;

use App\Models\Category;

/**
 * Membaca kolom aroma dan ukuran dari ekspor Shopee.
 *
 * Shopee menulis variasi sebagai "Aroma,Ukuran" dan penulisan ukurannya beragam
 * ("1mL (vial)", "1 mL (vial)", "Full Size 50ML (Old)", "Musk Aura 15ML",
 * "Scandal (Exp 09/26)"). Semua disamakan ke daftar ukuran tetap.
 */
class VariationParser
{
    public const SIZES_ML = [0.5, 1.0, 2.0, 3.0, 5.0, 10.0, 15.0, 37.0, 50.0, 100.0];

    private const NUMBER = '(\d+(?:[.,]\d+)?)';

    /**
     * @param  string  $defaultAroma  nama parfum untuk listing satu aroma (full size)
     * @param  list<float>  $nameSizes  ukuran yang tertulis di nama listing
     * @return ParsedVariation|null null bila tidak dikenali (masuk laporan impor)
     */
    public function parse(string $aroma, string $ukuran, string $category, string $defaultAroma = '', array $nameSizes = []): ?ParsedVariation
    {
        $aroma = $this->squash($aroma);
        $ukuran = $this->squash($ukuran);
        $note = null;

        if ($category === Category::HADIAH) {
            return new ParsedVariation('Hadiah', null);
        }

        // Aksesori tidak memakai daftar ukuran parfum; pilihannya ditulis di kolom ukuran
        if ($category === Category::AKSESORI) {
            $label = trim($aroma.' '.$ukuran);

            return $label === '' ? null : new ParsedVariation($label, null);
        }

        // "New packaging" di kolom aroma adalah keterangan kemasan, bukan aroma
        if (preg_match('/^new\s+packaging$/i', $aroma)) {
            $aroma = '';
            $note = 'Kemasan baru';
        }

        $n = self::NUMBER;
        $ml = null;

        if (preg_match("/^{$n}\s*ml\b(.*)$/i", $ukuran, $m)) {
            // "2mL (spray)", "0,5 mL (vial)", "100ML Non Segel Utuh"
            $ml = $this->number($m[1]);
            $note = $this->restNote($m[2]) ?? $note;
        } elseif (preg_match("/^(?:full|travel)\s*size\s*{$n}\s*ml\b(.*)$/i", $ukuran, $m)) {
            // "Full Size 50ML (Old)", "Travel Size 15 ML"
            $ml = $this->number($m[1]);
            $note = $this->restNote($m[2]) ?? $note;
        } elseif (preg_match("/^(.+?)\s*{$n}\s*ml$/i", $ukuran, $m)) {
            // "Musk Aura 15ML", "Once Upon Don 50ML": aroma tertulis di kolom ukuran
            $aroma = $aroma !== '' ? $aroma : $this->squash($m[1]);
            $ml = $this->number($m[2]);
        } elseif (preg_match('/^(.+?)\s*\(\s*(?:exp\s*)?(\d{1,2}\/\d{2})\s*\)$/i', $ukuran, $m)) {
            // Clearance near-exp: "Scandal (Exp 09/26)", "Greatest Toba(10/25)"
            $aroma = $this->squash($m[1]);
            $note = 'Exp '.$m[2];
            $ml = count($nameSizes) === 1 ? $nameSizes[0] : null;
        } elseif (preg_match('/^(.+?)\s+(\d{2}\/\d{2})$/', $ukuran, $m)) {
            // "Menu Daily Dew 08/27": angka ditulis apa adanya seperti di Shopee
            $aroma = $this->squash($m[1]);
            $note = $m[2];
            $ml = count($nameSizes) === 1 ? $nameSizes[0] : null;
        } elseif ($aroma === '' && $ukuran !== '') {
            // Kolom ukuran hanya berisi nama aroma ("Menu The Mistress")
            $aroma = $ukuran;
            $ml = count($nameSizes) === 1 ? $nameSizes[0] : null;
        } else {
            return null;
        }

        if ($ml !== null && ! in_array($ml, self::SIZES_ML, true)) {
            return null;
        }

        $aroma = $aroma !== '' ? $aroma : $this->squash($defaultAroma);

        return $aroma === '' ? null : new ParsedVariation($aroma, $ml, $note);
    }

    private function number(string $value): float
    {
        return (float) str_replace(',', '.', $value);
    }

    /** Sisa teks setelah ukuran: "(spray)" diabaikan, "(Old)" menjadi "Versi lama" */
    private function restNote(string $rest): ?string
    {
        $rest = trim($rest, " \t,");

        if ($rest === '' || preg_match('/^\((spray|vial|vial\s+oles)\)$/i', $rest)) {
            return null;
        }

        return match (strtolower(trim($rest, '()'))) {
            'old' => 'Versi lama',
            'new' => 'Versi baru',
            'new packaging' => 'Kemasan baru',
            default => ucfirst(strtolower(trim($rest, '()'))),
        };
    }

    private function squash(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
}
