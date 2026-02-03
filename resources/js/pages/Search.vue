<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

const props = defineProps<{
    query: string;
    results: Array<{
        id: string;
        title: string;
        url: string;
        excerpt: string;
    }>;
    menus: {
        navbar: MenuItem[];
        sidebar: MenuItem[];
        current_information?: MenuItem[];
    };
    special?: number | string;
    settings?: {
        left_sidebar_banners?: string | null;
        right_sidebar_banners?: string | null;
        right_sidebar_menu?: string | null;
        bottom_banners?: string | null;
        map?: string | null;
        external_links?: string | null;
        footer_left?: string | null;
        footer_center?: string | null;
        footer_right?: string | null;
    };
}>();

const searchTerm = ref(props.query ?? '');

const submit = (): void => {
    router.visit('/search', {
        method: 'get',
        data: { q: searchTerm.value },
        preserveState: true,
    });
};
</script>

<template>
    <PublicLayout title="Поиск" :menus="menus" :special="special" :settings="settings">
        <Head title="Поиск" />
        <div class="content">
            <h1>Поиск</h1>
            <form class="mb-4" @submit.prevent="submit">
                <input v-model="searchTerm" type="text" name="q" class="form-control" placeholder="Введите запрос" />
                <button type="submit" class="btn btn-primary" style="margin-top: 10px">Найти</button>
            </form>
            <div v-if="results.length">
                <div v-for="item in results" :key="item.id" class="news">
                    <h3 class="titlenews">
                        <Link :href="item.url">{{ item.title }}</Link>
                    </h3>
                    <p>{{ item.excerpt }}</p>
                </div>
            </div>
            <p v-else>Ничего не найдено.</p>
        </div>
    </PublicLayout>
</template>
