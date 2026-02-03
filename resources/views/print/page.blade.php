<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $page->title }}</title>

        <link rel="stylesheet" href="/legacy/style.css">
        <link rel="stylesheet" href="/legacy/pos.css">

        <style>
            body {
                background: #fff;
            }

            .print-header {
                padding: 20px 0 0 0;
                text-align: center;
            }

            .print-header img {
                max-width: 100%;
                height: auto;
            }

            .print-actions {
                padding: 16px 20px 0 20px;
            }

            .print-actions a {
                margin-left: 12px;
            }

            .news-gallery img {
                display: block;
                max-width: 100%;
                height: auto;
                margin: 0 0 10px 0;
            }

            @media print {
                .print-actions {
                    display: none;
                }

                .content {
                    border: none;
                    padding: 0;
                }

                .wrapper {
                    width: auto;
                }
            }
        </style>
    </head>
    <body>
        @php
            $normalizeHref = function (?string $filePath): ?string {
                if (! $filePath) {
                    return null;
                }

                $filePath = (string) $filePath;

                if (preg_match('#^https?://#i', $filePath) || str_starts_with($filePath, '//')) {
                    return $filePath;
                }

                if (str_starts_with($filePath, '/storage/')) {
                    return $filePath;
                }

                if (str_starts_with($filePath, '/')) {
                    return '/storage'.$filePath;
                }

                if (str_starts_with($filePath, 'storage/')) {
                    return '/'.$filePath;
                }

                return '/storage/'.ltrim(preg_replace('#^public/#', '', $filePath), '/');
            };

            $topImagePath = '/legacy/image/top1.gif';
        @endphp

        <div class="wrapper">
            <div class="print-header">
                <img src="{{ $topImagePath }}" alt="" width="641" height="114">
            </div>

            <div class="print print-actions">
                <a href="#" onclick="window.print(); return false;">Печать</a>
                <a href="{{ $page->url }}">Обычная версия</a>
            </div>

            <div class="content">
                <h1>{{ $page->title }}</h1>

                @if($page->publication_date)
                    <p class="date">{{ $page->publication_date->format('d.m.Y') }}</p>
                @endif

                {!! $content !!}

                @if(!empty($page->images))
                    <h3>Фотографии</h3>
                    <div class="news-gallery">
                        @foreach(($page->images ?? []) as $image)
                            @php($src = $normalizeHref($image))
                            @if($src)
                                <img src="{{ $src }}" alt="{{ $page->title }}">
                            @endif
                        @endforeach
                    </div>
                @endif

                @if(!empty($page->path))
                    @php($documentHref = $normalizeHref($page->path))
                    @if($documentHref)
                        <p>
                            <a href="{{ $documentHref }}" target="_blank" rel="noopener">Скачать документ</a>
                        </p>
                    @endif
                @endif

                @if(!empty($page->attachments))
                    <h3>Приложения</h3>
                    <ul>
                        @foreach(($page->attachments ?? []) as $file)
                            @php($filePath = (string) $file)
                            @php($href = $normalizeHref($filePath))
                            <li>
                                @if($href)
                                    <a href="{{ $href }}" target="_blank" rel="noopener">{{ basename($filePath) }}</a>
                                @else
                                    {{ basename($filePath) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </body>
</html>
