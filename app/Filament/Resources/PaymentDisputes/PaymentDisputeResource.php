<?php

namespace App\Filament\Resources\PaymentDisputes;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\PaymentDisputes\Pages\ListPaymentDisputes;
use App\Filament\Support\StripeDashboard;
use App\Models\PaymentDispute;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Card disputes. Evidence is submitted in the Stripe dashboard; this tracks them. */
class PaymentDisputeResource extends Resource
{
    protected static ?string $model = PaymentDispute::class;

    protected static ?string $modelLabel = 'dispute';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    public static function getNavigationBadge(): ?string
    {
        $open = PaymentDispute::query()->whereNull('closed_at')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('order'))
            ->columns([
                TextColumn::make('created_at')->label('Opened')->date('j M Y')->sortable(),
                TextColumn::make('order.reference')->label('Order')->placeholder('Unknown')
                    ->url(fn (PaymentDispute $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                TextColumn::make('amount')->formatStateUsing(fn (int $state, PaymentDispute $record) => Money::format($state, $record->currency)),
                TextColumn::make('reason')->formatStateUsing(fn (?string $state) => $state ? str($state)->replace('_', ' ')->ucfirst() : '—'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->ucfirst())
                    ->color(fn (string $state) => match ($state) {
                        'won' => 'success',
                        'lost' => 'danger',
                        'needs_response', 'warning_needs_response' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('evidence_due_by')->label('Respond by')->date('j M Y')->placeholder('—')
                    ->color(fn (PaymentDispute $record) => $record->isOpen() && $record->evidence_due_by?->isBefore(now()->addDays(3)) ? 'danger' : null),
            ])
            ->recordActions([
                Action::make('stripe')->label('Open in Stripe')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (PaymentDispute $record) => StripeDashboard::dispute($record->id), true),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPaymentDisputes::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
