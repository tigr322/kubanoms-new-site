<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use UnitEnum;

class LearnToAdmin extends Page
{
    protected static ?string $title = 'Инструкция для администраторов';

    protected static ?string $slug = 'learn-to-admin';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Справка';

    protected static ?string $navigationLabel = 'Инструкция';

    protected string $view = 'filament.pages.learn-to-admin';

    public string $markdown = '';

    public bool $fileExists = false;

    public ?string $lastModifiedAt = null;

    public function mount(): void
    {
        $this->loadFile();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'html' => str($this->markdown)->markdown()->sanitizeHtml()->toString(),
            'file_path' => $this->relativeFilePath(),
            'file_exists' => $this->fileExists,
            'last_modified_at' => $this->lastModifiedAt,
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Редактировать')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->visible(fn (): bool => $this->canEdit())
                ->authorize(fn (): bool => $this->canEdit())
                ->modalHeading('Редактировать инструкцию')
                ->schema([
                    MarkdownEditor::make('content')
                        ->label('LearnToAdmin.md')
                        ->required()
                        ->columnSpanFull()
                        ->minHeight('60vh'),
                ])
                ->fillForm(fn (): array => ['content' => $this->markdown])
                ->action(function (array $data): void {
                    $path = $this->filePath();
                    $content = (string) ($data['content'] ?? '');

                    File::put($path, $content);

                    $this->loadFile();
                })
                ->successNotificationTitle('Инструкция сохранена'),
        ];
    }

    private function canEdit(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    private function filePath(): string
    {
        $path = config('admin.learn_to_admin_path');

        if (is_string($path) && $path !== '') {
            return $path;
        }

        return base_path('LearnToAdmin.md');
    }

    private function relativeFilePath(): string
    {
        $path = $this->filePath();
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (str_starts_with($path, $base)) {
            return ltrim(str_replace($base, '', $path), DIRECTORY_SEPARATOR);
        }

        return $path;
    }

    private function loadFile(): void
    {
        $path = $this->filePath();

        if (! File::exists($path)) {
            $this->markdown = '';
            $this->fileExists = false;
            $this->lastModifiedAt = null;

            return;
        }

        $this->markdown = File::get($path);
        $this->fileExists = true;

        $timestamp = File::lastModified($path);
        $this->lastModifiedAt = Carbon::createFromTimestamp($timestamp)->format('d.m.Y H:i');
    }
}
