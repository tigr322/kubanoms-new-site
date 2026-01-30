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
        images?: string[];
        attachments?: Array<{
            name: string;
            url: string;
        }>;
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
            <div v-if="page.images?.length" class="news-gallery">
                <h3>Фотографии</h3>
                <div class="news-gallery__grid">
                    <figure v-for="image in page.images" :key="image" class="news-gallery__item">
                        <img :src="image" :alt="page.title" loading="lazy" />
                    </figure>
                </div>
            </div>
            <div v-if="page.attachments?.length" class="news-attachments">
                <h3>Прикреплённые документы</h3>
                <ul>
                    <li v-for="file in page.attachments" :key="file.url">
                        <a :href="file.url" target="_blank" rel="noopener">{{ file.name }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </PublicLayout>
</template>
