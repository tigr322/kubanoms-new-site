<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

defineProps<{
    items: MenuItem[];
}>();
</script>

<template>
    <ul class="navbar">
        <li v-for="item in items" :key="item.id">
            <Link v-if="item.url" :href="item.url">
                {{ item.title }}
            </Link>
            <span v-else>{{ item.title }}</span>
            <ul v-if="item.children?.length">
                <li v-for="child in item.children" :key="child.id">
                    <Link v-if="child.url" :href="child.url">
                        {{ child.title }}
                    </Link>
                    <span v-else>{{ child.title }}</span>
                </li>
            </ul>
        </li>
    </ul>
</template>
