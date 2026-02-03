<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';
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

onMounted(() => {
    document.body.classList.toggle('special-mode', isSpecial.value);
});

watch(
    () => isSpecial.value,
    (enabled) => {
        document.body.classList.toggle('special-mode', enabled);
    },
);
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
    <div class="wrapper">
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
                <div class="main clearfix">
                    <div class="sidebar-wrapper">
                        <Sidebar :items="menus.sidebar" />
                        <div class="banners" v-if="settings?.left_sidebar_banners" v-html="settings.left_sidebar_banners" />
                    </div>
                    <div class="content-wrapper">
                        <div class="main-content">
                            <slot />
                        </div>
                        <div class="content-left" v-if="menus.current_information?.length || settings?.right_sidebar_banners || settings?.right_sidebar_menu">
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
                </div>
                    <div class="bottom-banners" v-if="settings?.bottom_banners" v-html="settings.bottom_banners" />

            </div>
        </div>
        <div class="bottom-bar">
            <div class="container">
                <div class="footer">
                    <div class="column contact">
                        <div v-html="settings?.footer_left ?? ''" />
                    </div>
                    <div class="column section-site">
                        <div v-html="settings?.footer_center ?? ''" />
                    </div>
                    <div class="column additionally">
                        <div v-html="settings?.footer_right ?? ''" />
                        <p class="totop">
                            <a href="#" class="scroll-top"><img src="/legacy/image/top-button.gif" alt="Вверх" /></a>
                        </p>
                    </div>
                    <div class="clearfix" />
                    <div class="copyright-wrapper">
                        <div class="copyright">© 2009-{{ new Date().getFullYear() }}. ТФОМС КК. Все права защищены.</div>
                        <div class="developer">
                            <a href="#">Разработка сайта</a>: <a href="http://salich-dev.ru">Саленко А.С.</a>
                            <a href="#"> Безопасность и доработка</a>: <a href="https://tigran-dev.ru">Адамян Т.П.</a>
                            <br />
                            RSS-канал:
                            <a href="/rss.xml" target="_blank" rel="noopener">http://www.kubanoms.ru/rss.xml</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
