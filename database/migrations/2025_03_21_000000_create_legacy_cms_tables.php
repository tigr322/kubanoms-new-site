<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Idempotent cleanup only in local/testing to avoid dropping data in prod.
        if (app()->environment(['local', 'testing'])) {
            foreach ([
                'orm_routes',
                'oms_virtual_reception_treatment',
                'oms_virtual_reception_attachment',
                'oms_virtual_reception',
                'oms_treatment',
                'oms_smo',
                'oms_notification_smo_output',
                'oms_notification_smo_included',
                'oms_notification_smo_change',
                'oms_notification_mo_included',
                'cms_setting',
                'cms_menu_item',
                'cms_page',
                'cms_menu',
                'cms_file',
                'cms_file_folder',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        }

        Schema::create('cms_file_folder', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->string('update_user')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
        });

        Schema::create('cms_file', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('file_folder_id')->nullable();
            $table->string('original_name');
            $table->string('path')->nullable();
            $table->string('mime_type', 25);
            $table->string('extension');
            $table->string('description', 1024)->nullable();
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->string('update_user')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
            $table->index('file_folder_id');
            $table->foreign('file_folder_id')->references('id')->on('cms_file_folder')->nullOnDelete();
        });

        Schema::create('cms_menu', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->longText('description')->nullable();
            $table->integer('max_depth');
            $table->string('update_user')->nullable();
            $table->string('title');
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
        });

        Schema::create('cms_page', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('title');
            $table->string('title_short')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->dateTime('publication_date')->nullable();
            $table->longText('content')->nullable();
            $table->integer('page_status');
            $table->integer('page_of_type');
            $table->string('update_user')->nullable();
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
            $table->string('url')->nullable();
            $table->string('path')->nullable();
            $table->string('template')->nullable();
            $table->index('url');
            $table->index('parent_id');
            $table->foreign('parent_id')->references('id')->on('cms_page')->nullOnDelete();
        });

        Schema::create('cms_menu_item', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('page_id')->nullable();
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('menu_id')->nullable();
            $table->integer('type')->nullable();
            $table->string('url', 1024)->nullable();
            $table->decimal('sort_order', 18, 3)->nullable();
            $table->boolean('visible')->default(true);
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->string('update_user')->nullable();
            $table->string('title')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
            $table->index('parent_id');
            $table->index('page_id');
            $table->index('menu_id');
            $table->foreign('menu_id')->references('id')->on('cms_menu')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('cms_menu_item')->nullOnDelete();
            $table->foreign('page_id')->references('id')->on('cms_page')->nullOnDelete();
        });

        Schema::create('cms_setting', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('visibility');
            $table->string('update_user')->nullable();
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
        });

        Schema::create('oms_notification_mo_included', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('org_full_name')->nullable();
            $table->string('fio')->nullable();
            $table->string('org_short_name')->nullable();
            $table->string('org_address')->nullable();
            $table->string('persona_address')->nullable();
            $table->string('kpp')->nullable();
            $table->string('inn')->nullable();
            $table->string('org_form')->nullable();
            $table->string('org_director_data')->nullable();
            $table->string('org_medical_practice')->nullable();
            $table->string('org_document_medical')->nullable();
            $table->longText('org_help_form')->nullable();
            $table->longText('org_capacity_hospital_beds')->nullable();
            $table->longText('org_capacity_med')->nullable();
            $table->longText('org_fact_prev_year')->nullable();
            $table->longText('org_number_people')->nullable();
            $table->longText('org_offers')->nullable();
            $table->string('signature')->nullable();
        });

        Schema::create('oms_notification_smo_change', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('representative_surname', 1024)->nullable();
            $table->string('representative_name', 1024)->nullable();
            $table->string('representative_patronymic', 1024)->nullable();
            $table->smallInteger('representative_attitude')->nullable();
            $table->string('representative_document_type', 1024)->nullable();
            $table->string('representative_document_series', 1024)->nullable();
            $table->string('representative_document_number', 1024)->nullable();
            $table->date('representative_document_date')->nullable();
            $table->string('representative_phone_official')->nullable();
            $table->string('representative_phone_home')->nullable();
        });

        Schema::create('oms_notification_smo_included', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('update_user')->nullable();
            $table->string('full_short_name')->nullable();
            $table->string('branch_full_name')->nullable();
            $table->string('address')->nullable();
            $table->string('branch_address')->nullable();
            $table->string('kpp')->nullable();
            $table->string('inn')->nullable();
            $table->string('org_form')->nullable();
            $table->string('fio_director')->nullable();
            $table->string('branch_fio_director')->nullable();
            $table->string('license_information')->nullable();
            $table->string('amount_insured')->nullable();
            $table->string('director')->nullable();
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
        });

        Schema::create('oms_notification_smo_output', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('update_user')->nullable();
            $table->string('smo_full_name', 1024)->nullable();
            $table->dateTime('exclude_date')->nullable();
            $table->string('exclude_reason', 1024)->nullable();
            $table->string('director', 1024)->nullable();
            $table->dateTime('application_date')->nullable();
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
        });

        Schema::create('oms_smo', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('code', 25);
            $table->string('name_full');
            $table->string('name_short');
            $table->string('ogrn', 13);
        });

        Schema::create('oms_treatment', function (Blueprint $table): void {
            $table->increments('id');
            $table->dateTime('time');
            $table->string('nick', 15);
            $table->string('email', 25);
            $table->string('title', 100);
            $table->longText('text');
            $table->dateTime('r_time');
            $table->string('r_nick', 15);
            $table->string('r_email', 25);
            $table->longText('r_text');
        });

        Schema::create('oms_virtual_reception', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('update_user')->nullable();
            $table->string('fio')->nullable();
            $table->dateTime('birthdate')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('post_address')->nullable();
            $table->string('phone')->nullable();
            $table->longText('contents')->nullable();
            $table->integer('status')->nullable();
            $table->boolean('only_email')->default(false);
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
        });

        Schema::create('oms_virtual_reception_attachment', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('virtual_reception_id')->nullable();
            $table->string('update_user')->nullable();
            $table->string('path')->nullable();
            $table->dateTime('create_date')->nullable();
            $table->string('create_user')->nullable();
            $table->dateTime('update_date')->nullable();
            $table->dateTime('delete_date')->nullable();
            $table->string('delete_user')->nullable();
            $table->index('virtual_reception_id');
            $table->foreign('virtual_reception_id')->references('id')->on('oms_virtual_reception')->nullOnDelete();
        });

        Schema::create('oms_virtual_reception_treatment', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('fio');
            $table->string('address');
            $table->string('email');
            $table->string('post_address');
            $table->string('phone');
            $table->longText('contents');
        });

        Schema::create('orm_routes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('host');
            $table->longText('schemes');
            $table->longText('methods');
            $table->longText('defaults');
            $table->longText('requirements');
            $table->longText('options');
            $table->string('condition_expr')->nullable();
            $table->string('variable_pattern')->nullable();
            $table->string('staticPrefix')->nullable();
            $table->string('name');
            $table->integer('position');
            $table->unique('name');
            $table->index('name', 'name_idx');
            $table->index('staticPrefix', 'prefix_idx');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('orm_routes');
        Schema::dropIfExists('oms_virtual_reception_treatment');
        Schema::dropIfExists('oms_virtual_reception_attachment');
        Schema::dropIfExists('oms_virtual_reception');
        Schema::dropIfExists('oms_treatment');
        Schema::dropIfExists('oms_smo');
        Schema::dropIfExists('oms_notification_smo_output');
        Schema::dropIfExists('oms_notification_smo_included');
        Schema::dropIfExists('oms_notification_smo_change');
        Schema::dropIfExists('oms_notification_mo_included');
        Schema::dropIfExists('cms_setting');
        Schema::dropIfExists('cms_menu_item');
        Schema::dropIfExists('cms_page');
        Schema::dropIfExists('cms_menu');
        Schema::dropIfExists('cms_file');
        Schema::dropIfExists('cms_file_folder');
    }
};
