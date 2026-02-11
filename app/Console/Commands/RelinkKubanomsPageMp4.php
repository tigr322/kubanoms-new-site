<?php

namespace App\Console\Commands;

use App\Services\Import\KubanomsPageMp4Relinker;
use Illuminate\Console\Command;
use RuntimeException;

class RelinkKubanomsPageMp4 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kubanoms:relink-page-mp4
                            {--page-url=/page21913.html : URL страницы для обработки}
                            {--base-url=http://kubanoms.ru : Базовый URL источника}
                            {--disk=public : Диск для сохранения}
                            {--file-dir=cms/page/videos : Подкаталог для mp4}
                            {--show-links : Вывести storage-ссылки}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Скачать mp4 только для одной страницы и заменить ссылки на /storage/...';

    /**
     * Execute the console command.
     */
    public function handle(KubanomsPageMp4Relinker $relinker): int
    {
        $pageUrl = (string) ($this->option('page-url') ?: '/page21913.html');
        $baseUrl = (string) ($this->option('base-url') ?: 'http://kubanoms.ru');
        $disk = (string) ($this->option('disk') ?: 'public');
        $fileDirectory = (string) ($this->option('file-dir') ?: 'cms/page/videos');
        $showLinks = (bool) $this->option('show-links');

        $onStorageLink = $showLinks
            ? function (string $urlPath): void {
                $this->line($urlPath);
            }
        : null;

        try {
            if ($showLinks) {
                $this->line('Storage-ссылки mp4:');
            }

            $stats = $relinker->relink(
                pageUrl: $pageUrl,
                baseUrl: $baseUrl,
                disk: $disk,
                fileDirectory: $fileDirectory,
                collectLinks: false,
                onStorageLink: $onStorageLink,
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Обработка страницы завершена.');
        $this->line('Page URL: '.$stats['page_url']);
        $this->line('Links checked: '.$stats['links_checked']);
        $this->line('Links replaced: '.$stats['links_replaced']);
        $this->line('Files downloaded: '.$stats['files_downloaded']);
        $this->line('Files skipped: '.$stats['files_skipped']);
        $this->line('Files failed: '.$stats['files_failed']);
        $this->line('Page updated: '.$stats['page_updated']);

        return self::SUCCESS;
    }
}
