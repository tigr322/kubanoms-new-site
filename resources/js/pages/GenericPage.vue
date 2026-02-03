<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import MenuTree from '@/components/public/MenuTree.vue';

import PublicLayout from '@/layouts/public/PublicLayout.vue';
import type { MenuItem } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    page: {
        title: string;
        content: string;
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

const currentPath = computed(() => usePage().url.split('?')[0] || '/');

const findMenuItemByUrl = (items: MenuItem[], url: string): MenuItem | null => {
    for (const item of items) {
        if (item.url === url) {
            return item;
        }

        if (item.children?.length) {
            const found = findMenuItemByUrl(item.children, url);
            if (found) {
                return found;
            }
        }
    }

    return null;
};

const currentNavbarItem = computed(() => findMenuItemByUrl(props.menus.navbar, currentPath.value));
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

            <div v-if="currentNavbarItem?.children?.length" class="section-map">
                <h2>Страницы раздела</h2>
                <MenuTree :items="currentNavbarItem.children" />
            </div>

            <div v-html="page.content" />
            <!-- NOTE: content is legacy HTML; sanitize on the backend if user-generated -->
        </div>
    </PublicLayout>
</template>

<style>
.section-map {
    margin: 16px 0 24px 0;
    padding: 16px;
    background: #f7fbff;
    border: 1px solid #d7e7f3;
    border-radius: 8px;
}

.section-map h2 {
    margin: 0 0 12px 0;
    color: #0e517e;
    font-size: 18px;
}

.section-map .sitemap-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.section-map .sitemap-item {
    margin-bottom: 10px;
    padding: 6px 0;
    border-bottom: 1px solid #d7e7f3;
}

.section-map .sitemap-link {
    color: #0e517e;
    text-decoration: none;
    font-weight: bold;
    display: block;
    padding: 2px 0;
}

.section-map .sitemap-link:hover {
    color: #08b7be;
    text-decoration: underline;
}

.section-map .sitemap-sublist {
    list-style: none;
    padding-left: 20px;
    margin: 6px 0 0 0;
}

.section-map .sitemap-subitem {
    margin-bottom: 6px;
    padding: 2px 0;
    border-bottom: none;
}

.section-map .sitemap-sublink {
    color: #2c4f6b;
    text-decoration: none;
    font-size: 14px;
    display: block;
    padding: 2px 0;
}

.section-map .sitemap-sublink:hover {
    color: #0e517e;
    text-decoration: underline;
}
</style>
