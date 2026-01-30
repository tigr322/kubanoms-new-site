<?php

namespace App\Filament\Resources\Cms\CmsFileFolders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsFileFoldersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('create_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('create_user')
                    ->searchable(),
                TextColumn::make('update_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('update_user')
                    ->searchable(),
                TextColumn::make('delete_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delete_user')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
