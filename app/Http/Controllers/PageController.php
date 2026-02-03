<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageShowRequest;
use App\Models\Cms\CmsSetting;
use App\Repositories\PageRepository;
use App\Services\PageResolverService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly PageResolverService $pageResolverService,
    ) {}

    public function show(PageShowRequest $request, string $url): Response
    {
        $page = $this->pageRepository->findByUrl($url);

        if (! $page) {
            abort(404);
        }

        $isAdmin = $request->user()?->role === 'admin';

        // Проверяем статус страницы с учетом enum
        $pageStatus = $page->page_status;
        if ($pageStatus instanceof \App\PageStatus) {
            // Если это enum, сравниваем с enum значением
            if ($pageStatus !== \App\PageStatus::PUBLISHED && ! $isAdmin) {
                throw new ModelNotFoundException('Page unpublished');
            }
        } else {
            // Если это старое значение (int), сравниваем с числом
            if ((int) $pageStatus !== 3 && ! $isAdmin) {
                throw new ModelNotFoundException('Page unpublished');
            }
        }

        $props = $this->pageResolverService->buildViewModel($page);

        // Добавляем контакты для главной страницы
        if ($page->url === '/') {
            $props['contacts'] = CmsSetting::getContacts();
        }

        // Определяем компонент с учетом enum
        $pageType = $page->page_of_type;
        if ($pageType instanceof \App\PageType) {
            // Если это enum, используем enum значения
            $component = match ($pageType) {
                \App\PageType::NEWS => 'NewsDetail',
                \App\PageType::DOCUMENT => 'DocumentDetail',
                \App\PageType::SITEMAP => 'Sitemap',
                default => 'GenericPage',
            };
        } else {
            // Если это старое значение (int), используем числовые значения
            $component = match ((int) $pageType) {
                2 => 'NewsDetail',
                3 => 'DocumentDetail',
                5 => 'PublicationDetail',
                7 => 'Sitemap',
                default => 'GenericPage',
            };
        }

        return Inertia::render($component, [
            ...$props,
            'special' => $request->cookie('special', 0),
            ...$this->documentPageProps($request, $component, $page),
        ]);
    }

    private function documentPageProps(PageShowRequest $request, string $component, \App\Models\Cms\CmsPage $page): array
    {
        if ($component !== 'DocumentDetail') {
            return [];
        }

        $groups = $page->documentsAll()
            ->select('group_title')
            ->whereNotNull('group_title')
            ->distinct()
            ->orderBy('group_title')
            ->pluck('group_title')
            ->values();

        $hasUngrouped = $page->documentsAll()
            ->whereNull('group_title')
            ->exists();

        $activeGroup = $request->query('group');

        if ($activeGroup && ! in_array($activeGroup, ['__all', '__ungrouped'], true) && ! $groups->contains($activeGroup)) {
            $activeGroup = null;
        }

        if (! $activeGroup) {
            $activeGroup = $groups->first() ?? ($hasUngrouped ? '__ungrouped' : '__all');
        }

        $documentsQuery = $page->documentsAll()
            ->where('is_visible', true)
            ->with('file');

        if ($activeGroup === '__ungrouped') {
            $documentsQuery->whereNull('group_title');
        } elseif ($activeGroup !== '__all') {
            $documentsQuery->where('group_title', $activeGroup);
        }

        $documents = $documentsQuery
            ->orderByRaw('CASE WHEN document_date IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('document_date')
            ->orderBy('order')
            ->paginate(50)
            ->withQueryString()
            ->through(function (\App\Models\Cms\CmsPageDocument $doc): array {
                $filePath = $doc->file?->path;

                return [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'group_title' => $doc->group_title,
                    'document_date' => $doc->document_date?->format('d.m.Y'),
                    'file' => [
                        'name' => $doc->file?->original_name,
                        'url' => $filePath ? Storage::disk('public')->url($filePath) : null,
                    ],
                ];
            });

        return [
            'document_groups' => $groups,
            'has_ungrouped_documents' => $hasUngrouped,
            'active_group' => $activeGroup,
            'documents' => $documents,
        ];
    }
}
