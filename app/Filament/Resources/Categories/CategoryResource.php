<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Departments shown in the shop navigation (Women, Men, Accessories...). */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $modelLabel = 'department';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('slug')->required()->alphaDash()->maxLength(100)->unique(ignoreRecord: true)
                ->helperText('Used in the URL, e.g. /shop/women.'),
            Textarea::make('description')->rows(2)->maxLength(255)->columnSpanFull(),
            FileUpload::make('image')->image()->disk(fn () => config('shop.media_disk'))->directory('categories')
                ->visibility('public')->maxSize(5120)->columnSpanFull(),
            ColorPicker::make('swatch')->required()->default('#e5e5e5'),
            TextInput::make('position')->label('Menu order')->required()->integer()->default(0)
                ->helperText('Lower numbers come first.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('position')
            ->reorderable('position')
            ->modifyQueryUsing(fn ($query) => $query->withCount('products'))
            ->columns([
                TextColumn::make('name')->weight('bold'),
                TextColumn::make('slug'),
                TextColumn::make('products_count')->label('Products'),
            ])
            ->recordActions([
                EditAction::make(),
                // Deleting a department would delete its products too (and their stock), so only empty ones can go.
                DeleteAction::make()->visible(fn (Category $record) => $record->products_count === 0),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}
