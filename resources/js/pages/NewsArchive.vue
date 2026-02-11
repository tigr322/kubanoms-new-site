<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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
    image: string | null;
};

type NewsPaginator = {
    data: NewsItem[];
    meta: {
        total: number;
    };
};

const props = defineProps<{
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

const itemsPerPage = 10;
const currentPage = ref(1);

const totalPages = computed(() => {
    const totalItems = props.news.data?.length ?? 0;

    return Math.max(1, Math.ceil(totalItems / itemsPerPage));
});

const paginatedNews = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;

    return (props.news.data ?? []).slice(start, start + itemsPerPage);
});

const pages = computed(() => Array.from({ length: totalPages.value }, (_, i) => i + 1));

watch(
    () => props.news.data?.length ?? 0,
    () => {
        currentPage.value = 1;
    },
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
            <h1>{{ page.title }}</h1>

            <div v-if="page.content" v-html="page.content" />

            <template v-if="news.data?.length">
                <div v-for="item in paginatedNews" :key="item.id" class="news">
                    <div class="news-row">
                        <div v-if="item.image" class="news-preview">
                            <img :src="item.image" :alt="item.title" loading="lazy" />
                        </div>
                        <div class="news-body">
                            <div v-if="item.date" class="date">{{ item.date }}</div>
                            <h3 class="titlenews">
                                <Link :href="item.url">{{ item.title }}</Link>
                            </h3>
                            <p class="link">
                                <Link :href="item.url">Посмотреть полностью</Link>
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="totalPages > 1" class="pagination">
                    <span
                        v-for="page in pages"
                        :key="`page-${page}`"
                        class="paginate_button"
                        :class="{ active: page === currentPage }"
                    >
                        <a href="#" @click.prevent="currentPage = page">{{ page }}</a>
                    </span>
                </div>
            </template>
            <p v-else>Новости пока не загружены.</p>
        </div>
    </PublicLayout>
</template>

<style scoped>
.news {
    margin: 0 0 20px;
    padding: 0 0 16px;
    border-bottom: 1px solid #e0e4e7;
}

.news:last-of-type {
    margin-bottom: 8px;
}

.news .date {
    margin-bottom: 8px;
}

.news-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.news-preview {
    flex: 0 0 158px;
    width: 158px;
    max-width: 158px;
}

.news-preview img {
    display: block;
    width: 100%;
    height: auto;
}

.news-body {
    min-width: 0;
    flex: 1;
}

.news .titlenews {
    margin: 0 0 8px;
    line-height: 1.35;
}

.news .link {
    margin: 0;
}

.pagination {
    margin-top: 18px;
}

.pagination .paginate_button a {
    min-width: 32px;
    text-align: center;
}

@media (max-width: 640px) {
    .news-row {
        flex-direction: column;
    }

    .news-preview {
        width: 100%;
        max-width: 100%;
        flex-basis: auto;
    }
}
</style>
