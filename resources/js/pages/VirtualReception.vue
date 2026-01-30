<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

type MenuItem = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItem[];
};

defineProps<{
    title: string;
    menus: {
        navbar: MenuItem[];
        sidebar: MenuItem[];
        current_information?: MenuItem[];
    };
    settings?: Record<string, unknown>;
    special?: number | string;
}>();

const form = useForm({
    fio: '',
    email: '',
    phone: '',
    contents: '',
    only_email: false,
});

const submit = () => {
    form.post('/virtual-reception');
};
</script>

<template>
    <PublicLayout
        :title="title"
        :menus="menus as any"
        :settings="settings as any"
        :special="special"
    >
        <Head :title="title" />
        <div class="content">
            <h1>Виртуальная приёмная</h1>
            <form @submit.prevent="submit">
                <div class="form-group">
                    <label for="fio">ФИО</label>
                    <input id="fio" v-model="form.fio" type="text" required />
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" v-model="form.email" type="email" />
                </div>
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input id="phone" v-model="form.phone" type="text" />
                </div>
                <div class="form-group">
                    <label for="contents">Сообщение</label>
                    <textarea id="contents" v-model="form.contents" required />
                </div>
                <div class="form-group">
                    <label>
                        <input v-model="form.only_email" type="checkbox" />
                        Получать ответ только на email
                    </label>
                </div>
                <button type="submit">Отправить</button>
            </form>
        </div>
    </PublicLayout>
</template>
