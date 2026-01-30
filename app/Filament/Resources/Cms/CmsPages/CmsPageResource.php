<?php

namespace App\Filament\Resources\Cms\CmsPages;

use App\Filament\Resources\Cms\CmsPages\Pages\CreateCmsPage;
use App\Filament\Resources\Cms\CmsPages\Pages\EditCmsPage;
use App\Filament\Resources\Cms\CmsPages\Pages\ListCmsPages;
use App\Filament\Resources\Cms\CmsPages\Schemas\CmsPageForm;
use App\Filament\Resources\Cms\CmsPages\Tables\CmsPagesTable;
use App\Filament\Resources\Cms\RelationManagers\PageDocumentsRelationManager;
use App\Models\Cms\CmsPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CmsPageResource extends Resource
{
    protected static ?string $model = CmsPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Страницы';

    protected static ?string $modelLabel = 'Страница';

    protected static ?string $pluralModelLabel = 'Страницы';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CmsPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsPagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PageDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCmsPages::route('/'),
            'create' => CreateCmsPage::route('/create'),
            'edit' => EditCmsPage::route('/{record}/edit'),
        ];
    }
}
