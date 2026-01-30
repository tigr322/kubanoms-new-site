<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

defineProps<{
    page: {
        title: string;
        content: string;
        meta_description?: string | null;
        meta_keywords?: string | null;
        publication_date?: string | null;
        path?: string | null;
    };
    menus: {
        navbar: MenuItem[];
        sidebar: MenuItem[];
        current_information?: MenuItem[];
    };
    special?: number | string;
    settings?: Record<string, unknown>;
}>();
</script>

<template>
    <PublicLayout
        :title="page.title"
        :meta_description="page.meta_description"
        :meta_keywords="page.meta_keywords"
        :menus="menus"
        :special="special"
        :settings="settings as any"
    >
        <Head :title="page.title" />
        <div class="content">
            <Breadcrumbs :items="[]" />
            <h1>{{ page.title }}</h1>
            <p class="date" v-if="page.publication_date">{{ page.publication_date }}</p>
            <div v-html="page.content" />
            <p v-if="page.path">
                <a :href="page.path" target="_blank" rel="noopener">Скачать документ</a>
            </p>
        </div>
    </PublicLayout>
</template>
