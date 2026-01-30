<?php

namespace App\Services;

use App\Models\Oms\OmsVirtualReception;

class VirtualReceptionService
{
    public function create(array $data): OmsVirtualReception
    {
        return OmsVirtualReception::query()->create([
            'fio' => $data['fio'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'contents' => $data['contents'] ?? null,
            'only_email' => (bool) ($data['only_email'] ?? false),
            'create_date' => now(),
        ]);
    }
}
