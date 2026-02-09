<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { NewsListItem } from '@/types';

const props = defineProps<{
    news: NewsListItem[];
    documents: NewsListItem[];
}>();

const activeTab = ref<'news' | 'documents'>('news');
</script>

<template>
    <div class="tabs-container">
        <ul class="tabs">
            <li :class="{ active: activeTab === 'news' }">
                <a href="#tab-news" @click.prevent="activeTab = 'news'">НОВОСТИ</a>
            </li>
            <li :class="{ active: activeTab === 'documents' }">
                <a href="#tab-document" @click.prevent="activeTab = 'documents'">НОВЫЕ ДОКУМЕНТЫ</a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane" :class="{ active: activeTab === 'news' }" id="tab-news">
                <div class="item-container">
                    <div
                        v-for="item in props.news"
                        :key="item.id"
                        class="item clearfix"
                        style="display: flex; align-items: center; gap: 12px"
                    >
                        <div
                            v-if="item.image"
                            class="item-image"
                            style="
                                flex: 0 0 auto;
                                overflow: hidden;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            "
                        >
                            <img :src="item.image" :alt="item.title" loading="lazy" style="max-width: 100%; height: auto;" />
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 4px">
                            <div class="item-date">{{ item.date }}</div>
                            <div class="item-text">
                                <Link :href="item.url">{{ item.title }}</Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane" :class="{ active: activeTab === 'documents' }" id="tab-document">
                <div class="item-container">
                    <div v-for="item in props.documents" :key="item.id" class="item clearfix">
                        <div class="item-date">{{ item.date }}</div>
                        <div class="item-text">
                            <Link :href="item.url">{{ item.title }}</Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
