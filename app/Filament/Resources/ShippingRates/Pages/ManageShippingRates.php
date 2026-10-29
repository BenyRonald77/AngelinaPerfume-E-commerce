<?php

namespace App\Filament\Resources\ShippingRates\Pages;

use App\Filament\Resources\ShippingRates\ShippingRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageShippingRates extends ManageRecords
{
    protected static string $resource = ShippingRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
