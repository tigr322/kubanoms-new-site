<script setup lang="ts">
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <PublicLayout title="Страница не найдена" :menus="{ navbar: [], sidebar: [] }">
        <Head title="Страница не найдена" />
        <div class="content">
            <h1>404</h1>
            <p>Страница не найдена.</p>
            <Link href="/">Вернуться на главную</Link>
        </div>
    </PublicLayout>
</template>
