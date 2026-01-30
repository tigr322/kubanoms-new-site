<?php

namespace App\Filament\Resources\Cms\CmsFiles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CmsFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('file_folder_id')
                    ->numeric(),
                TextInput::make('original_name')
                    ->required(),
                TextInput::make('path'),
                TextInput::make('mime_type')
                    ->required(),
                TextInput::make('extension')
                    ->required(),
                TextInput::make('description'),
                DateTimePicker::make('create_date'),
                TextInput::make('create_user'),
                DateTimePicker::make('update_date'),
                TextInput::make('update_user'),
                DateTimePicker::make('delete_date'),
                TextInput::make('delete_user'),
            ]);
    }
}
