<?php

namespace App\Console\Commands;

use App\Services\Import\KubanomsSitemapDownloader;
use Illuminate\Console\Command;

class DownloadKubanomsSitemapPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kubanoms:download-sitemap-pages
                            {--file= : Локальный HTML-файл карты сайта}
                            {--base-url=http://kubanoms.ru : Базовый URL для относительных ссылок}
                            {--output= : Папка для сохранения скачанных страниц}
                            {--include-files : Скачивать файлы (pdf/doc/etc)}
                            {--include-external : Скачивать внешние ссылки}
                            {--limit= : Ограничить количество ссылок}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Скачать страницы из карты сайта kubanoms.ru';

    /**
     * Execute the console command.
     */
    public function handle(KubanomsSitemapDownloader $downloader): int
    {
        $file = (string) ($this->option('file') ?: 'docs/Территориальный фонд ОМС Краснодарского края __ Карта сайта.html');
        $baseUrl = (string) ($this->option('base-url') ?: 'http://kubanoms.ru');
        $outputDir = (string) ($this->option('output') ?: storage_path('app/sitemap-downloads/'.now()->format('Ymd_His')));
        $includeFiles = (bool) $this->option('include-files');
        $includeExternal = (bool) $this->option('include-external');
        $limit = $this->option('limit');
        $limit = $limit !== null ? (int) $limit : null;

        $stats = $downloader->downloadFromFile(
            filePath: $file,
            baseUrl: $baseUrl,
            outputDir: $outputDir,
            includeExternal: $includeExternal,
            includeFiles: $includeFiles,
            limit: $limit,
        );

        $this->info('Скачивание завершено.');
        $this->line('Всего ссылок: '.$stats['links_total']);
        $this->line('Отобрано: '.$stats['links_selected']);
        $this->line('Скачано: '.$stats['downloaded']);
        $this->line('Пропущено: '.$stats['skipped']);
        $this->line('Ошибки: '.$stats['failed']);
        $this->line('Папка: '.$stats['output_dir']);

        if ($stats['failed'] > 0) {
            $this->warn('Часть ссылок не удалось скачать.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
