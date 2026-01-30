<?php

namespace App\Filament\Resources\Cms\CmsFiles;

use App\Filament\Resources\Cms\CmsFiles\Pages\CreateCmsFile;
use App\Filament\Resources\Cms\CmsFiles\Pages\EditCmsFile;
use App\Filament\Resources\Cms\CmsFiles\Pages\ListCmsFiles;
use App\Filament\Resources\Cms\CmsFiles\Schemas\CmsFileForm;
use App\Filament\Resources\Cms\CmsFiles\Tables\CmsFilesTable;
use App\Models\Cms\CmsFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CmsFileResource extends Resource
{
    protected static ?string $model = CmsFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Файлы';

    protected static ?string $modelLabel = 'Файл';

    protected static ?string $pluralModelLabel = 'Файлы';

    protected static ?string $recordTitleAttribute = 'original_name';

    public static function form(Schema $schema): Schema
    {
        return CmsFileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsFilesTable::configure($table);
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
            'index' => ListCmsFiles::route('/'),
            'create' => CreateCmsFile::route('/create'),
            'edit' => EditCmsFile::route('/{record}/edit'),
        ];
    }
}
