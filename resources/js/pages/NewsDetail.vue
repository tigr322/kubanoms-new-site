<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

const props = defineProps<{
    page: {
        title: string;
        url: string;
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

const normalizeImageForCompare = (value: string): string => {
    const trimmed = value.trim();

    if (trimmed === '') {
        return '';
    }

    if (trimmed.startsWith('//')) {
        return trimmed.toLowerCase();
    }

    if (trimmed.startsWith('/')) {
        return trimmed.toLowerCase();
    }

    try {
        const parsed = new URL(trimmed);

        return `${parsed.pathname}${parsed.search}`.toLowerCase();
    } catch {
        return trimmed.toLowerCase();
    }
};

const contentImageSet = computed(() => {
    const html = props.page.content ?? '';
    const matches = html.matchAll(/<img[^>]+src=["']([^"']+)["'][^>]*>/gi);
    const sources = new Set<string>();

    for (const match of matches) {
        const src = match[1] ?? '';
        const normalized = normalizeImageForCompare(src);

        if (normalized !== '') {
            sources.add(normalized);
        }
    }

    return sources;
});

const galleryImages = computed(() =>
    (props.page.images ?? []).filter((image) => {
        const normalized = normalizeImageForCompare(image);

        if (normalized === '') {
            return false;
        }

        return !contentImageSet.value.has(normalized);
    }),
);
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
            <div class="print">
                <a :href="`/print${page.url}`" target="_blank" rel="noopener">Версия для печати</a>
            </div>
            <h1>{{ page.title }}</h1>
            <p class="date" v-if="page.publication_date">{{ page.publication_date }}</p>
            <div v-html="page.content" />
            <div v-if="galleryImages.length" class="news-gallery">
                <div class="news-gallery__grid">
                    <figure v-for="image in galleryImages" :key="image" class="news-gallery__item">
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
