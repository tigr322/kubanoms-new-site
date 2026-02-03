<?php

namespace App\Http\Controllers;

use App\PageStatus;
use App\PageType;
use App\Repositories\PageRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintController extends Controller
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {}

    public function show(Request $request, string $url): View
    {
        $page = $this->pageRepository->findByUrl($url);

        if (! $page) {
            abort(404);
        }

        $isAdmin = $request->user()?->role === 'admin';

        $status = $page->page_status;
        $isPublished = $status instanceof PageStatus
            ? $status === PageStatus::PUBLISHED
            : (int) $status === PageStatus::PUBLISHED->value;

        if (! $isPublished && ! $isAdmin) {
            abort(404);
        }

        $type = $page->page_of_type;
        $typeValue = $type instanceof PageType ? $type->value : (int) $type;

        if (! in_array($typeValue, [PageType::NEWS->value, PageType::DOCUMENT->value], true)) {
            abort(404);
        }

        return view('print.page', [
            'page' => $page,
            // Print version should look like the regular page (including images/tables).
            // We rely on admin-controlled content, same as the public Inertia pages.
            'content' => (string) ($page->content ?? ''),
        ]);
    }
}
