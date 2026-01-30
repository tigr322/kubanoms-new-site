<?php

namespace App\Filament\Resources\Cms\VirtualReceptions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class VirtualReceptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fio')
                    ->label('ФИО')
                    ->disabled(),
                TextInput::make('email')
                    ->label('Email')
                    ->disabled(),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->disabled(),
                TextInput::make('birthdate')
                    ->label('Дата рождения')
                    ->disabled(),
                TextInput::make('address')
                    ->label('Адрес проживания')
                    ->disabled(),
                TextInput::make('post_address')
                    ->label('Почтовый адрес')
                    ->disabled(),
                Textarea::make('contents')
                    ->label('Текст обращения')
                    ->rows(6)
                    ->disabled(),
                TextInput::make('status')
                    ->label('Статус')
                    ->disabled(),
                TextInput::make('create_date')
                    ->label('Дата создания')
                    ->disabled(),
                TextInput::make('create_user')
                    ->label('Кто создал')
                    ->disabled(),
                TextInput::make('update_user')
                    ->label('Кто обновил')
                    ->disabled(),
            ]);
    }
}
