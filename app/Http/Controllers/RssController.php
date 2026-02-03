<?php

namespace App\Http\Controllers;

use App\Models\Cms\CmsPage;
use App\PageStatus;
use App\PageType;
use Carbon\Carbon;
use Illuminate\Http\Response;

class RssController extends Controller
{
    public function index(): Response
    {
        $items = CmsPage::query()
            ->where('page_status', PageStatus::PUBLISHED->value)
            ->where('page_of_type', PageType::NEWS->value)
            ->where(function ($query): void {
                $query
                    ->where('template', 'news')
                    ->orWhere('url', 'like', '/news/%');
            })
            ->orderByDesc('publication_date')
            ->limit(50)
            ->get();

        $siteLink = url('/');

        $xml = '';
        $xml .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<rss version="2.0">'."\n";
        $xml .= "    <channel>\n";
        $xml .= '        <title>'.$this->xmlEscape('Выгрузка новостей сайта www.kubanoms.ru')."</title>\n";
        $xml .= '        <description>'.$this->xmlEscape('Свежие новости сайта www.kubanoms.ru')."</description>\n";
        $xml .= '        <link>'.$this->xmlEscape($siteLink)."</link>\n";
        $xml .= '        <language>ru</language>'."\n";
        $xml .= '        <ttl>60</ttl>'."\n";
        $xml .= '        <lastBuildDate>'.$this->xmlEscape(Carbon::now('Europe/Moscow')->format(DATE_RSS))."</lastBuildDate>\n";

        foreach ($items as $page) {
            $publishedAt = $this->rssDateTime(
                $page->getRawOriginal('publication_date')
                    ?: $page->getRawOriginal('create_date'),
            );
            $absoluteUrl = url($page->url);

            $titlePrefix = $publishedAt->format('d.m.Y').'. ';
            $title = $titlePrefix.($page->title ?? '');

            $description = $page->meta_description;
            if (! $description) {
                $description = str($page->content ?? '')
                    ->stripTags()
                    ->squish()
                    ->limit(800)
                    ->toString();
            }

            $xml .= "        <item>\n";
            $xml .= '            <pubDate>'.$this->xmlEscape($publishedAt->format(DATE_RSS))."</pubDate>\n";
            $xml .= '            <category>'.$this->xmlEscape('Новости')."</category>\n";
            $xml .= '            <title>'.$this->cdata($title)."</title>\n";
            $xml .= '            <link>'.$this->xmlEscape($absoluteUrl)."</link>\n";
            $xml .= '            <description>'.$this->cdata($description)."</description>\n";
            $xml .= '            <guid>'.$this->xmlEscape($absoluteUrl)."</guid>\n";
            $xml .= "        </item>\n";
        }

        $xml .= "    </channel>\n";
        $xml .= '</rss>'."\n";

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    private function rssDateTime(?string $rawDateTime): Carbon
    {
        if (! $rawDateTime) {
            return Carbon::now('Europe/Moscow');
        }

        return Carbon::parse($rawDateTime, 'Europe/Moscow');
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function cdata(string $value): string
    {
        // Ensure we never close the CDATA section accidentally.
        $safe = str_replace(']]>', ']]]]><![CDATA[>', $value);

        return '<![CDATA['.$safe.']]>';
    }
}
