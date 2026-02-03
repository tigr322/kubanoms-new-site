<?php

namespace App\Filament\Resources\Cms\RelationManagers;

use App\Models\Cms\CmsFile;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsPageDocument;
use App\PageType;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PageDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documentsAll';

    protected static ?string $model = CmsPageDocument::class;

    protected static ?string $title = 'Документы';

    public static function canViewForRecord(mixed $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord instanceof CmsPage) {
            return false;
        }

        $type = $ownerRecord->page_of_type;

        if ($type instanceof PageType) {
            return $type === PageType::DOCUMENT;
        }

        return (int) $type === PageType::DOCUMENT->value;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),

                TextInput::make('group_title')
                    ->label('Таблица')
                    ->helperText('Название таблицы/группы для вывода на странице. Например: "2026 год".')
                    ->maxLength(255),

                DatePicker::make('document_date')
                    ->label('Дата документа'),

                FileUpload::make('upload')
                    ->label('Файл')
                    ->storeFiles(false)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain',
                        'text/rtf',
                    ])
                    ->maxSize(10240)
                    ->helperText('Если редактируете документ — загрузите новый файл только если нужно заменить.')
                    ->required(fn (?CmsPageDocument $record): bool => $record === null),

                TextInput::make('order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_visible')
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

                Tables\Columns\TextColumn::make('group_title')
                    ->label('Таблица')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('document_date')
                    ->label('Дата')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('file.original_name')
                    ->label('Файл')
                    ->searchable()
                    ->url(fn (CmsPageDocument $record): ?string => $record->file?->path
                        ? \Storage::disk('public')->url($record->file->path)
                        : null)
                    ->openUrlInNewTab(),

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
                Action::make('bulkUpload')
                    ->label('Загрузить файлы')
                    ->schema([
                        FileUpload::make('files')
                            ->label('Файлы')
                            ->storeFiles(false)
                            ->multiple()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'text/plain',
                                'text/rtf',
                            ])
                            ->maxSize(10240)
                            ->required(),
                        TextInput::make('group_title')
                            ->label('Таблица')
                            ->maxLength(255),
                        Toggle::make('is_visible')
                            ->label('Видимость')
                            ->default(true),
                    ])
                    ->action(function (array $data): void {
                        /** @var array<int, TemporaryUploadedFile> $files */
                        $files = $data['files'] ?? [];
                        $groupTitle = filled($data['group_title'] ?? null) ? (string) $data['group_title'] : null;
                        $isVisible = (bool) ($data['is_visible'] ?? true);

                        $nextOrder = ((int) $this->ownerRecord->documentsAll()->max('order')) + 1;

                        foreach ($files as $file) {
                            if (! $file instanceof TemporaryUploadedFile) {
                                continue;
                            }

                            $path = $file->store('cms/documents/files', 'public');

                            $cmsFile = CmsFile::create([
                                'original_name' => $file->getClientOriginalName(),
                                'path' => $path,
                                'mime_type' => $file->getMimeType(),
                                'extension' => $file->getClientOriginalExtension(),
                                'description' => '',
                                'create_date' => now(),
                                'create_user' => Auth::user()?->name ?? 'system',
                            ]);

                            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                            CmsPageDocument::create([
                                'page_id' => $this->ownerRecord->id,
                                'file_id' => $cmsFile->id,
                                'title' => $title,
                                'group_title' => $groupTitle,
                                'order' => $nextOrder++,
                                'is_visible' => $isVisible,
                            ]);
                        }
                    }),
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    BulkAction::make('setGroup')
                        ->label('Назначить таблицу')
                        ->schema([
                            TextInput::make('group_title')
                                ->label('Название таблицы')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $groupTitle = (string) $data['group_title'];

                            $records->each(fn (CmsPageDocument $record) => $record->update([
                                'group_title' => $groupTitle,
                            ]));
                        }),
                    BulkAction::make('clearGroup')
                        ->label('Убрать таблицу')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(
                            fn (CmsPageDocument $record) => $record->update(['group_title' => null]),
                        )),
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
        /** @var TemporaryUploadedFile $upload */
        $upload = $data['upload'];
        unset($data['upload']);

        $path = $upload->store('cms/documents/files', 'public');

        // Создаем запись в cms_file
        $cmsFile = CmsFile::create([
            'original_name' => $upload->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $upload->getMimeType(),
            'extension' => $upload->getClientOriginalExtension(),
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
        $upload = $data['upload'] ?? null;
        unset($data['upload']);

        // Если есть новый файл, обновляем его
        if ($upload instanceof TemporaryUploadedFile) {
            $path = $upload->store('cms/documents/files', 'public');

            // Создаем новую запись в cms_file
            $cmsFile = CmsFile::create([
                'original_name' => $upload->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $upload->getMimeType(),
                'extension' => $upload->getClientOriginalExtension(),
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
