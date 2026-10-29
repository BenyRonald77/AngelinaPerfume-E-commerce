<?php

namespace Tests\Unit\Shopee;

use App\Models\Category;
use App\Support\Shopee\VariationParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VariationParserTest extends TestCase
{
    public static function decantSizes(): array
    {
        return [
            ['0,5mL (vial oles)', 0.5],
            ['0,5 mL (vial)', 0.5],
            ['1mL (vial)', 1.0],
            ['1 mL (vial)', 1.0],
            ['2mL (spray)', 2.0],
            ['2 mL (spray)', 2.0],
            ['10mL (spray)', 10.0],
        ];
    }

    #[DataProvider('decantSizes')]
    public function test_decant_sizes_are_normalised(string $ukuran, float $ml): void
    {
        $v = (new VariationParser)->parse('Black Onyx', $ukuran, Category::DECANT);

        $this->assertSame('Black Onyx', $v->aroma);
        $this->assertSame($ml, $v->ml);
        $this->assertNull($v->note);
    }

    public function test_aroma_names_with_commas_are_kept(): void
    {
        $v = (new VariationParser)->parse('Xoxo, Rosy', '50 ML', Category::FULL_SIZE);

        $this->assertSame('Xoxo, Rosy', $v->aroma);
        $this->assertSame(50.0, $v->ml);
    }

    public function test_full_size_without_aroma_uses_perfume_name(): void
    {
        $v = (new VariationParser)->parse('', 'Full Size 100ML', Category::FULL_SIZE, 'Penthouse');

        $this->assertSame('Penthouse', $v->aroma);
        $this->assertSame(100.0, $v->ml);
    }

    public function test_travel_size_and_version_notes(): void
    {
        $parser = new VariationParser;

        $this->assertSame(15.0, $parser->parse('', 'Travel Size 15 ML', Category::FULL_SIZE, 'Utopia')->ml);
        $this->assertSame('Versi lama', $parser->parse('', 'Full Size 50ML (Old)', Category::FULL_SIZE, 'X')->note);
        $this->assertSame('Non segel utuh', $parser->parse('', '100ML Non Segel Utuh', Category::FULL_SIZE, 'Zora')->note);
    }

    public function test_new_packaging_in_aroma_column_becomes_a_note(): void
    {
        $v = (new VariationParser)->parse('New packaging', 'Full Size 50ML', Category::FULL_SIZE, 'California Blue');

        $this->assertSame('California Blue', $v->aroma);
        $this->assertSame('Kemasan baru', $v->note);
    }

    public function test_aroma_written_in_size_column(): void
    {
        $parser = new VariationParser;

        $musk = $parser->parse('', 'Musk Aura 15ML', Category::FULL_SIZE);
        $this->assertSame('Musk Aura', $musk->aroma);
        $this->assertSame(15.0, $musk->ml);

        $this->assertSame('Moroccan Vanilla', $parser->parse('', 'Moroccan Vanilla15ML', Category::FULL_SIZE)->aroma);
    }

    public function test_clearance_expiry_takes_size_from_listing_name(): void
    {
        $parser = new VariationParser;

        $scandal = $parser->parse('', 'Scandal (Exp 09/26)', Category::FULL_SIZE, '', [50.0]);
        $this->assertSame('Scandal', $scandal->aroma);
        $this->assertSame('Exp 09/26', $scandal->note);
        $this->assertSame(50.0, $scandal->ml);

        $this->assertSame('Exp 10/25', $parser->parse('', 'Greatest Toba(10/25)', Category::FULL_SIZE, '', [50.0])->note);
    }

    public function test_accessories_keep_their_option_without_size(): void
    {
        $v = (new VariationParser)->parse('', 'Storage Vial Oles', Category::AKSESORI);

        $this->assertSame('Storage Vial Oles', $v->aroma);
        $this->assertNull($v->ml);
    }

    public function test_unknown_sizes_are_rejected(): void
    {
        $this->assertNull((new VariationParser)->parse('Aroma', '7mL (spray)', Category::DECANT));
    }
}
