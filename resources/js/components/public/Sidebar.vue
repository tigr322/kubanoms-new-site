<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

const props = defineProps<{
    items: MenuItem[];
}>();

const sidebarRef = ref<HTMLElement | null>(null);
const openItems = ref<number[]>([]);
const hoveredItemId = ref<number | null>(null);
const supportsHover = ref(true);

const hasChildren = (item: MenuItem): boolean => {
    return Array.isArray(item.children) && item.children.length > 0;
};

const isOpen = (id: number): boolean => {
    if (supportsHover.value) {
        return openItems.value.includes(id) || hoveredItemId.value === id;
    }

    return openItems.value.includes(id);
};

const toggle = (id: number): void => {
    if (openItems.value.includes(id)) {
        openItems.value = openItems.value.filter((itemId) => itemId !== id);
    } else {
        openItems.value = [...openItems.value, id];
    }
};

const handleMouseEnter = (item: MenuItem): void => {
    if (!supportsHover.value || !hasChildren(item)) {
        return;
    }

    hoveredItemId.value = item.id;
};

const handleMouseLeave = (item: MenuItem): void => {
    if (!supportsHover.value) {
        return;
    }

    if (hoveredItemId.value === item.id) {
        hoveredItemId.value = null;
    }
};

const handleItemClick = (event: MouseEvent, item: MenuItem): void => {
    if (!hasChildren(item)) {
        return;
    }

    if (!supportsHover.value) {
        if (!openItems.value.includes(item.id)) {
            event.preventDefault();
            toggle(item.id);

            return;
        }

        return;
    }

    event.preventDefault();
};

const handleItemTitleClick = (event: MouseEvent, item: MenuItem): void => {
    if (!hasChildren(item)) {
        return;
    }

    event.preventDefault();
    toggle(item.id);
};

const handleItemTitleEnter = (item: MenuItem): void => {
    if (!hasChildren(item)) {
        return;
    }

    toggle(item.id);
};

const handleOutsideClick = (event: MouseEvent): void => {
    if (supportsHover.value) {
        return;
    }

    const root = sidebarRef.value;

    if (!root) {
        return;
    }

    if (event.target instanceof Node && !root.contains(event.target)) {
        openItems.value = [];
    }
};

onMounted(() => {
    supportsHover.value = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
    <ul ref="sidebarRef" class="sidebar">
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
            <span
                v-else
                role="button"
                tabindex="0"
                @click="(event) => handleItemTitleClick(event, item)"
                @keydown.enter.prevent="handleItemTitleEnter(item)"
            >
                {{ item.title }}
            </span>
            <ul v-if="item.children?.length">
                <li v-for="child in item.children" :key="child.id">
                    <Link v-if="child.url" :href="child.url">{{ child.title }}</Link>
                    <span v-else>{{ child.title }}</span>
                </li>
            </ul>
        </li>
    </ul>
</template>

<style scoped>
.sidebar > li > span {
    display: block;
    cursor: pointer;
}
</style>
