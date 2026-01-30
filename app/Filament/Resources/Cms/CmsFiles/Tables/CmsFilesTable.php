<?php

namespace App\Filament\Resources\Cms\CmsFiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsFilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_folder_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('original_name')
                    ->searchable(),
                TextColumn::make('path')
                    ->searchable(),
                TextColumn::make('mime_type')
                    ->searchable(),
                TextColumn::make('extension')
                    ->searchable(),
                TextColumn::make('description')
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
