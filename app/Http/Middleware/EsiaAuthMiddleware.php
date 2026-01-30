<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EsiaAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, есть ли данные пользователя от ЕСИА в сессии
        if (!Session::has('esia_user')) {
            // Если нет данных ЕСИА, редиректим на esia-mini
            $esiaMiniUrl = config('services.esia.url', 'http://localhost:3001');
            $callbackUrl = route('virtual-reception.callback');

            return redirect()->away("{$esiaMiniUrl}/auth?callback=" . urlencode($callbackUrl));
        }

        return $next($request);
    }
}
