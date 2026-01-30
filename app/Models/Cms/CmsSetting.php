<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Model;

class CmsSetting extends Model
{
    protected $table = 'cms_setting';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * Получить контактные данные
     */
    public static function getContacts(): array
    {
        $contacts = self::whereIn('name', [
            'contact_phone',
            'contact_email',
            'contact_address',
            'contact_work_time'
        ])
        ->where('visibility', 1)
        ->pluck('content', 'name')
        ->toArray();

        return [
            'phone' => $contacts['contact_phone'] ?? null,
            'email' => $contacts['contact_email'] ?? null,
            'address' => $contacts['contact_address'] ?? null,
            'work_time' => $contacts['contact_work_time'] ?? null,
        ];
    }
}
