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
const hoveredItemId = ref<number | null>(null);

const isOpen = (id: number): boolean => {
    return openItems.value.includes(id) || hoveredItemId.value === id;
};

const toggle = (id: number): void => {
    if (openItems.value.includes(id)) {
        openItems.value = openItems.value.filter((itemId) => itemId !== id);
    } else {
        openItems.value = [...openItems.value, id];
    }
};

const handleMouseEnter = (item: MenuItem): void => {
    if (!item.children?.length) {
        return;
    }

    hoveredItemId.value = item.id;
};

const handleMouseLeave = (item: MenuItem): void => {
    if (hoveredItemId.value === item.id) {
        hoveredItemId.value = null;
    }
};

const handleItemClick = (event: MouseEvent, item: MenuItem): void => {
    if (!item.children?.length) {
        return;
    }

    // Match legacy behavior: parents with children expand instead of navigating.
    event.preventDefault();
    toggle(item.id);
};
</script>

<template>
    <ul class="sidebar">
        <li
            v-for="item in props.items"
            :key="item.id"
            :class="{ active: isOpen(item.id) }"
            @mouseenter="handleMouseEnter(item)"
            @mouseleave="handleMouseLeave(item)"
        >
            <Link
                v-if="item.url"
                :href="item.url"
                @click="(event) => handleItemClick(event, item)"
            >
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
