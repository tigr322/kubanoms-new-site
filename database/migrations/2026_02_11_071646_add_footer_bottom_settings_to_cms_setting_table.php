<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'name' => 'FOOTER_COPYRIGHT',
                'description' => 'Нижняя строка футера (копирайт)',
                'content' => '© 2009-2022. ТФОМС КК. Все права защищены.',
            ],
            [
                'name' => 'FOOTER_COUNTERS',
                'description' => 'Нижняя строка футера (счетчики)',
                'content' => '',
            ],
            [
                'name' => 'FOOTER_DEVELOPER',
                'description' => 'Нижняя строка футера (RSS и разработчик)',
                'content' => '<a href="/rss.xml">RSS-канал</a> <a href="https://mirazher.ru/ru" target="_blank" rel="noopener">За создание сайта - </a>: <a href="https://mirazher.ru/ru" target="_blank" rel="noopener">Mirazher</a>',
            ],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('cms_setting')
                ->where('name', $row['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('cms_setting')->insert([
                'name' => $row['name'],
                'description' => $row['description'],
                'content' => $row['content'],
                'visibility' => true,
                'update_user' => 'Admin User',
                'create_date' => now(),
                'create_user' => 'Admin User',
                'update_date' => now(),
                'delete_date' => null,
                'delete_user' => null,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
