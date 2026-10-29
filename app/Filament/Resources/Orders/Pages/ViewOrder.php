<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Actions\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return "Pesanan {$this->getRecord()->code}";
    }

    protected function getHeaderActions(): array
    {
        return OrderActions::all();
    }
}
