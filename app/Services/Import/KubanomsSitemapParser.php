<?php

namespace App\Services\Import;

use DOMDocument;
use DOMElement;
use DOMXPath;

class KubanomsSitemapParser
{
    /**
     * @return array<int, array{title: string, href: string, children: array<int, array<string, mixed>>}>
     */
    public function parse(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $prepared = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x10FFFF], 'UTF-8');
        $dom->loadHTML($prepared, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $root = $this->findRootList($xpath);

        if (! $root) {
            return [];
        }

        return $this->parseList($root);
    }

    private function findRootList(DOMXPath $xpath): ?DOMElement
    {
        $list = $xpath->query('//h1[contains(normalize-space(.), "Карта сайта")]/following::ul[1]');

        if ($list && $list->length > 0 && $list->item(0) instanceof DOMElement) {
            return $list->item(0);
        }

        $list = $xpath->query('//div[contains(@class,"middle_second")]//ul[1]');

        if ($list && $list->length > 0 && $list->item(0) instanceof DOMElement) {
            return $list->item(0);
        }

        $list = $xpath->query('//ul[1]');

        if ($list && $list->length > 0 && $list->item(0) instanceof DOMElement) {
            return $list->item(0);
        }

        return null;
    }

    /**
     * @return array<int, array{title: string, href: string, children: array<int, array<string, mixed>>}>
     */
    private function parseList(DOMElement $list): array
    {
        $items = [];

        foreach ($list->childNodes as $node) {
            if (! $node instanceof DOMElement || $node->tagName !== 'li') {
                continue;
            }

            $link = $this->firstDirectLink($node);

            if (! $link) {
                continue;
            }

            $title = trim(preg_replace('/\s+/u', ' ', $link->textContent ?? '') ?? '');
            $href = trim($link->getAttribute('href'));

            if ($title === '' || $href === '') {
                continue;
            }

            $childrenList = $this->firstChildList($node);
            $children = $childrenList ? $this->parseList($childrenList) : [];

            $items[] = [
                'title' => $title,
                'href' => $href,
                'children' => $children,
            ];
        }

        return $items;
    }

    private function firstDirectLink(DOMElement $item): ?DOMElement
    {
        foreach ($item->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'a') {
                return $child;
            }
        }

        $links = $item->getElementsByTagName('a');

        if ($links->length > 0 && $links->item(0) instanceof DOMElement) {
            return $links->item(0);
        }

        return null;
    }

    private function firstChildList(DOMElement $item): ?DOMElement
    {
        foreach ($item->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'ul') {
                return $child;
            }
        }

        return null;
    }
}
