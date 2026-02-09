<?php

namespace App\Filament\Resources\Cms\CmsFileFolders\RelationManagers;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsFileFolder;
use App\Services\Cms\CmsFileUploadService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * @property CmsFileFolder $ownerRecord
 */
class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';

    protected static ?string $title = 'Файлы папки';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            FileUpload::make('upload')
                ->label('Загрузить файл')
                ->storeFiles(false)
                ->maxSize(51200)
                ->columnSpanFull()
                ->helperText('После сохранения появится storage-ссылка вида /storage/...'),
            TextInput::make('original_name')
                ->label('Имя файла')
                ->required(fn (Get $get): bool => blank($get('upload'))),
            TextInput::make('path')
                ->label('Путь')
                ->helperText('Можно задать вручную, если файл уже лежит в storage/app/public.'),
            TextInput::make('storage_url')
                ->label('Storage ссылка')
                ->disabled()
                ->dehydrated(false),
            TextInput::make('mime_type')
                ->label('MIME')
                ->required(fn (Get $get): bool => blank($get('upload'))),
            TextInput::make('extension')
                ->label('Расширение')
                ->required(fn (Get $get): bool => blank($get('upload'))),
            TextInput::make('description')
                ->label('Описание'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name')
                    ->label('Файл')
                    ->searchable()
                    ->url(fn (CmsFile $record): ?string => $record->storage_url)
                    ->openUrlInNewTab(),
                TextColumn::make('path')
                    ->searchable(),
                TextColumn::make('storage_url')
                    ->label('Storage ссылка')
                    ->copyable()
                    ->copyMessage('Ссылка скопирована')
                    ->toggleable(),
                TextColumn::make('mime_type')
                    ->searchable(),
                TextColumn::make('extension')
                    ->searchable(),
                TextColumn::make('update_date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): CmsFile
    {
        return CmsFile::query()->create($this->normalizeData($data, true));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(CmsFile $record, array $data): CmsFile
    {
        $record->update($this->normalizeData($data, false));

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeData(array $data, bool $isCreate): array
    {
        return app(CmsFileUploadService::class)->normalizeForSave(
            data: $data,
            folderId: $this->ownerRecord->id,
            actor: Filament::auth()->user()?->name,
            isCreate: $isCreate,
        );
    }
}
