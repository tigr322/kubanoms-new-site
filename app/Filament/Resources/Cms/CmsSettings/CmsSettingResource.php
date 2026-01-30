<?php

namespace App\Filament\Resources\Cms\CmsSettings;

use App\Filament\Resources\Cms\CmsSettings\Pages\CreateCmsSetting;
use App\Filament\Resources\Cms\CmsSettings\Pages\EditCmsSetting;
use App\Filament\Resources\Cms\CmsSettings\Pages\ListCmsSettings;
use App\Filament\Resources\Cms\CmsSettings\Schemas\CmsSettingForm;
use App\Filament\Resources\Cms\CmsSettings\Tables\CmsSettingsTable;
use App\Models\Cms\CmsSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CmsSettingResource extends Resource
{
    protected static ?string $model = CmsSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?string $navigationLabel = 'Настройки сайта';

    protected static ?string $modelLabel = 'Настройка';

    protected static ?string $pluralModelLabel = 'Настройки';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CmsSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CmsSettingsTable::configure($table);
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
            'index' => ListCmsSettings::route('/'),
            'create' => CreateCmsSetting::route('/create'),
            'edit' => EditCmsSetting::route('/{record}/edit'),
        ];
    }
}
