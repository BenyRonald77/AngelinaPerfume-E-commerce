<?php

namespace App\Filament\Resources\GiftRules\Pages;

use App\Filament\Resources\GiftRules\GiftRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGiftRules extends ManageRecords
{
    protected static string $resource = GiftRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
