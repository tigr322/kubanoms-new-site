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

const navbarRef = ref<HTMLElement | null>(null);
const openItemId = ref<number | null>(null);
const supportsHover = ref(true);

const hasChildren = (item: MenuItem): boolean => {
    return Array.isArray(item.children) && item.children.length > 0;
};

const isOpen = (id: number): boolean => {
    return openItemId.value === id;
};

const closeAll = (): void => {
    openItemId.value = null;
};

const handleOutsideClick = (event: MouseEvent): void => {
    if (supportsHover.value) {
        return;
    }

    const root = navbarRef.value;

    if (!root) {
        return;
    }

    if (event.target instanceof Node && !root.contains(event.target)) {
        closeAll();
    }
};

const toggleOpen = (itemId: number): void => {
    openItemId.value = openItemId.value === itemId ? null : itemId;
};

const handleItemClick = (event: MouseEvent, item: MenuItem): void => {
    if (!hasChildren(item)) {
        closeAll();

        return;
    }

    if (supportsHover.value) {
        return;
    }

    if (openItemId.value !== item.id) {
        event.preventDefault();
        openItemId.value = item.id;

        return;
    }

    if (!item.url) {
        event.preventDefault();
        closeAll();
    }
};

const handleItemTitleTap = (event: MouseEvent, item: MenuItem): void => {
    if (!hasChildren(item)) {
        return;
    }

    if (supportsHover.value) {
        return;
    }

    event.preventDefault();
    toggleOpen(item.id);
};

const handleItemTitleEnter = (item: MenuItem): void => {
    if (!hasChildren(item) || supportsHover.value) {
        return;
    }

    toggleOpen(item.id);
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
    <ul ref="navbarRef" class="navbar">
        <li
            v-for="item in props.items"
            :key="item.id"
            :class="{ 'has-children': hasChildren(item), 'is-open': isOpen(item.id) }"
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
                @click="(event) => handleItemTitleTap(event, item)"
                @keydown.enter.prevent="handleItemTitleEnter(item)"
            >
                {{ item.title }}
            </span>
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

<style scoped>
.navbar > li.has-children > span {
    cursor: pointer;
}

@media (max-width: 768px) {
    .navbar > li.has-children > a,
    .navbar > li.has-children > span {
        position: relative;
        padding-right: 34px;
    }

    .navbar > li.has-children > a::after,
    .navbar > li.has-children > span::after {
        content: '+';
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-weight: 700;
    }

    .navbar > li.has-children.is-open > a::after,
    .navbar > li.has-children.is-open > span::after {
        content: '-';
    }
}
</style>
