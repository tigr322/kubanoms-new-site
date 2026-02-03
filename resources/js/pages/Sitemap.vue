<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import MenuTree from '@/components/public/MenuTree.vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import type { MenuItem } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    page?: {
        title?: string;
        meta_description?: string | null;
        meta_keywords?: string | null;
    };
    menus: {
        navbar: MenuItem[];
        sidebar: MenuItem[];
        current_information?: MenuItem[];
    };
    special?: number | string;
    settings?: Record<string, unknown>;
}>();

const pageTitle = computed(() => props.page?.title ?? 'Карта сайта');
const metaDescription = computed(() => props.page?.meta_description ?? null);
const metaKeywords = computed(() => props.page?.meta_keywords ?? null);
</script>

<template>
    <PublicLayout
        :title="pageTitle"
        :meta_description="metaDescription"
        :meta_keywords="metaKeywords"
        :menus="menus"
        :special="special"
        :settings="settings as any"
    >
        <Head :title="pageTitle" />

        <div class="content sitemap-page">
            <Breadcrumbs :items="[{ title: pageTitle, url: '/sitemap' }]" />
            <h1>{{ pageTitle }}</h1>

            <div class="item-container">
                <section v-if="menus.navbar?.length" class="sitemap-section">
                    <h2>Основное меню</h2>
                    <MenuTree :items="menus.navbar" />
                </section>

                <section v-if="menus.sidebar?.length" class="sitemap-section">
                    <h2>Разделы</h2>
                    <MenuTree :items="menus.sidebar" />
                </section>

                <section v-if="menus.current_information?.length" class="sitemap-section">
                    <h2>Актуальная информация</h2>
                    <MenuTree :items="menus.current_information" />
                </section>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
/* Стили для карты сайта (не трогаем глобальный .content из legacy CSS) */
.sitemap-page .item-container {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.sitemap-page .sitemap-section + .sitemap-section {
    margin-top: 24px;
}

.sitemap-page .sitemap-section h2 {
    color: #0e517e;
    margin: 0 0 12px 0;
    font-size: 18px;
}

.sitemap-page .sitemap-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sitemap-page .sitemap-item {
    margin-bottom: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #e0e4e7;
}

.sitemap-page .sitemap-link {
    color: #0e517e;
    text-decoration: none;
    font-size: 16px;
    font-weight: bold;
    display: block;
    padding: 4px 0;
}

.sitemap-page .sitemap-link:hover {
    color: #08b7be;
    text-decoration: underline;
}

.sitemap-page .sitemap-sublist {
    list-style: none;
    padding-left: 24px;
    margin: 8px 0 0 0;
}

.sitemap-page .sitemap-subitem {
    margin-bottom: 6px;
    padding: 2px 0;
}

.sitemap-page .sitemap-sublink {
    color: #2c4f6b;
    text-decoration: none;
    font-size: 14px;
    display: block;
    padding: 2px 0;
}

.sitemap-page .sitemap-sublink:hover {
    color: #0e517e;
    text-decoration: underline;
}

.sitemap-page h1 {
    color: #0e517e;
    margin: 0 0 16px 0;
    font-size: 24px;
}
</style>
