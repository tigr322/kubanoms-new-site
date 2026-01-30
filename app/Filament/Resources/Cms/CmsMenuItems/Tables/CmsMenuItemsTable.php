<?php

namespace App\Filament\Resources\Cms\CmsMenuItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsMenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page.title')
                    ->searchable(),
                TextColumn::make('parent.title')
                    ->searchable(),
                TextColumn::make('menu.name')
                    ->searchable(),
                TextColumn::make('type')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('url')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('visible')
                    ->boolean(),
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
                TextColumn::make('title')
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
