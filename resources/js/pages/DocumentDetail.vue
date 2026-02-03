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

type DocumentItem = {
    id: number;
    title: string;
    group_title: string | null;
    document_date: string | null;
    file: {
        name: string | null;
        url: string | null;
    };
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type DocumentsPaginator = {
    data: DocumentItem[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        per_page: number;
    };
};

defineProps<{
    page: {
        title: string;
        url: string;
        content: string;
        meta_description?: string | null;
        meta_keywords?: string | null;
        publication_date?: string | null;
        path?: string | null;
        attachments?: Array<{
            name: string;
            url: string;
        }>;
    };
    documents?: DocumentsPaginator;
    document_groups?: string[];
    has_ungrouped_documents?: boolean;
    active_group?: string;
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
            <div class="print">
                <a :href="`/print${page.url}`" target="_blank" rel="noopener">Версия для печати</a>
            </div>
            <h1>{{ page.title }}</h1>
            <p class="date" v-if="page.publication_date">{{ page.publication_date }}</p>
            <div v-html="page.content" />
            <p v-if="page.path">
                <a :href="page.path" target="_blank" rel="noopener">Скачать документ</a>
            </p>
            <div v-if="page.attachments?.length" class="news-attachments">
                <h3>Приложения</h3>
                <ul>
                    <li v-for="file in page.attachments" :key="file.url">
                        <a :href="file.url" target="_blank" rel="noopener">{{ file.name }}</a>
                    </li>
                </ul>
            </div>

            <template v-if="documents">
                <div v-if="(document_groups?.length || has_ungrouped_documents)" class="tabs">
                    <div :class="{ tabs_a: active_group === '__all' }">
                        <Link :href="page.url + '?group=__all'">Все документы</Link>
                    </div>
                    <div v-if="has_ungrouped_documents" :class="{ tabs_a: active_group === '__ungrouped' }">
                        <Link :href="page.url + '?group=__ungrouped'">Без таблицы</Link>
                    </div>
                    <div
                        v-for="group in document_groups"
                        :key="group"
                        :class="{ tabs_a: active_group === group }"
                    >
                        <Link :href="page.url + '?group=' + encodeURIComponent(group)">
                            {{ group }}
                        </Link>
                    </div>
                </div>

                <h3>Документы</h3>

                <template v-if="documents.data?.length">
                    <table class="cnttab" cellspacing="0" cellpadding="0" width="100%">
                        <thead>
                            <tr>
                                <th class="cntcel"><p>№</p></th>
                                <th class="cntcel"><p>Название</p></th>
                                <th class="cntcel"><p>Дата</p></th>
                                <th class="cntcel"><p>Файл</p></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(doc, index) in documents.data"
                                :key="doc.id"
                                :class="{ cntrow1: index % 2 === 1 }"
                            >
                                <td class="cntcel">
                                    <p>
                                        {{
                                            (documents.meta.current_page - 1) * documents.meta.per_page +
                                            index +
                                            1
                                        }}
                                    </p>
                                </td>
                                <td class="cntcel">
                                    <p>
                                        <a v-if="doc.file.url" :href="doc.file.url" target="_blank" rel="noopener">
                                            {{ doc.title }}
                                        </a>
                                        <span v-else>{{ doc.title }}</span>
                                    </p>
                                </td>
                                <td class="cntcel">
                                    <p>{{ doc.document_date ?? '' }}</p>
                                </td>
                                <td class="cntcel">
                                    <p>
                                        <a v-if="doc.file.url" :href="doc.file.url" target="_blank" rel="noopener">
                                            {{ doc.file.name ?? 'Скачать' }}
                                        </a>
                                        <span v-else>-</span>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="documents.links?.length" class="pagination">
                        <span
                            v-for="link in documents.links"
                            :key="`${link.label}-${link.url ?? ''}`"
                            class="paginate_button"
                            :class="{ active: link.active }"
                        >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label" />
                        </span>
                    </div>
                </template>
                <p v-else>Документы пока не загружены.</p>
            </template>
        </div>
    </PublicLayout>
</template>
