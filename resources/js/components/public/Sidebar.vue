<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

const props = defineProps<{
    items: MenuItem[];
}>();

const openItems = ref<number[]>([]);

const toggle = (id: number): void => {
    if (openItems.value.includes(id)) {
        openItems.value = openItems.value.filter((itemId) => itemId !== id);
    } else {
        openItems.value = [...openItems.value, id];
    }
};
</script>

<template>
    <ul class="sidebar">
        <li v-for="item in props.items" :key="item.id" :class="{ active: openItems.includes(item.id) }">
            <Link v-if="item.url" :href="item.url" @click="item.children?.length ? toggle(item.id) : undefined">
                {{ item.title }}
            </Link>
            <span v-else>{{ item.title }}</span>
            <ul v-if="item.children?.length">
                <li v-for="child in item.children" :key="child.id">
                    <Link v-if="child.url" :href="child.url">{{ child.title }}</Link>
                    <span v-else>{{ child.title }}</span>
                </li>
            </ul>
        </li>
    </ul>
</template>
