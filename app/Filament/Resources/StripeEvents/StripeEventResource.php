<?php

namespace App\Filament\Resources\StripeEvents;

use App\Filament\Resources\StripeEvents\Pages\ListStripeEvents;
use App\Jobs\ProcessStripeEvent;
use App\Models\StripeEvent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Webhook events from Stripe: mainly to spot and retry ones that failed. */
class StripeEventResource extends Resource
{
    protected static ?string $model = StripeEvent::class;

    protected static ?string $modelLabel = 'Stripe event';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    public static function getNavigationBadge(): ?string
    {
        $count = self::stuck()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /** Unprocessed for more than 10 minutes: retries are failing or no queue worker is running. */
    public static function stuck(): Builder
    {
        return StripeEvent::query()->whereNull('processed_at')->where('created_at', '<=', now()->subMinutes(10));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->dateTime('j M, H:i:s')->sortable(),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('id')->label('Event')->copyable()->searchable()->fontFamily('mono')->size('xs'),
                TextColumn::make('object_id')->label('Object')->copyable()->searchable()->fontFamily('mono')->size('xs'),
                TextColumn::make('attempts'),
                TextColumn::make('processed_at')->label('Processed')->dateTime('j M, H:i:s')->placeholder('Not yet')
                    ->color(fn (?StripeEvent $record) => $record?->processed_at ? null : 'danger'),
                TextColumn::make('last_error')->wrap()->limit(120)->placeholder('—')->color('danger'),
            ])
            ->filters([
                TernaryFilter::make('processed')->label('Processed')
                    ->placeholder('All')->trueLabel('Processed')->falseLabel('Not processed')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('processed_at'),
                        false: fn (Builder $q) => $q->whereNull('processed_at'),
                    ),
            ])
            ->recordActions([
                Action::make('retry')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (StripeEvent $record) => ! $record->isProcessed())
                    ->action(function (StripeEvent $record) {
                        ProcessStripeEvent::dispatch($record->id);
                        Notification::make()->title('Queued for another attempt')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListStripeEvents::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
