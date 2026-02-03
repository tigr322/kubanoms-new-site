<?php

namespace App\Filament\Resources\Cms\CmsPages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CmsPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.title')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('title_short')
                    ->searchable(),
                TextColumn::make('meta_description')
                    ->searchable(),
                TextColumn::make('meta_keywords')
                    ->searchable(),
                TextColumn::make('publication_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('page_status')
                    ->label('Статус')
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof \App\PageStatus) {
                            return $state->getLabel();
                        }

                        return match ((int) $state) {
                            1 => 'Черновик',
                            2 => 'На модерации',
                            3 => 'Опубликовано',
                            default => 'Неизвестно'
                        };
                    })
                    ->color(function ($state): string {
                        if ($state instanceof \App\PageStatus) {
                            return $state->getColor();
                        }

                        return match ((int) $state) {
                            1 => 'gray',
                            2 => 'warning',
                            3 => 'success',
                            default => 'danger'
                        };
                    })
                    ->sortable(),
                TextColumn::make('page_of_type')
                    ->label('Тип')
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof \App\PageType) {
                            return $state->getLabel();
                        }

                        return match ((int) $state) {
                            1 => 'Страница',
                            2 => 'Новость',
                            3 => 'Документ',
                            7 => 'Карта сайта',
                            default => 'Неизвестно'
                        };
                    })
                    ->color(function ($state): string {
                        if ($state instanceof \App\PageType) {
                            return $state->getColor();
                        }

                        return match ((int) $state) {
                            1 => 'primary',
                            2 => 'info',
                            3 => 'success',
                            7 => 'warning',
                            default => 'danger'
                        };
                    })
                    ->sortable(),
                TextColumn::make('update_user')
                    ->searchable(),
                TextColumn::make('create_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('create_user')
                    ->searchable(),
                TextColumn::make('update_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delete_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delete_user')
                    ->searchable(),
                TextColumn::make('url')
                    ->searchable(),
                TextColumn::make('path')
                    ->searchable(),
                TextColumn::make('template')
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
