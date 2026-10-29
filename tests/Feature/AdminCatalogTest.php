<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StockReason;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAroma;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        Category::ensureDefaults();
        $this->admin = User::factory()->create();
        $this->admin->role = Role::Admin;
        $this->admin->save();

        $product = Product::create([
            'number' => 12, 'name' => 'Zataru Signature', 'slug' => 'zataru-signature',
            'category_id' => Category::where('slug', Category::DECANT)->value('id'),
        ]);
        $aroma = ProductAroma::create(['product_id' => $product->id, 'number' => 3, 'name' => 'Topaz']);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id, 'product_aroma_id' => $aroma->id,
            'size_id' => Size::where('ml', 2)->value('id'), 'sku' => 'AP-0012-03-2ML',
            'price' => 20000, 'weight_grams' => 60, 'stock' => 4,
        ]);
    }

    public function test_admin_catalog_pages_open(): void
    {
        $this->actingAs($this->admin);

        foreach (['/admin/products', '/admin/products/create', "/admin/products/{$this->variant->product->slug}/edit",
            '/admin/stok', '/admin/riwayat-stok', '/admin/merek', '/admin/jenis', '/admin/hadiah'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_stock_changes_are_logged_and_never_negative(): void
    {
        $this->variant->adjustStock(-3, StockReason::Penyesuaian, $this->admin, 'Botol pecah');

        $this->assertSame(1, $this->variant->fresh()->stock);
        $movement = $this->variant->stockMovements()->sole();
        $this->assertSame(-3, $movement->change);
        $this->assertSame(1, $movement->stock_after);
        $this->assertSame($this->admin->id, $movement->user_id);

        $this->expectException(InvalidArgumentException::class);
        $this->variant->adjustStock(-2, StockReason::Penyesuaian, $this->admin);
    }

    public function test_sku_code_follows_format(): void
    {
        $product = $this->variant->product;
        $aroma = $this->variant->aroma;

        $this->assertSame('AP-0012-03-0.5ML', ProductVariant::generateSku($product, $aroma, Size::where('ml', 0.5)->first()));
        $this->assertSame('AP-0012-03-100ML', ProductVariant::generateSku($product, $aroma, Size::where('ml', 100)->first()));
        $this->assertSame('AP-0012-03', ProductVariant::generateSku($product, $aroma, null));
        // Sudah dipakai: diberi akhiran
        $this->assertSame('AP-0012-03-2ML-2', ProductVariant::generateSku($product, $aroma, Size::where('ml', 2)->first()));
    }
}
