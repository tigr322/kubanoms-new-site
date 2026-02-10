<?php

namespace App\Console\Commands;

use App\Services\Import\KubanomsFileRelinker;
use Illuminate\Console\Command;

class RelinkKubanomsFileLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kubanoms:relink-file-links
                            {--base-url=http://kubanoms.ru : Базовый URL источника файлов}
                            {--disk=public : Диск для сохранения файлов}
                            {--file-dir=cms/page/files : Подкаталог для файлов документов}
                            {--limit= : Ограничить количество страниц}
                            {--page-id=* : Обрабатывать только указанные ID страниц}
                            {--show-links : Вывести storage-ссылки загруженных файлов}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Скачать файловые ссылки kubanoms.ru из контента страниц и заменить их на /storage/...';

    /**
     * Execute the console command.
     */
    public function handle(KubanomsFileRelinker $relinker): int
    {
        $baseUrl = (string) ($this->option('base-url') ?: 'http://kubanoms.ru');
        $disk = (string) ($this->option('disk') ?: 'public');
        $fileDirectory = (string) ($this->option('file-dir') ?: 'cms/page/files');
        $limit = $this->option('limit');
        $limit = $limit !== null ? (int) $limit : null;
        $pageIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $value): int => (int) $value, (array) $this->option('page-id')),
            static fn (int $value): bool => $value > 0,
        )));
        $showLinks = (bool) $this->option('show-links');

        if ($showLinks) {
            $this->line('Файлы:');
        }

        $onStorageLink = $showLinks
            ? function (string $urlPath): void {
                $this->line($urlPath);
            }
        : null;

        $stats = $relinker->relink(
            baseUrl: $baseUrl,
            disk: $disk,
            fileDirectory: $fileDirectory,
            limit: $limit,
            pageIds: $pageIds,
            collectLinks: false,
            onStorageLink: $onStorageLink,
        );

        $this->info('Обновление ссылок завершено.');
        $this->line('Страниц всего: '.$stats['pages_total']);
        $this->line('Страниц обработано: '.$stats['pages_processed']);
        $this->line('Страниц обновлено: '.$stats['pages_updated']);
        $this->line('Ссылок проверено: '.$stats['links_checked']);
        $this->line('Ссылок заменено: '.$stats['links_replaced']);
        $this->line('Файлов скачано: '.$stats['files_downloaded']);
        $this->line('Файлов пропущено: '.$stats['files_skipped']);
        $this->line('Ошибок файлов: '.$stats['files_failed']);
        $this->line('Storage-ссылок: '.$stats['storage_links_reported']);

        return self::SUCCESS;
    }
}
