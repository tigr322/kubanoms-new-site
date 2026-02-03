<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { MenuItem } from '@/types';

defineOptions({ name: 'MenuTree' });

const props = withDefaults(
    defineProps<{
        items: MenuItem[];
        level?: number;
    }>(),
    {
        level: 0,
    },
);
</script>

<template>
    <ul :class="props.level === 0 ? 'sitemap-list' : 'sitemap-sublist'">
        <li
            v-for="item in props.items"
            :key="item.id"
            :class="props.level === 0 ? 'sitemap-item' : 'sitemap-subitem'"
        >
            <Link
                v-if="item.url"
                :href="item.url"
                :class="props.level === 0 ? 'sitemap-link' : 'sitemap-sublink'"
            >
                {{ item.title }}
            </Link>
            <span
                v-else
                :class="props.level === 0 ? 'sitemap-link' : 'sitemap-sublink'"
            >
                {{ item.title }}
            </span>

            <MenuTree
                v-if="item.children?.length"
                :items="item.children"
                :level="props.level + 1"
            />
        </li>
    </ul>
</template>

