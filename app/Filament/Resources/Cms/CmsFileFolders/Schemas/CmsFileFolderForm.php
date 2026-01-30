<?php

namespace App\Filament\Resources\Cms\CmsFileFolders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CmsFileFolderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                DateTimePicker::make('create_date'),
                TextInput::make('create_user'),
                DateTimePicker::make('update_date'),
                TextInput::make('update_user'),
                DateTimePicker::make('delete_date'),
                TextInput::make('delete_user'),
            ]);
    }
}
