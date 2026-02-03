<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageShowRequest;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsPageDocument;
use App\Models\Cms\CmsSetting;
use App\PageStatus;
use App\PageType;
use App\Repositories\PageRepository;
use App\Services\PageResolverService;
use Illuminate\Database\Eloquent\Model;
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

        if ($page->page_status !== PageStatus::PUBLISHED && ! $isAdmin) {
            throw new ModelNotFoundException('Page unpublished');
        }

        $props = $this->pageResolverService->buildViewModel($page);

        // Добавляем контакты для главной страницы
        if ($page->url === '/') {
            $props['contacts'] = CmsSetting::getContacts();
        }

        $component = match ($page->page_of_type) {
            PageType::NEWS => 'NewsDetail',
            PageType::DOCUMENT => 'DocumentDetail',
            PageType::SITEMAP => 'Sitemap',
            PageType::PUBLICATION => 'PublicationDetail',
            default => 'GenericPage',
        };

        return Inertia::render($component, [
            ...$props,
            'special' => (int) $request->cookie('special', '0'),
            ...$this->documentPageProps($request, $component, $page),
        ]);
    }

    private function documentPageProps(PageShowRequest $request, string $component, CmsPage $page): array
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
            ->through(function (Model $doc): array {
                /** @var CmsPageDocument $doc */
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
