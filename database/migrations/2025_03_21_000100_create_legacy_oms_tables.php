<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Clean up only in local/testing to avoid dropping data in prod.
        if (app()->environment(['local', 'testing'])) {
            foreach ([
                'oms_vote_ans',
                'oms_vote_quest',
                'oms_srchwords',
                'oms_srchpathes',
                'oms_srchparts',
                'oms_srchobj',
                'oms_srchconjunctives',
                'oms_smo_smo',
                'oms_smo_rating',
                'oms_smo_info',
                'oms_setup',
                'oms_propstorage',
                'oms_navigation',
                'oms_media',
                'oms_imagegroupitems',
                'oms_imagegroups',
                'oms_guestbook',
                'oms_field_types',
                'oms_field_access',
                'oms_faq',
                'oms_event_items',
                'oms_event_cat',
                'oms_ctl_catalog1',
                'oms_ctltree',
                'oms_ctlcolumns',
                'oms_basket',
                'oms_anketa_resp',
                'oms_anketa_ans',
                'oms_anketa_quest',
                'oms_anketa',
                'oms_ad_target',
                'oms_ad_stat',
                'oms_ad_banners',
                'oms_ad_groups',
            ] as $table) {
                Schema::dropIfExists($table);
            }
        }

        Schema::create('oms_ad_groups', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->enum('type', ['picture', 'text', 'combo'])->default('picture');
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->unsignedInteger('default')->default(0);
            $table->char('comment', 100)->default('');
            $table->unsignedTinyInteger('txtcount')->default(0);
            $table->char('txtcomment', 100)->default('');
        });

        Schema::create('oms_ad_banners', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->unsignedInteger('group_id')->default(0);
            $table->text('text');
            $table->string('file', 15)->default('');
            $table->date('begin_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('count')->default(0);
            $table->string('url', 128)->default('');
            $table->index('group_id');
        });

        Schema::create('oms_ad_stat', function (Blueprint $table): void {
            $table->unsignedInteger('banner_id')->primary();
            $table->unsignedInteger('count')->default(0);
            $table->char('upd', 1)->default('I');
        });

        Schema::create('oms_ad_target', function (Blueprint $table): void {
            $table->unsignedInteger('banner_id');
            $table->char('path', 128)->default('');
            $table->primary(['banner_id', 'path']);
            $table->index('banner_id');
        });

        Schema::create('oms_anketa', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('caption', 200)->default('');
            $table->text('description');
            $table->integer('sort_order')->default(0);
            $table->integer('ans_count')->default(0);
            $table->string('template', 50)->default('');
        });

        Schema::create('oms_anketa_quest', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('anketa_id')->default(0);
            $table->char('caption', 200)->default('');
            $table->integer('sort_order')->default(0);
            $table->enum('type', ['radio', 'checkbox', 'rating'])->default('radio');
        });

        Schema::create('oms_anketa_ans', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('anketa_id')->default(0);
            $table->integer('quest_id')->default(0);
            $table->char('caption', 150)->default('');
            $table->enum('type', ['choice', 'text'])->default('choice');
            $table->integer('sort_order')->default(0);
            $table->integer('ans_count')->default(0);
        });

        Schema::create('oms_anketa_resp', function (Blueprint $table): void {
            $table->increments('id');
            $table->dateTime('time')->nullable();
            $table->integer('anketa_id')->default(0);
            $table->text('data');
            $table->tinyInteger('folder')->default(10);
        });

        Schema::create('oms_basket', function (Blueprint $table): void {
            $table->unsignedInteger('id')->default(0);
            $table->char('cat_id', 16)->default('');
            $table->char('cat_name', 255)->default('');
            $table->char('good_id', 16)->default('');
            $table->char('good_name', 255)->default('');
            $table->double('sort_order')->default(0);
            $table->unsignedInteger('count')->default(0);
            // MySQL DECIMAL UNSIGNED isn't supported via schema builder; using decimal with non-negative default.
            $table->decimal('price', 20, 2)->default(0);
            $table->primary(['id', 'cat_id', 'good_id']);
            $table->index('id');
        });

        Schema::create('oms_ctlcolumns', function (Blueprint $table): void {
            $table->char('ctl_id', 16)->default('');
            $table->unsignedTinyInteger('column_no')->default(0);
            $table->string('caption', 60)->default('');
            $table->enum('type', ['name', 'main', 'detail', 'description'])->default('main');
            $table->enum('srchtype', ['none', 'contchars', 'range', 'set', 'equal'])->default('contchars');
            $table->char('fieldtype', 1)->default('');
            $table->integer('fieldsize')->default(0);
            $table->tinyInteger('fieldprec')->default(0);
            $table->tinyInteger('import_col')->default(0);
            $table->primary(['ctl_id', 'column_no']);
        });

        Schema::create('oms_ctltree', function (Blueprint $table): void {
            // Composite PK (id + ctl_id) in legacy; no auto-increment to satisfy SQLite composite PK limitation.
            $table->unsignedInteger('id');
            $table->char('ctl_id', 16)->default('');
            $table->string('name', 235)->default('');
            $table->unsignedInteger('parent_id')->default(0);
            $table->integer('sort_order')->default(0);
            $table->enum('visible', ['1', '0'])->default('1');
            $table->primary(['id', 'ctl_id']);
            $table->index(['parent_id', 'name', 'ctl_id']);
            $table->index(['sort_order', 'parent_id', 'ctl_id']);
        });

        Schema::create('oms_ctl_catalog1', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->unsignedInteger('parent_id')->default(0);
            $table->string('f0', 60)->nullable();
            $table->decimal('f1', 9, 2)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->enum('visible', ['0', '1'])->default('1');
            $table->timestamp('modifytime')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('createtime')->nullable();
            $table->index(['sort_order', 'parent_id']);
            $table->index('f0');
            $table->index('f1');
            $table->index('parent_id');
        });

        Schema::create('oms_event_cat', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->char('branch_id', 16)->default('');
            $table->integer('parent_id')->default(0);
            $table->char('caption', 100)->default('');
            $table->integer('sort_order')->default(0);
        });

        Schema::create('oms_event_items', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->string('branch_id', 16)->default('');
            $table->string('cat_ids', 100)->default('');
            $table->date('date')->nullable();
            $table->date('date2')->nullable();
            $table->string('caption', 250)->default('');
            $table->text('text');
            $table->string('author', 250)->default('');
        });

        Schema::create('oms_faq', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('category', 100)->default('');
            $table->text('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('oms_field_access', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->char('class', 32)->default('');
            $table->char('field_name', 32)->default('');
            $table->unsignedInteger('group_id')->default(0);
            $table->char('caption', 100)->default('');
            $table->unique(['class', 'field_name', 'group_id'], 'cfg');
        });

        Schema::create('oms_field_types', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->string('class', 32)->default('');
            $table->string('field_name', 32)->default('');
            $table->enum('field_type', ['text', 'password', 'email', 'integer', 'float', 'money', 'enum', 'checkbox', 'identifier'])->default('text');
            $table->unsignedInteger('length')->default(0);
            $table->text('params')->nullable();
            $table->enum('required', ['0', '1'])->default('1');
            $table->unique(['class', 'field_name'], 'cf');
        });

        Schema::create('oms_guestbook', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('branch_id', 16)->default('');
            $table->dateTime('time')->nullable();
            $table->string('nick', 15)->default('');
            $table->string('email', 25)->default('');
            $table->string('title', 100)->default('');
            $table->text('text');
            $table->dateTime('r_time')->nullable();
            $table->string('r_nick', 15)->default('');
            $table->string('r_email', 25)->default('');
            $table->text('r_text');
        });

        Schema::create('oms_imagegroups', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->integer('fix_width')->default(0);
            $table->integer('fix_height')->default(0);
            $table->unsignedTinyInteger('columns')->default(0);
        });

        Schema::create('oms_imagegroupitems', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->unsignedInteger('group_id')->default(0);
            $table->char('small_src', 150)->default('');
            $table->char('big_src', 150)->default('');
            $table->char('small_text', 254)->default('');
            $table->char('big_text', 254)->default('');
            $table->char('anchor', 50)->default('');
            $table->char('anchor_txt', 50)->default('');
            $table->index('group_id', 'gid');
        });

        Schema::create('oms_media', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('filetype', 5)->default('');
            $table->string('filename', 100)->default('');
            $table->string('picname', 100)->default('');
            $table->integer('width')->default(0);
            $table->integer('height')->default(0);
            $table->string('text', 100)->default('');
        });

        Schema::create('oms_navigation', function (Blueprint $table): void {
            $table->string('id', 16)->primary();
            $table->string('parent_id', 16)->default('');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('title');
            $table->text('caption');
            $table->text('description');
            $table->string('keywords', 255)->default('');
            $table->string('b_title', 255)->default('');
            $table->text('b_descr');
            $table->enum('visible', ['0', '1'])->default('1');
            $table->enum('type', ['node', 'branch', 'href'])->default('node');
            $table->index(['sort_order', 'parent_id']);
            $table->index('parent_id');
            $table->index('type');
        });

        Schema::create('oms_propstorage', function (Blueprint $table): void {
            $table->string('object', 60)->default('');
            $table->string('template', 16)->default('');
            $table->string('property', 250)->default('');
            $table->string('value', 255)->default('');
            $table->primary(['object', 'template', 'property']);
        });

        Schema::create('oms_setup', function (Blueprint $table): void {
            $table->string('id', 16)->primary()->default('');
            $table->text('value');
            $table->string('caption', 100)->default('');
            $table->char('type', 1)->default('C');
        });

        Schema::create('oms_smo_info', function (Blueprint $table): void {
            $table->increments('id');
            $table->date('period')->nullable();
            $table->integer('smo')->default(0);
            $table->text('data');
            $table->unique(['period', 'smo'], 'period');
        });

        Schema::create('oms_smo_rating', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('smo')->default(0);
            $table->string('anketa', 250)->default('');
            $table->integer('rating')->default(0);
            $table->dateTime('create_date')->nullable();
        });

        Schema::create('oms_smo_smo', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 250)->default('');
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('active')->default(1);
        });

        Schema::create('oms_srchconjunctives', function (Blueprint $table): void {
            $table->char('word', 3)->primary()->default('');
        });

        Schema::create('oms_srchobj', function (Blueprint $table): void {
            $table->integer('id')->default(0)->primary();
            $table->string('url', 100)->default('');
            $table->string('path', 200)->default('');
            $table->string('class', 30)->default('');
            $table->string('parent', 100)->default('');
            $table->string('caption', 250)->default('');
            $table->text('description');
        });

        Schema::create('oms_srchparts', function (Blueprint $table): void {
            $table->string('path', 255)->primary()->default('');
            $table->string('title', 64)->default('');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->unique('sort_order');
        });

        Schema::create('oms_srchpathes', function (Blueprint $table): void {
            $table->integer('path_id')->default(0)->primary();
            $table->string('path', 255)->default('');
        });

        Schema::create('oms_srchwords', function (Blueprint $table): void {
            $table->integer('path_id')->default(0);
            $table->integer('word_id')->default(0);
            $table->char('info_id', 16)->default('0');
            $table->unsignedSmallInteger('importance')->default(0);
            $table->primary(['path_id', 'word_id', 'info_id']);
        });

        Schema::create('oms_vote_quest', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->text('question');
            $table->enum('check', ['0', '1'])->default('0');
            $table->unsignedInteger('kol')->default(0);
        });

        Schema::create('oms_vote_ans', function (Blueprint $table): void {
            $table->increments('id')->unsigned();
            $table->unsignedInteger('quest_id')->default(0);
            $table->char('answer', 200)->default('');
            $table->unsignedInteger('kol')->default(0);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('oms_vote_ans');
        Schema::dropIfExists('oms_vote_quest');
        Schema::dropIfExists('oms_srchwords');
        Schema::dropIfExists('oms_srchpathes');
        Schema::dropIfExists('oms_srchparts');
        Schema::dropIfExists('oms_srchobj');
        Schema::dropIfExists('oms_srchconjunctives');
        Schema::dropIfExists('oms_smo_smo');
        Schema::dropIfExists('oms_smo_rating');
        Schema::dropIfExists('oms_smo_info');
        Schema::dropIfExists('oms_setup');
        Schema::dropIfExists('oms_propstorage');
        Schema::dropIfExists('oms_navigation');
        Schema::dropIfExists('oms_media');
        Schema::dropIfExists('oms_imagegroupitems');
        Schema::dropIfExists('oms_imagegroups');
        Schema::dropIfExists('oms_guestbook');
        Schema::dropIfExists('oms_field_types');
        Schema::dropIfExists('oms_field_access');
        Schema::dropIfExists('oms_faq');
        Schema::dropIfExists('oms_event_items');
        Schema::dropIfExists('oms_event_cat');
        Schema::dropIfExists('oms_ctl_catalog1');
        Schema::dropIfExists('oms_ctltree');
        Schema::dropIfExists('oms_ctlcolumns');
        Schema::dropIfExists('oms_basket');
        Schema::dropIfExists('oms_anketa_resp');
        Schema::dropIfExists('oms_anketa_ans');
        Schema::dropIfExists('oms_anketa_quest');
        Schema::dropIfExists('oms_anketa');
        Schema::dropIfExists('oms_ad_target');
        Schema::dropIfExists('oms_ad_stat');
        Schema::dropIfExists('oms_ad_banners');
        Schema::dropIfExists('oms_ad_groups');
    }
};
