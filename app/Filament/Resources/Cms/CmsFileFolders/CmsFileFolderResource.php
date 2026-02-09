<?php

namespace App\Filament\Resources\Cms\CmsFileFolders;

use App\Filament\Resources\Cms\CmsFileFolders\Pages\CreateCmsFileFolder;
use App\Filament\Resources\Cms\CmsFileFolders\Pages\EditCmsFileFolder;
use App\Filament\Resources\Cms\CmsFileFolders\Pages\ListCmsFileFolders;
use App\Filament\Resources\Cms\CmsFileFolders\RelationManagers\FilesRelationManager;
use App\Filament\Resources\Cms\CmsFileFolders\Schemas\CmsFileFolderForm;
use App\Filament\Resources\Cms\CmsFileFolders\Tables\CmsFileFoldersTable;
use App\Models\Cms\CmsFileFolder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CmsFileFolderResource extends Resource
{
    protected static ?string $model = CmsFileFolder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Папки файлов';

    protected static ?string $modelLabel = 'Папка файлов';

    protected static ?string $pluralModelLabel = 'Папки файлов';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CmsFileFolderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsFileFoldersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCmsFileFolders::route('/'),
            'create' => CreateCmsFileFolder::route('/create'),
            'edit' => EditCmsFileFolder::route('/{record}/edit'),
        ];
    }
}
