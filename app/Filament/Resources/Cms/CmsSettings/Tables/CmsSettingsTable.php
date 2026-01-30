<?php

namespace App\Filament\Resources\Cms\CmsSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Описание')
                    ->searchable(),
                IconColumn::make('visibility')
                    ->label('Видимость')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),
                TextColumn::make('content')
                    ->label('Содержимое')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('update_user')
                    ->label('Обновил')
                    ->searchable(),
                TextColumn::make('create_date')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('create_user')
                    ->label('Создал')
                    ->searchable(),
                TextColumn::make('update_date')
                    ->label('Дата обновления')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delete_date')
                    ->label('Дата удаления')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delete_user')
                    ->label('Удалил')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }
}
