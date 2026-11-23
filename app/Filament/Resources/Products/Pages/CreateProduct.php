<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

/**
 * @property Product $record
 */
class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $this->record->syncStockLevels();
    }

    protected function getRedirectUrl(): string
    {
        // Straight to the edit page, where the Stock tab is.
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
