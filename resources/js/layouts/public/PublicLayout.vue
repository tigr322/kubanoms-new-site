<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AccessibilityPanel from '@/components/public/AccessibilityPanel.vue';
import Navbar from '@/components/public/Navbar.vue';
import Sidebar from '@/components/public/Sidebar.vue';

type MenuItem = {
    id: number;
    title: string;
    url?: string;
    children?: MenuItem[];
}

const props = withDefaults(
    defineProps<{
        title: string;
        meta_description?: string | null;
        meta_keywords?: string | null;
        menus: {
            navbar: MenuItem[];
            sidebar: MenuItem[];
            current_information?: MenuItem[];
        };
        special?: number | string;
        settings?: {
            left_sidebar_banners?: string | null;
            right_sidebar_banners?: string | null;
            right_sidebar_menu?: string | null;
            bottom_banners?: string | null;
            map?: string | null;
            external_links?: string | null;
            footer_left?: string | null;
            footer_center?: string | null;
            footer_right?: string | null;
            footer_copyright?: string | null;
            footer_counters?: string | null;
            footer_developer?: string | null;
        };
    }>(),
    {
        meta_description: '',
        meta_keywords: '',
        special: 0,
        settings: () => ({}),
    },
);

const isSpecial = computed(() => Number(props.special) === 1);
const footerCopyright = computed(
    () => props.settings?.footer_copyright ?? '© 2009-2022. ТФОМС КК. Все права защищены.',
);
const footerCounters = computed(() => props.settings?.footer_counters ?? '');
const footerDeveloper = computed(
    () => props.settings?.footer_developer
        ?? '<a href="/rss.xml">RSS-канал</a> <a href="https://mirazher.ru/ru" target="_blank" rel="noopener">За создание сайта - </a>: <a href="https://mirazher.ru/ru" target="_blank" rel="noopener">Mirazher</a>',
);
const page = usePage();
const mainContentRef = ref<HTMLElement | null>(null);
const hideRightSidebar = ref(false);
const isMobileViewport = ref(false);
const isMobileSidebarOpen = ref(false);

let resizeObserver: ResizeObserver | null = null;
let mutationObserver: MutationObserver | null = null;
let frameId: number | null = null;

const setSpecialCookie = (value: boolean): void => {
    const expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1);
    document.cookie = `special=${value ? 1 : 0}; path=/; expires=${expires.toUTCString()}`;
};

const toggleSpecial = (): void => {
    setSpecialCookie(!isSpecial.value);
    document.body.classList.toggle('special-mode', !isSpecial.value);
    window.location.reload();
};

const hasOverflowingTable = (): boolean => {
    const mainContent = mainContentRef.value;

    if (!mainContent) {
        return false;
    }

    const containers = mainContent.querySelectorAll<HTMLElement>('.content');

    return Array.from(containers).some((container) => {
        const tables = container.querySelectorAll<HTMLTableElement>('table');

        return Array.from(tables).some(
            (table) => table.scrollWidth > container.clientWidth + 1,
        );
    });
};

const detectWideTables = (): void => {
    if (hideRightSidebar.value) {
        return;
    }

    hideRightSidebar.value = hasOverflowingTable();
};

const scheduleDetectWideTables = (): void => {
    if (frameId !== null) {
        cancelAnimationFrame(frameId);
    }

    frameId = requestAnimationFrame(() => {
        frameId = null;
        detectWideTables();
    });
};

const resetWideTablesState = (): void => {
    hideRightSidebar.value = false;

    nextTick(() => {
        scheduleDetectWideTables();
    });
};

const updateMobileViewportState = (): void => {
    isMobileViewport.value = window.matchMedia('(max-width: 768px)').matches;

    if (!isMobileViewport.value && isMobileSidebarOpen.value) {
        isMobileSidebarOpen.value = false;
    }
};

const toggleMobileSidebar = (): void => {
    if (!isMobileViewport.value) {
        return;
    }

    isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
};

const closeMobileSidebar = (): void => {
    if (!isMobileSidebarOpen.value) {
        return;
    }

    isMobileSidebarOpen.value = false;
};

const handleWindowResize = (): void => {
    scheduleDetectWideTables();
    updateMobileViewportState();
};

const handleSidebarWrapperClick = (event: MouseEvent): void => {
    if (!isMobileViewport.value || !isMobileSidebarOpen.value) {
        return;
    }

    if (!(event.target instanceof Element)) {
        return;
    }

    if (event.target.closest('a')) {
        closeMobileSidebar();
    }
};

onMounted(() => {
    document.body.classList.toggle('special-mode', isSpecial.value);
    scheduleDetectWideTables();
    updateMobileViewportState();

    if (mainContentRef.value) {
        mutationObserver = new MutationObserver(() => {
            scheduleDetectWideTables();
        });
        mutationObserver.observe(mainContentRef.value, {
            childList: true,
            subtree: true,
        });

        resizeObserver = new ResizeObserver(() => {
            scheduleDetectWideTables();
        });
        resizeObserver.observe(mainContentRef.value);
    }

    window.addEventListener('resize', handleWindowResize);
});

