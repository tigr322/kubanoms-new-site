<?php

namespace App\Http\Controllers\Oms;

use App\Http\Controllers\Controller;
use App\Http\Requests\VirtualReceptionRequest;
use App\Services\PageResolverService;
use App\Services\VirtualReceptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VirtualReceptionController extends Controller
{
    public function __construct(
        private readonly VirtualReceptionService $service,
        private readonly PageResolverService $pageResolverService,
    ) {}

    public function create(Request $request): Response
    {
        return Inertia::render('VirtualReception', [
            'title' => 'Виртуальная приёмная',
            ...$this->pageResolverService->layout(),
            'special' => $request->cookie('special', 0),
        ]);
    }

    public function store(VirtualReceptionRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Ваше обращение отправлено.');
    }
}
