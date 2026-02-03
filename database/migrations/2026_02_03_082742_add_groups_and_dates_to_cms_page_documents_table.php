<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cms_page_documents', function (Blueprint $table) {
            $table->string('group_title')
                ->nullable()
                ->after('title')
                ->comment('Название таблицы/группы для группировки документов на странице');

            $table->date('document_date')
                ->nullable()
                ->after('group_title')
                ->comment('Дата документа для отображения в таблице');

            $table->index(['page_id', 'group_title', 'order'], 'cms_page_documents_page_group_order_index');
            $table->index(['page_id', 'group_title', 'document_date'], 'cms_page_documents_page_group_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_page_documents', function (Blueprint $table) {
            $table->dropIndex('cms_page_documents_page_group_order_index');
            $table->dropIndex('cms_page_documents_page_group_date_index');
            $table->dropColumn(['group_title', 'document_date']);
        });
    }
};
