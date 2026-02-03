<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Oms\VirtualReceptionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VirtualReceptionController as NewVirtualReceptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/dashboard', function () {
    return redirect()->route('filament.admin.pages.dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/rss.xml', [RssController::class, 'index'])->name('rss');
Route::get('/sitemap', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap.html', [SitemapController::class, 'index']);
Route::get('/print/{url}', [PrintController::class, 'show'])
    ->where('url', '.*')
    ->name('print');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/branches', [BranchController::class, 'index'])->name('branches');

// Старая виртуальная приемная (оставляем для совместимости)
Route::get('/virtual-reception', [VirtualReceptionController::class, 'create'])->name('vr.form');
Route::post('/virtual-reception', [VirtualReceptionController::class, 'store'])
    ->middleware(['auth'])
    ->name('vr.store');

// Новая виртуальная приемная с ЕСИА авторизацией
Route::prefix('virtual-reception')->name('virtual-reception.')->group(function () {
    Route::get('/', [NewVirtualReceptionController::class, 'index'])
        ->middleware(['esia.auth'])
        ->name('index');

    Route::get('/callback', [NewVirtualReceptionController::class, 'callback'])
        ->name('callback');

    Route::post('/', [NewVirtualReceptionController::class, 'store'])
        ->name('store');
});

// Filament панель обрабатывает /admin; старые заглушки удалены.

$reserved = implode('|', ['admin', 'api', 'rss\\.xml', 'search', 'branches', 'virtual-reception', 'sitemap', 'print']);

Route::get('/{url}', [PageController::class, 'show'])
    ->where('url', '^(?!('.$reserved.'))[A-Za-z0-9\\-\\/_\\.]+$')
    ->name('page.show');

Route::fallback(fn () => Inertia::render('Errors/NotFound'))->name('fallback');

require __DIR__.'/settings.php';
