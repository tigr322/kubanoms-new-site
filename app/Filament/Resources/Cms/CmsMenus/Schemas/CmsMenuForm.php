<?php

namespace App\Filament\Resources\Cms\CmsMenus\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CmsMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('max_depth')
                    ->required()
                    ->numeric(),
                TextInput::make('update_user'),
                TextInput::make('title')
                    ->required(),
                DateTimePicker::make('create_date'),
                TextInput::make('create_user'),
                DateTimePicker::make('update_date'),
                DateTimePicker::make('delete_date'),
                TextInput::make('delete_user'),
            ]);
    }
}
