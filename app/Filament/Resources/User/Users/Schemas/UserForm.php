<?php

namespace App\Filament\Resources\User\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),

            // Если поля role в users НЕТ — удали этот блок.
            Select::make('role')
                ->options([
                    'admin' => 'admin',
                    'editor' => 'editor',
                ])
                ->required(),

            TextInput::make('password')
                ->password()
                ->label('New password')
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? $state : null)
                ->dehydrated(fn (?string $state) => filled($state)),
        ]);
    }
}
