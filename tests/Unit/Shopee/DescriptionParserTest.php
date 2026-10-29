<?php

namespace Tests\Unit\Shopee;

use App\Support\Shopee\DescriptionParser;
use PHPUnit\Framework\TestCase;

class DescriptionParserTest extends TestCase
{
    private const FULL_SIZE = <<<'TXT'
        MYKONOS Aphrodite Eau de Parfum 50ML & 100ML

        Warm, woody & aromatic Myrrh hypnotises at first spray.

        Aphrodite Notes
        Top- Jasmine, Burnt Cinnamon, Myrrh
        Middle - Cedarwood, tonka bean, sandalwood
        Bottom- White musk, tobacco, Vanilla

        ***

        ✨Free Tester Minimum Purchase 250k✨
        [Ketentuan Pengiriman]
        TXT;

    public function test_store_rules_are_detected(): void
    {
        $parser = new DescriptionParser;

        $this->assertTrue($parser->isStoreBoilerplate("Welcome to Angelia Perfume!\nOperasional 08.00-15.00"));
        $this->assertTrue($parser->isStoreBoilerplate("Mengapa kami?\n✅Menggunakan suntikan berbeda"));
        $this->assertFalse($parser->isStoreBoilerplate(self::FULL_SIZE));
    }

    public function test_shipping_rules_are_cut_from_display_text(): void
    {
        $text = (new DescriptionParser)->displayText(self::FULL_SIZE);

        $this->assertStringContainsString('Warm, woody', $text);
        $this->assertStringNotContainsString('Ketentuan', $text);
        $this->assertStringNotContainsString('Free Tester', $text);
    }

    public function test_notes_are_read_from_description(): void
    {
        $notes = (new DescriptionParser)->notes(self::FULL_SIZE);

        $this->assertSame('Jasmine, Burnt Cinnamon, Myrrh', $notes['top']);
        $this->assertSame('Cedarwood, Tonka Bean, Sandalwood', $notes['middle']);
        $this->assertSame('White Musk, Tobacco, Vanilla', $notes['base']);
        $this->assertNull($notes['general']);
    }

    public function test_notes_without_layers_go_to_general(): void
    {
        $notes = (new DescriptionParser)->notes("Accord : Citrus, Aromatic\nNotes: grapefruit, Cedar, tonka bean");

        $this->assertNull($notes['top']);
        $this->assertSame('Grapefruit, Cedar, Tonka Bean', $notes['general']);
    }
}
