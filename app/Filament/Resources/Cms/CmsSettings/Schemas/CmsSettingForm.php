<?php

namespace App\Filament\Resources\Cms\CmsSettings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CmsSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название настройки')
                    ->required()
                    ->helperText('Уникальное имя настройки (например: contact_phone, contact_email)'),
                TextInput::make('description')
                    ->label('Описание')
                    ->helperText('Краткое описание для администратора'),
                Textarea::make('content')
                    ->label('Содержимое')
                    ->helperText('Значение настройки')
                    ->columnSpanFull(),
                Toggle::make('visibility')
                    ->label('Видимость')
                    ->helperText('Показывать на сайте')
                    ->required(),
                TextInput::make('update_user')
                    ->label('Обновил')
                    ->disabled(),
                DateTimePicker::make('create_date')
                    ->label('Дата создания')
                    ->disabled(),
                TextInput::make('create_user')
                    ->label('Создал')
                    ->disabled(),
                DateTimePicker::make('update_date')
                    ->label('Дата обновления')
                    ->disabled(),
                DateTimePicker::make('delete_date')
                    ->label('Дата удаления')
                    ->disabled(),
                TextInput::make('delete_user')
                    ->label('Удалил')
                    ->disabled(),
            ]);
    }
}
