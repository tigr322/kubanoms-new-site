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
        Schema::create('cms_page_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('page_id');
            $table->unsignedInteger('file_id');
            $table->string('title')->comment('Название документа для отображения');
            $table->integer('order')->default(0)->comment('Порядок отображения');
            $table->boolean('is_visible')->default(true)->comment('Видимость документа');
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('cms_page')->onDelete('cascade');
            $table->foreign('file_id')->references('id')->on('cms_file')->onDelete('cascade');
            $table->index(['page_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_page_documents');
    }
};
