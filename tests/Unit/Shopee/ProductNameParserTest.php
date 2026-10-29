<?php

namespace Tests\Unit\Shopee;

use App\Models\Category;
use App\Support\Shopee\ProductNameParser;
use PHPUnit\Framework\TestCase;

class ProductNameParserTest extends TestCase
{
    public function test_decant_listing_name_is_cleaned(): void
    {
        $p = (new ProductNameParser)->parse('[ DECANT / SHARE ] Zataru Signature All Variant | Size 0,5mL, 1mL, 2mL, 3mL, 5mL');

        $this->assertSame(Category::DECANT, $p->category);
        $this->assertSame('Zataru Signature', $p->name);
    }

    public function test_full_size_name_concentration_and_sizes(): void
    {
        $p = (new ProductNameParser)->parse('[FULL SIZE] MYKONOS Aphrodite Eau de Parfum 50ML & 100ML | Free Tester 3,5ml - Angelia Perfume');

        $this->assertSame(Category::FULL_SIZE, $p->category);
        $this->assertSame('MYKONOS Aphrodite', $p->name);
        $this->assertSame('Eau de Parfum', $p->concentration);
        $this->assertSame([50.0, 100.0], $p->sizesMl);
    }

    public function test_clearance_listing(): void
    {
        $p = (new ProductNameParser)->parse('[CLEARANCE FULL SIZE] CRUSITA Near Exp 50ML Stok Terbatas (WAJIB BACA DESKRIPSI)- Angelia Perfume');

        $this->assertSame(Category::FULL_SIZE, $p->category);
        $this->assertSame('CRUSITA Near Exp', $p->name);
        $this->assertSame([50.0], $p->sizesMl);
    }

    public function test_old_packaging_becomes_a_note(): void
    {
        $p = (new ProductNameParser)->parse('[FULL SIZE] MYKONOS Invade Old Packaging Extrait De Parfum 50ML - Angelia Perfume');

        $this->assertSame('MYKONOS Invade', $p->name);
        $this->assertSame('Kemasan lama', $p->packagingNote);
        $this->assertSame('Extrait de Parfum', $p->concentration);
    }

    public function test_mixed_concentrations_get_no_label(): void
    {
        $p = (new ProductNameParser)->parse('[ DECANT/SHARE ] PARFUM MINISO EDT EDP | Size 1mL, 2mL, 5mL');

        $this->assertNull($p->concentration);
    }

    public function test_categories_for_special_listings(): void
    {
        $parser = new ProductNameParser;

        $this->assertSame(Category::HADIAH, $parser->category('[ GWP ] FREEBIES Random Official Tester | Minimum Purchase 400k'));
        $this->assertSame(Category::HADIAH, $parser->category('[ WAJIB CLAIM ] GWP Freebies Mystery Gift for Minimum purchase 100K - Angelia Perfume'));
        $this->assertSame(ProductNameParser::PACKAGING, $parser->category('EXTRA DUS / BUBBLE WRAP (Optional)'));
        $this->assertSame(Category::AKSESORI, $parser->category('Atomizer 5ml Botol Parfum Travel Size - Angelia Perfume'));
    }
}
