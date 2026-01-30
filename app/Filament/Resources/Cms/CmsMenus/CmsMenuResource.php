<?php

namespace App\Filament\Resources\Cms\CmsMenus;

use App\Filament\Resources\Cms\CmsMenus\Pages\CreateCmsMenu;
use App\Filament\Resources\Cms\CmsMenus\Pages\EditCmsMenu;
use App\Filament\Resources\Cms\CmsMenus\Pages\ListCmsMenus;
use App\Filament\Resources\Cms\CmsMenus\Schemas\CmsMenuForm;
use App\Filament\Resources\Cms\CmsMenus\Tables\CmsMenusTable;
use App\Models\Cms\CmsMenu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CmsMenuResource extends Resource
{
    protected static ?string $model = CmsMenu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Меню';

    protected static ?string $modelLabel = 'Меню';

    protected static ?string $pluralModelLabel = 'Меню';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CmsMenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsMenusTable::configure($table);
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
            'index' => ListCmsMenus::route('/'),
            'create' => CreateCmsMenu::route('/create'),
            'edit' => EditCmsMenu::route('/{record}/edit'),
        ];
    }
}
