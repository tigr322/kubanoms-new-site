<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/public/Breadcrumbs.vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

type MenuItem = {
    id: number;
    title: string;
    url: string;
    children: MenuItem[];
};

type SitemapItem = {
    page: {
        id: number;
        title: string;
        title_short?: string;
        url: string;
    };
    url: string;
    title: string;
    items: SitemapItem[];
};

const props = defineProps<{
    link_list: SitemapItem[];
    menus: {
        navbar: MenuItem[];
        sidebar: MenuItem[];
        current_information?: MenuItem[];
    };
    special?: number | string;
    settings?: Record<string, unknown>;
}>();

// Отладочный лог
console.log('Sitemap props:', props);
console.log('link_list length:', props.link_list?.length);
console.log('menus keys:', props.menus ? Object.keys(props.menus) : 'no menus');
console.log('settings:', props.settings);

// Рекурсивная функция для рендеринга вложенных элементов
const renderSitemapItems = (items: SitemapItem[], level: number = 0) => {
    return items.map(item => ({
        ...item,
        hasChildren: item.items && item.items.length > 0
    }));
};
</script>

<template>
    <PublicLayout
        title="Карта сайта"
        :menus="menus"
        :special="special"
        :settings="settings as any"
    >
        <Head title="Карта сайта" />
        <div class="content">
            <Breadcrumbs :items="[{ title: 'Карта сайта', url: undefined }]" />
            <h1>Карта сайта</h1>

            <div class="item-container">
                <div>DEBUG: link_list length = {{ link_list?.length }}</div>
                <div>DEBUG: menus = {{ menus ? 'YES' : 'NO' }}</div>
                <div>DEBUG: settings = {{ settings ? 'YES' : 'NO' }}</div>

                <ul class="sitemap-list">
                    <li v-for="item in link_list" :key="item.page.id" class="sitemap-item">
                        <a :href="item.url" class="sitemap-link">
                            {{ item.title }}
                        </a>

                        <!-- Вложенные элементы -->
                        <ul v-if="item.items && item.items.length > 0" class="sitemap-sublist">
                            <li v-for="subItem in item.items" :key="subItem.page.id" class="sitemap-subitem">
                                <a :href="subItem.url" class="sitemap-sublink">
                                    {{ subItem.title }}
                                </a>

                                <!-- Вложенные элементы второго уровня -->
                                <ul v-if="subItem.items && subItem.items.length > 0" class="sitemap-sublist">
                                    <li v-for="subSubItem in subItem.items" :key="subSubItem.page.id" class="sitemap-subitem">
                                        <a :href="subSubItem.url" class="sitemap-sublink">
                                            {{ subSubItem.title }}
                                        </a>

                                        <!-- Вложенные элементы третьего уровня -->
                                        <ul v-if="subSubItem.items && subSubItem.items.length > 0" class="sitemap-sublist">
                                            <li v-for="deepItem in subSubItem.items" :key="deepItem.page.id" class="sitemap-subitem">
                                                <a :href="deepItem.url" class="sitemap-sublink">
                                                    {{ deepItem.title }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </PublicLayout>
</template>

<style>
/* Глобальные стили для карты сайта */
.content {
    background-color: white;
    min-height: 100vh;
    padding: 20px;
}

.sitemap-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sitemap-item {
    margin-bottom: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #e0e4e7;
}

.sitemap-link {
    color: #0e517e;
    text-decoration: none;
    font-size: 16px;
    font-weight: bold;
    display: block;
    padding: 5px 0;
}

.sitemap-link:hover {
    color: #08b7be;
    text-decoration: underline;
}

.sitemap-sublist {
    list-style: none;
    padding-left: 30px;
    margin: 10px 0 0 0;
}

.sitemap-subitem {
    margin-bottom: 8px;
    padding: 3px 0;
}

.sitemap-sublink {
    color: #2c4f6b;
    text-decoration: none;
    font-size: 14px;
    display: block;
    padding: 3px 0;
}

.sitemap-sublink:hover {
    color: #0e517e;
    text-decoration: underline;
}

.sitemap-sublist .sitemap-sublist {
    padding-left: 20px;
    margin-top: 5px;
}

.sitemap-sublist .sitemap-sublink {
    font-size: 13px;
    color: #555;
}

.breadcrumbs {
    margin-bottom: 20px;
}

.breadcrumbs ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    align-items: center;
}

.breadcrumbs li {
    margin-right: 10px;
}

.breadcrumbs li a {
    color: #0e517e;
    text-decoration: none;
}

.breadcrumbs li a:hover {
    text-decoration: underline;
}

.breadcrumbs li:not(:last-child)::after {
    content: '›';
    margin-left: 10px;
    color: #666;
}

h1 {
    color: #0e517e;
    margin-bottom: 20px;
    font-size: 24px;
}

.item-container {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
