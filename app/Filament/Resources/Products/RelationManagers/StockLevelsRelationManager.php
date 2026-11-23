<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\StockLevel;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

/**
 * Stock per variant. Variants come from the product's colours and sizes, so
 * they're never added or removed here; only quantities change.
 */
class StockLevelsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockLevels';

    protected static ?string $title = 'Stock';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('colour')
            ->defaultSort('id')
            ->paginated(false)
            ->description('"Reserved" units are held by open checkouts or payments in progress. Edit "On hand" after a stock count; use "Receive" for deliveries.')
            ->columns([
                TextColumn::make('colour'),
                TextColumn::make('size'),
                TextInputColumn::make('on_hand')->label('On hand')
                    ->type('number')->rules(['required', 'integer', 'min:0', 'max:100000'])->width('8rem'),
                TextColumn::make('reserved'),
                TextColumn::make('available')->state(fn (StockLevel $record) => $record->available())
                    ->badge()->color(fn (int $state) => $state === 0 ? 'danger' : ($state <= (int) config('shop.stock.low_stock_threshold') ? 'warning' : 'success')),
            ])
            ->recordActions([
                Action::make('receive')
                    ->label('Receive')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->schema([TextInput::make('units')->numeric()->integer()->required()->minValue(1)->maxValue(100000)])
                    ->action(function (StockLevel $record, array $data) {
                        // Atomic, so a sale at the same moment isn't overwritten.
                        $record->increment('on_hand', (int) $data['units']);
                        Notification::make()->title("Added {$data['units']} to {$record->colour} / {$record->size}")->success()->send();
                    }),
            ]);
    }
}
