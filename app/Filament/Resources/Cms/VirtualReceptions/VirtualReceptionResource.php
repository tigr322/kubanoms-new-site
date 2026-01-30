<?php

namespace App\Filament\Resources\Cms\VirtualReceptions;

use App\Filament\Resources\Cms\VirtualReceptions\Pages\ListVirtualReceptions;
use App\Filament\Resources\Cms\VirtualReceptions\Schemas\VirtualReceptionForm;
use App\Models\Oms\OmsVirtualReception;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VirtualReceptionResource extends Resource
{
    protected static ?string $model = OmsVirtualReception::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static UnitEnum|string|null $navigationGroup = 'Обращения';

    protected static ?string $navigationLabel = 'Виртуальная приемная';

    protected static ?string $modelLabel = 'Обращение';

    protected static ?string $pluralModelLabel = 'Обращения';

    public static function form(Schema $schema): Schema
    {
        return VirtualReceptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fio')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'warning',
                        'processed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('create_date')
                    ->label('Дата создания')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Новые',
                        'processed' => 'Обработанные',
                        'rejected' => 'Отклоненные',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([
                // Убираем массовые действия для безопасности
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVirtualReceptions::route('/'),
            'view' => Pages\ViewVirtualReception::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('create_date', 'desc');
    }
}
