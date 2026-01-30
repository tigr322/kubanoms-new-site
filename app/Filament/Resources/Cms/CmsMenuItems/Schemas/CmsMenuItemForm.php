<?php

namespace App\Filament\Resources\Cms\CmsMenuItems\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CmsMenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('page_id')
                    ->relationship('page', 'title'),
                Select::make('parent_id')
                    ->relationship('parent', 'title'),
                Select::make('menu_id')
                    ->relationship('menu', 'name'),
                TextInput::make('type')
                    ->numeric(),
                TextInput::make('url')
                    ->url(),
                TextInput::make('sort_order')
                    ->numeric(),
                Toggle::make('visible')
                    ->required(),
                DateTimePicker::make('create_date'),
                TextInput::make('create_user'),
                DateTimePicker::make('update_date'),
                TextInput::make('update_user'),
                TextInput::make('title'),
                DateTimePicker::make('delete_date'),
                TextInput::make('delete_user'),
            ]);
    }
}
