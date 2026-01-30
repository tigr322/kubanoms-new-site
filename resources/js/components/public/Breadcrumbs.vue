<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type Crumb = {
    title: string;
    url: string;
};

withDefaults(
    defineProps<{
        items?: Crumb[];
    }>(),
    {
        items: () => [],
    },
);
</script>

<template>
    <div class="breadcrumbs" v-if="items.length">
        <ul>
            <li>
                <Link href="/">Главная страница</Link>
            </li>
            <li v-for="item in items" :key="item.url">
                <Link :href="item.url">{{ item.title }}</Link>
            </li>
        </ul>
    </div>
</template>
