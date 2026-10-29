<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Pengaturan toko yang dipakai checkout (tabel settings) */
class StoreSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengaturan toko';

    protected static ?string $title = 'Pengaturan toko';

    protected static ?string $slug = 'pengaturan';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(collect(Setting::DEFAULTS)->mapWithKeys(fn ($default, $key) => [$key => Setting::int($key)])->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Checkout')
                    ->columns(2)
                    ->schema([
                        TextInput::make('packaging_fee')
                            ->label('Biaya extra dus dan bubble wrap')
                            ->prefix('Rp')
                            ->numeric()->integer()->minValue(0)->required(),
                        TextInput::make('packaging_weight_grams')
                            ->label('Berat tambahan extra dus')
                            ->suffix('gram')
                            ->numeric()->integer()->minValue(0)->required(),
                        TextInput::make('payment_window_hours')
                            ->label('Batas waktu bayar')
                            ->suffix('jam')
                            ->numeric()->integer()->minValue(1)->maxValue(72)->required()
                            ->helperText('Pesanan yang belum dibayar lewat batas ini batal otomatis dan stok kembali.'),
                        TextInput::make('low_stock_threshold')
                            ->label('Batas stok menipis')
                            ->suffix('botol')
                            ->numeric()->integer()->minValue(0)->required()
                            ->helperText('Halaman produk menampilkan "Sisa sedikit" di bawah angka ini.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Simpan')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::put($key, (string) (int) $value);
        }

        Notification::make()->title('Pengaturan disimpan.')->success()->send();
    }
}
