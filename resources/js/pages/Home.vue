<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TabsLatest from '@/components/public/TabsLatest.vue';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import type { MenuItem, NewsListItem } from '@/types';

defineProps<{
    page: {
        title: string;
        meta_description?: string | null;
        meta_keywords?: string | null;
    };
    menus: {
        navbar: MenuItem[];
        sidebar: MenuItem[];
        current_information?: MenuItem[];
    };
    latest_news: NewsListItem[];
    latest_documents: NewsListItem[];
    special?: number | string;
    settings?: Record<string, unknown>;
    contacts?: {
        phone?: string;
        email?: string;
        address?: string;
        work_time?: string;
    };
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
        <div class="content index">
            <!-- Показываем карту сайта если она есть в настройках -->
            <div v-if="(settings as any)?.sitemap" v-html="(settings as any)?.sitemap" class="sitemap-section" />

            <!-- Показываем обычный контент если это не карта сайта -->
            <template v-else>
                <!-- Блок с контактами -->
                <div v-if="contacts" class="contacts-section">
                    <h2>Контакты</h2>
                    <div class="contacts-grid">
                        <div v-if="contacts.phone" class="contact-item">
                            <strong>Телефон:</strong> {{ contacts.phone }}
                        </div>
                        <div v-if="contacts.email" class="contact-item">
                            <strong>Email:</strong> {{ contacts.email }}
                        </div>
                        <div v-if="contacts.address" class="contact-item">
                            <strong>Адрес:</strong> {{ contacts.address }}
                        </div>
                        <div v-if="contacts.work_time" class="contact-item">
                            <strong>Время работы:</strong> {{ contacts.work_time }}
                        </div>
                    </div>
                </div>

                <TabsLatest :news="latest_news" :documents="latest_documents" />
                <div v-if="(settings as any)?.map" v-html="(settings as any)?.map" />
            </template>
        </div>
    </PublicLayout>
</template>

<style>
.sitemap-section {
    margin: 20px 0;
}

.sitemap-section h2 {
    color: #333;
    margin-bottom: 20px;
    font-size: 24px;
}

.sitemap-section ul {
    list-style: none;
    padding: 0;
}

.sitemap-section .sitemap-list {
    margin-bottom: 20px;
}

.sitemap-section .sitemap-item {
    margin-bottom: 10px;
}

.sitemap-section .sitemap-link {
    color: #0066cc;
    text-decoration: none;
    font-weight: 500;
}

.sitemap-section .sitemap-link:hover {
    text-decoration: underline;
}

.sitemap-section .sitemap-sublist {
    margin-left: 30px;
    margin-top: 8px;
}

.sitemap-section .sitemap-sublist .sitemap-item {
    margin-bottom: 6px;
}

.sitemap-section .sitemap-sublist .sitemap-link {
    font-weight: normal;
    font-size: 0.95em;
}

/* Стили для контактов */
.contacts-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.contacts-section h2 {
    color: #333;
    margin-bottom: 20px;
    font-size: 24px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.contacts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.contact-item {
    padding: 10px;
    background: white;
    border-radius: 6px;
    border-left: 4px solid #007bff;
}

.contact-item strong {
    color: #007bff;
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contact-item:not(:last-child) {
    margin-bottom: 0;
}
</style>
