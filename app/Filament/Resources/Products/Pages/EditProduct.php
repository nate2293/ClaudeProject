<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Product $record
 */
class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('View in shop')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn () => route('products.show', $this->record), true)
                ->visible(fn () => $this->record->is_active),
            DeleteAction::make()
                ->modalDescription('Past orders keep their details, but the product page disappears. To hide it for now, switch off "On sale" instead.'),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->syncStockLevels();
    }
}
