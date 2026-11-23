<?php

namespace App\Filament\Resources\Products;

use App\Enums\ProductBadge;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\StockLevelsRelationManager;
use App\Filament\Support\MoneyInput;
use App\Models\Product;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Details')->columnSpan(2)->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')->required()->maxLength(255)->alphaDash()->unique(ignoreRecord: true)
                    ->helperText('Used in the product URL. Changing it breaks old links.'),
                Select::make('category_id')->label('Department')->relationship('category', 'name')->required(),
                TextInput::make('type')->required()->maxLength(100)->datalist(fn () => Product::query()->distinct()->orderBy('type')->pluck('type')->all())
                    ->helperText('e.g. Knitwear. Shoppers filter by this.'),
                Textarea::make('description')->required()->rows(4)->columnSpanFull(),
                TextInput::make('materials')->maxLength(255)->columnSpanFull(),
            ]),

            Section::make('Visibility & price')->columnSpan(1)->schema([
                Toggle::make('is_active')->label('On sale in the shop')->default(true),
                Toggle::make('is_featured')->label('Featured on the home page'),
                MoneyInput::make('price')->required()->rules(['gt:0']),
                MoneyInput::make('compare_at_price')->label('Was (for reduced items)')
                    ->gt('price')
                    ->validationMessages(['gt' => 'The "was" price must be higher than the price.']),
                Select::make('badge')->options(ProductBadge::class)->placeholder('None'),
            ]),

            Section::make('Photo')->columnSpan(2)->schema([
                FileUpload::make('image')->hiddenLabel()
                    ->image()
                    ->disk(fn () => config('shop.media_disk'))
                    ->directory('products')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->imageEditor()
                    ->imageEditorAspectRatioOptions(['3:4'])
                    ->helperText('Portrait, 3:4, at least 1200 × 1600 px. JPEG, PNG or WebP, up to 5 MB.'),
                ColorPicker::make('swatch')->label('Placeholder colour')->required()->default('#e5e5e5')
                    ->helperText('Shown while the photo loads, or instead of it if there is none.'),
            ]),

            Section::make('Options')->columnSpan(1)
                ->description('Each colour × size gets its own stock level (new ones start at 0).')
                ->schema([
                    Repeater::make('colours')->required()->minItems(1)->reorderable()->defaultItems(1)
                        ->schema([
                            TextInput::make('name')->required()->maxLength(50)->distinct(),
                            ColorPicker::make('hex')->required(),
                        ])->columns(2),
                    TagsInput::make('sizes')->required()
                        ->placeholder('Add a size, e.g. M')
                        ->suggestions(['XS', 'S', 'M', 'L', 'XL', 'One size']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category')->withSum('stockLevels as on_hand_total', 'on_hand')
                ->withSum('stockLevels as reserved_total', 'reserved'))
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('image')->label('')->disk(fn () => config('shop.media_disk'))->square()->size(40),
                TextColumn::make('name')->searchable()->sortable()->weight('bold')
                    ->description(fn (Product $record) => $record->type),
                TextColumn::make('category.name')->label('Department')->sortable(),
                TextColumn::make('price')->sortable()
                    ->formatStateUsing(fn (int $state) => Money::format($state))
                    ->description(fn (Product $record) => $record->compare_at_price ? 'was '.Money::format($record->compare_at_price) : null),
                TextColumn::make('available')->label('Available')
                    ->state(fn (Product $record) => max(0, (int) $record->on_hand_total - (int) $record->reserved_total))
                    ->badge()
                    ->color(fn (int $state) => match (true) {
                        $state === 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    }),
                ToggleColumn::make('is_active')->label('On sale'),
                ToggleColumn::make('is_featured')->label('Featured'),
            ])
            ->filters([
                SelectFilter::make('category')->label('Department')->relationship('category', 'name'),
                TernaryFilter::make('is_active')->label('On sale'),
                TernaryFilter::make('stock')->label('Stock')
                    ->placeholder('All')->trueLabel('Has stock')->falseLabel('Sold out')
                    ->queries(
                        true: fn (Builder $q) => $q->whereHas('stockLevels', fn (Builder $l) => $l->whereColumn('on_hand', '>', 'reserved')),
                        false: fn (Builder $q) => $q->whereDoesntHave('stockLevels', fn (Builder $l) => $l->whereColumn('on_hand', '>', 'reserved')),
                    ),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [StockLevelsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
