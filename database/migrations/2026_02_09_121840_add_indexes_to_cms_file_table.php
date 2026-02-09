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
        Schema::table('cms_file', function (Blueprint $table) {
            $table->index('path', 'cms_file_path_idx');
            $table->index('original_name', 'cms_file_original_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_file', function (Blueprint $table) {
            $table->dropIndex('cms_file_path_idx');
            $table->dropIndex('cms_file_original_name_idx');
        });
    }
};
