<?php

namespace App\Filament\Resources\Cms\RelationManagers;

use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsPageDocument;
use App\Models\Cms\CmsFile;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PageDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $model = CmsPageDocument::class;

    public static function canViewForRecord(mixed $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof CmsPage;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\FileUpload::make('file')
                    ->label('Файл')
                    ->disk('public')
                    ->directory('documents')
                    ->visibility('public')
                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain', 'text/rtf'])
                    ->maxSize(10240)
                    ->required(),

                \Filament\Forms\Components\TextInput::make('order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),

                \Filament\Forms\Components\Toggle::make('is_visible')
                    ->label('Видимость')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable(),

                Tables\Columns\TextColumn::make('file.original_name')
                    ->label('Файл')
                    ->searchable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Видимость')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return $this->getRelationship()->getQuery()->orderBy('order');
    }

    protected function handleRecordCreation(array $data): CmsPageDocument
    {
        $fileData = $data['file'];
        unset($data['file']);

        // Создаем запись в cms_file
        $cmsFile = CmsFile::create([
            'original_name' => $fileData->getClientOriginalName(),
            'path' => $fileData->store('documents', 'public'),
            'mime_type' => $fileData->getMimeType(),
            'extension' => $fileData->getClientOriginalExtension(),
            'description' => $data['title'] ?? '',
            'create_date' => now(),
            'create_user' => Auth::user()?->name ?? 'system',
        ]);

        // Создаем документ с file_id
        $data['file_id'] = $cmsFile->id;
        $data['page_id'] = $this->ownerRecord->id;

        return static::getModel()::create($data);
    }

    protected function handleRecordUpdate(CmsPageDocument $record, array $data): CmsPageDocument
    {
        $fileData = $data['file'] ?? null;
        unset($data['file']);

        // Если есть новый файл, обновляем его
        if ($fileData && is_object($fileData) && method_exists($fileData, 'getClientOriginalName')) {
            // Создаем новую запись в cms_file
            $cmsFile = CmsFile::create([
                'original_name' => $fileData->getClientOriginalName(),
                'path' => $fileData->store('documents', 'public'),
                'mime_type' => $fileData->getMimeType(),
                'extension' => $fileData->getClientOriginalExtension(),
                'description' => $data['title'] ?? '',
                'create_date' => now(),
                'create_user' => Auth::user()?->name ?? 'system',
            ]);

            $data['file_id'] = $cmsFile->id;
        }

        $record->update($data);
        return $record;
    }
}
