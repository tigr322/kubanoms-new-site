<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

type NewsItem = {
    id: number;
    title: string;
    url: string;
    date: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type NewsPaginator = {
    data: NewsItem[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        per_page: number;
        total: number;
    };
};

defineProps<{
    page: {
        title: string;
        url: string;
        content: string;
        meta_description?: string | null;
        meta_keywords?: string | null;
    };
    news: NewsPaginator;
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

            <div v-if="page.content" v-html="page.content" />

            <template v-if="news.data?.length">
                <div v-for="item in news.data" :key="item.id" class="news">
                    <div v-if="item.date" class="date">{{ item.date }}</div>
                    <h3 class="titlenews">
                        <Link :href="item.url">{{ item.title }}</Link>
                    </h3>
                    <p class="link">
                        <Link :href="item.url">Посмотреть полностью</Link>
                    </p>
                </div>

                <div v-if="news.links?.length" class="pagination">
                    <span
                        v-for="link in news.links"
                        :key="`${link.label}-${link.url ?? ''}`"
                        class="paginate_button"
                        :class="{ active: link.active }"
                    >
                        <Link v-if="link.url" :href="link.url" v-html="link.label" />
                        <span v-else v-html="link.label" />
                    </span>
                </div>
            </template>
            <p v-else>Новости пока не загружены.</p>
        </div>
    </PublicLayout>
</template>
