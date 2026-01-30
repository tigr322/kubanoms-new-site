<?php

namespace App\Http\Controllers\Admin\Oms;

use App\Http\Controllers\Controller;
use App\Models\Oms\OmsAdBanner;
use App\Models\Oms\OmsAnketa;
use App\Models\Oms\OmsFaq;
use App\Models\Oms\OmsGuestbook;
use App\Models\Oms\OmsMedia;
use Illuminate\Http\JsonResponse;

class LegacyOmsReadController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'counts' => [
                'ads' => OmsAdBanner::count(),
                'anketa' => OmsAnketa::count(),
                'faq' => OmsFaq::count(),
                'guestbook' => OmsGuestbook::count(),
                'media' => OmsMedia::count(),
            ],
            'message' => 'Read-only просмотр legacy OMS данных (подлежит уточнению)',
        ]);
    }
}