watch(
    () => isSpecial.value,
    (enabled) => {
        document.body.classList.toggle('special-mode', enabled);
    },
);

watch(
    () => page.url,
    () => {
        resetWideTablesState();
        closeMobileSidebar();
    },
);

onBeforeUnmount(() => {
    if (frameId !== null) {
        cancelAnimationFrame(frameId);
    }

    mutationObserver?.disconnect();
    resizeObserver?.disconnect();
    window.removeEventListener('resize', handleWindowResize);
});
</script>

<template>
    <Head>
        <title>
            Территориальный фонд ОМС Краснодарского края
            <template v-if="title"> :: {{ title }}</template>
        </title>
        <meta name="description" :content="meta_description ?? undefined" />
        <meta name="keywords" :content="meta_keywords ?? undefined" />
        <link rel="stylesheet" href="/legacy/style.css" />
        <link rel="stylesheet" href="/legacy/pos.css" />
        <link v-if="isSpecial" rel="stylesheet" href="/legacy/special_new.css" />
        <link v-if="isSpecial" rel="stylesheet" href="/legacy/bw.css" />
    </Head>
    <AccessibilityPanel />
    <div id="top" class="wrapper">
        <Link href="/admin" class="admin-shortcut">Админка</Link>
        <div class="top">
            <div class="container">
                <div class="header">
                    <div class="wrap clearfix">
                        <div class="logo" />
                        <div class="title">
                            <Link href="/">
                                Территориальный фонд<br />
                                обязательного медицинского страхования<br />
                                Краснодарского края
                            </Link>
                        </div>
                        <div class="top-nav">
                            <div class="buttons">
                                <ul>
                                    <li>
                                        <Link href="/"><img src="/legacy/image/home.gif" alt="" /></Link>
                                    </li>
                                    <li>
                                        <Link href="/sitemap"><img src="/legacy/image/map.gif" alt="" /></Link>
                                    </li>
                                    <li>
                                        <Link href="/faq"><img src="/legacy/image/mail.gif" alt="" /></Link>
                                    </li>
                                    <li>
                                        <a href="#"><img src="/legacy/image/div.gif" alt="" /></a>
                                    </li>
                                    <li>
                                        <a href="#" @click.prevent="toggleSpecial">
                                            <img src="/legacy/image/special.gif" alt="" />
                                            <span class="special">
                                                {{ isSpecial ? 'Выйти из режима для слабовидящих' : 'Версия для слабовидящих' }}
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#"><img src="/legacy/image/div.gif" alt="" /></a>
                                    </li>
                                    <li>
                                        <Link href="/search"><img src="/legacy/image/search.gif" alt="" /></Link>
                                    </li>
                                </ul>
                            </div>
                            <div class="today">
                                <strong>Сегодня</strong>:
                                {{ new Date().toLocaleDateString('ru-RU', { day: '2-digit', month: 'long', year: 'numeric' }) }}
                            </div>
                        </div>
                    </div>
                    <div class="image">
                        <img src="/legacy/image/collage.jpg" alt="" />
                    </div>
                    <Navbar :items="menus.navbar" />
                </div>
                <div class="main clearfix" :class="{ 'mobile-sidebar-open': isMobileSidebarOpen && isMobileViewport }">
                    <button
                        v-if="isMobileViewport"
                        type="button"
                        class="mobile-sidebar-toggle"
                        :aria-expanded="isMobileSidebarOpen ? 'true' : 'false'"
                        aria-controls="mobile-site-sidebar"
                        @click="toggleMobileSidebar"
                    >
                        <span class="mobile-sidebar-toggle-label">Разделы сайта</span>
                        <span class="mobile-sidebar-toggle-arrow">{{ isMobileSidebarOpen ? '▾' : '▸' }}</span>
                    </button>
                    <div
                        id="mobile-site-sidebar"
                        class="sidebar-wrapper"
                        :class="{ 'mobile-sidebar-panel': isMobileViewport, 'is-mobile-open': isMobileSidebarOpen && isMobileViewport }"
                        @click.capture="handleSidebarWrapperClick"
                    >
                        <Sidebar :items="menus.sidebar" />
                        <div class="banners" v-if="settings?.left_sidebar_banners" v-html="settings.left_sidebar_banners" />
                    </div>
                    <div class="content-wrapper" :class="{ 'hide-right-sidebar': hideRightSidebar }">
                        <div ref="mainContentRef" class="main-content">
                            <slot />
                        </div>
                        <div
                            class="content-left"
                            v-if="
                                !hideRightSidebar &&
                                (menus.current_information?.length || settings?.right_sidebar_banners || settings?.right_sidebar_menu)
                            "
                        >
                            <div class="banners" v-if="settings?.right_sidebar_banners" v-html="settings.right_sidebar_banners" />
                            <div class="content" v-if="settings?.right_sidebar_menu" v-html="settings.right_sidebar_menu" />
                            <div class="content" v-if="menus.current_information?.length">
                                <h4>Актуальная информация</h4>
                                <ul>
                                    <li v-for="item in menus.current_information" :key="`ci-${item.id}`">
                                        <Link :href="item.url ?? '#'">{{ item.title }}</Link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mobile-left-banners banners"
                        v-if="settings?.left_sidebar_banners"
                        v-html="settings.left_sidebar_banners"
                    />
                </div>
                    <div class="bottom-banners" v-if="settings?.bottom_banners" v-html="settings.bottom_banners" />

            </div>
        </div>
        <div class="bottom-bar">
            <div class="container">
                <table class="nb p0 bottom">
                    <tbody>
                        <tr>
                            <td align="center" class="bottom">
                                <table class="nbm" align="center" width="1160">
                                    <tbody>
                                        <tr>
                                            <td class="copy1" width="450">
                                                <div v-html="settings?.footer_left ?? ''" />
                                            </td>
                                            <td class="copy2" width="360">
                                                <div v-html="settings?.footer_center ?? ''" />
                                            </td>
                                            <td class="copy3" width="350">
                                                <div class="totop">
                                                    <a href="#top" class="scroll-top"><img src="/legacy/image/top-button.gif" alt="Вверх" /></a>
                                                </div>
                                                <div v-html="settings?.footer_right ?? ''" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <table class="nbm" align="center" width="1160">
                                    <tbody>
                                        <tr>
                                            <td class="copy4" v-html="footerCopyright"></td>
                                            <td class="counters" v-html="footerCounters"></td>
                                            <td class="copy5" v-html="footerDeveloper"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.content-wrapper.hide-right-sidebar .main-content {
    width: 100%;
}

