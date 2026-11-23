<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/**
 * A price field that shows pounds (89.50) but stores minor units (8950),
 * matching how the shop stores every amount.
 */
final class MoneyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->prefix('£')
            ->formatStateUsing(fn ($state) => $state === null ? null : number_format($state / 100, 2, '.', ''))
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 100));
    }
}
