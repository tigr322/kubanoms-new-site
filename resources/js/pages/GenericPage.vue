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

const normalizeUrl = (value: string | null | undefined): string => {
    const raw = (value ?? '').trim();

    if (raw === '') {
        return '';
    }

    if (raw.startsWith('/')) {
        return raw.toLowerCase();
    }

    if (raw.startsWith('//')) {
        try {
            const parsed = new URL(`https:${raw}`);

            return `${parsed.pathname}${parsed.search}`.toLowerCase();
        } catch {
            return raw.toLowerCase();
        }
    }

    try {
        const parsed = new URL(raw);

        return `${parsed.pathname}${parsed.search}`.toLowerCase();
    } catch {
        return raw.toLowerCase();
    }
};

const normalizeText = (value: string): string =>
    value
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

const buildMenuDedupKey = (item: MenuItem): string => {
    const normalizedUrl = normalizeUrl(item.url);
    const normalizedTitle = normalizeText(item.title);

    return `${normalizedUrl}|${normalizedTitle}`;
};

const deduplicateMenuItems = (items: MenuItem[]): MenuItem[] => {
    const groupedItems = new Map<string, MenuItem>();

    for (const item of items) {
        const deduplicatedChildren = deduplicateMenuItems(item.children ?? []);
        const key = buildMenuDedupKey(item);
        const existingItem = groupedItems.get(key);

        if (!existingItem) {
            groupedItems.set(key, {
                ...item,
                children: deduplicatedChildren,
            });
            continue;
        }

        existingItem.children = deduplicateMenuItems([
            ...existingItem.children,
            ...deduplicatedChildren,
        ]);
    }

    return Array.from(groupedItems.values());
};

const currentNavbarItem = computed(() => findMenuItemByUrl(props.menus.navbar, currentPath.value));
const currentNavbarChildren = computed(() =>
    deduplicateMenuItems(currentNavbarItem.value?.children ?? []),
);

const contentAnchorLinks = computed(() => {
    const html = props.page.content ?? '';
    const matches = html.matchAll(/<a[^>]+href=["']([^"']+)["'][^>]*>([\s\S]*?)<\/a>/gi);
    const links: Array<{ url: string; title: string }> = [];

    for (const match of matches) {
        links.push({
            url: normalizeUrl(match[1] ?? ''),
            title: normalizeText(match[2] ?? ''),
        });
    }

    return links;
});

const isSectionMapDuplicatedByContent = computed(() => {
    if (currentNavbarChildren.value.length === 0) {
        return false;
    }

    const sectionLinks = currentNavbarChildren.value
        .filter((item) => !!item.url)
        .map((item) => ({
            url: normalizeUrl(item.url),
            title: normalizeText(item.title),
        }));

    if (sectionLinks.length === 0) {
        return false;
    }

    return sectionLinks.every((sectionItem) =>
        contentAnchorLinks.value.some(
            (contentLink) =>
                contentLink.url !== '' &&
                contentLink.url === sectionItem.url &&
                contentLink.title !== '' &&
                contentLink.title === sectionItem.title,
        ),
    );
});
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

            <div v-if="currentNavbarChildren.length && !isSectionMapDuplicatedByContent" class="section-map">
                <h2>Страницы раздела</h2>
                <MenuTree :items="currentNavbarChildren" />
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
