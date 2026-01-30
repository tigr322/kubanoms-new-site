<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем страницу для карты сайта
        DB::table('cms_page')->insert([
            'parent_id' => null,
            'title' => 'Карта сайта',
            'title_short' => 'Карта сайта',
            'meta_description' => 'Карта сайта ТФОМС Краснодарского края',
            'meta_keywords' => 'карта сайта, ТФОМС, Краснодарский край',
            'publication_date' => now(),
            'content' => '<h1>Карта сайта</h1><p>Загрузка...</p>',
            'page_status' => 3, // опубликована
            'page_of_type' => 7, // специальный тип для карты сайта
            'update_user' => 'system',
            'create_user' => 'system',
            'update_date' => now(),
            'create_date' => now(),
            'url' => '/sitemap',
            'path' => null,
            'template' => 'sitemap'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cms_page')->where('url', '/sitemap')->delete();
    }
};