.admin-shortcut {
    position: fixed;
    top: 8px;
    left: 8px;
    z-index: 1500;
    padding: 3px 8px;
    border-radius: 4px;
    border: 1px solid #0e517e;
    background: rgba(255, 255, 255, 0.94);
    color: #0e517e;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.2;
    text-decoration: none;
}

.admin-shortcut:hover {
    background: #0e517e;
    color: #fff;
}

@media (max-width: 768px) {
    .mobile-sidebar-toggle {
        position: relative;
        width: 100%;
        margin: 0 0 10px;
        padding: 10px 14px;
        border: 1px solid #0e517e;
        border-radius: 6px;
        background: #0e517e;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        cursor: pointer;
    }

    .mobile-sidebar-toggle-label {
        font-size: 14px;
        line-height: 1.2;
        font-weight: 700;
    }

    .mobile-sidebar-toggle-arrow {
        font-size: 17px;
        line-height: 1;
        font-weight: 700;
    }

    .sidebar-wrapper.mobile-sidebar-panel {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        margin: 0 !important;
        padding: 0;
        background: #eef6f9;
        border-radius: 6px;
        border: 1px solid #d8e8ef;
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transition: max-height 0.32s ease, opacity 0.25s ease, margin-bottom 0.25s ease;
    }

    .sidebar-wrapper.mobile-sidebar-panel.is-mobile-open {
        max-height: 72vh;
        opacity: 1;
        overflow-y: auto;
        margin-bottom: 12px !important;
    }

    .main.mobile-sidebar-open .mobile-sidebar-toggle {
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }

    .main :deep(.sidebar) {
        width: 100%;
    }
}
</style>

<style>
.top,
.bottom-bar {
    background-repeat: repeat-x !important;
}

.top {
    background-position: top center !important;
}

.bottom-bar {
    background-position: bottom center !important;
}

html,
html.dark {
    background-color: #c6e3e8 !important;
    background-image:
        url('/legacy/image/top_bg.jpg'),
        url('/legacy/image/body_bg.jpg') !important;
    background-repeat: repeat-x, repeat-x !important;
    background-position: top center, center 201px !important;
}

body:not(.special-mode) {
    background-color: #ffffff !important;
    background-image:
        url('/legacy/image/top_bg.jpg'),
        url('/legacy/image/body_bg.jpg') !important;
    background-repeat: repeat-x, repeat-x !important;
    background-position: top center, center 201px !important;
}

body:not(.special-mode) .bottom-bar {
    position: relative;
    isolation: isolate;
    overflow-x: hidden;
    overflow-x: clip;
    background-color: #c6e3e8 !important;
    background-image:
        url('/legacy/image/bottom_bg.jpg'),
        url('/legacy/image/body_bg.jpg') !important;
    background-repeat: repeat-x, repeat-x !important;
    background-position: bottom center, top center !important;
}

body:not(.special-mode) .bottom-bar::before {
    content: '';
    position: absolute;
    top: 0;
    right: -4000px;
    bottom: 0;
    left: -4000px;
    background-color: #c6e3e8;
    background-image: url('/legacy/image/bottom_bg.jpg'), url('/legacy/image/body_bg.jpg');
    background-repeat: repeat-x, repeat-x;
    background-position: bottom center, top center;
    z-index: -1;
    pointer-events: none;
}

body:not(.special-mode) table.bottom {
    background: transparent !important;
}

</style>
