<?php

namespace App\Filament\Resources\Cms\CmsMenuItems;

use App\Filament\Resources\Cms\CmsMenuItems\Pages\CreateCmsMenuItem;
use App\Filament\Resources\Cms\CmsMenuItems\Pages\EditCmsMenuItem;
use App\Filament\Resources\Cms\CmsMenuItems\Pages\ListCmsMenuItems;
use App\Filament\Resources\Cms\CmsMenuItems\Schemas\CmsMenuItemForm;
use App\Filament\Resources\Cms\CmsMenuItems\Tables\CmsMenuItemsTable;
use App\Models\Cms\CmsMenuItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CmsMenuItemResource extends Resource
{
    protected static ?string $model = CmsMenuItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Пункты меню';

    protected static ?string $modelLabel = 'Пункт меню';

    protected static ?string $pluralModelLabel = 'Пункты меню';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CmsMenuItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsMenuItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCmsMenuItems::route('/'),
            'create' => CreateCmsMenuItem::route('/create'),
            'edit' => EditCmsMenuItem::route('/{record}/edit'),
        ];
    }
}
