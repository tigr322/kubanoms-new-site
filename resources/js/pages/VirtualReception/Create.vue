<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    esiaUser: Object,
    preFilledData: Object,
});

const form = useForm({
    lastName: props.preFilledData.lastName || '',
    firstName: props.preFilledData.firstName || '',
    middleName: props.preFilledData.middleName || '',
    email: props.preFilledData.email || '',
    phone: props.preFilledData.phone || '',
    snils: props.preFilledData.snils || '',
    subject: '',
    message: '',
    attachments: [],
});

const submit = () => {
    form.post(route('virtual-reception.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset('subject', 'message', 'attachments');
        },
    });
};

const handleFileChange = (event) => {
    form.attachments = Array.from(event.target.files);
};
</script>

<template>
    <Head title="Виртуальная приемная" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h1 class="text-2xl font-bold text-gray-900 mb-6">
                            Виртуальная приемная
                        </h1>
                        
                        <div v-if="esiaUser" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-green-800">
                                    Вы авторизованы через ЕСИА. Данные автоматически заполнены.
                                </span>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700">
                                        Фамилия <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="lastName"
                                        v-model="form.lastName"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        required
                                        :disabled="!!esiaUser"
                                    />
                                    <p v-if="esiaUser" class="mt-1 text-sm text-gray-500">
                                        Автозаполнено из ЕСИА
                                    </p>
                                </div>

                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700">
                                        Имя <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="firstName"
                                        v-model="form.firstName"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        required
                                        :disabled="!!esiaUser"
                                    />
                                    <p v-if="esiaUser" class="mt-1 text-sm text-gray-500">
                                        Автозаполнено из ЕСИА
                                    </p>
                                </div>

                                <div>
                                    <label for="middleName" class="block text-sm font-medium text-gray-700">
                                        Отчество
                                    </label>
                                    <input
                                        id="middleName"
                                        v-model="form.middleName"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        :disabled="!!esiaUser"
                                    />
                                    <p v-if="esiaUser" class="mt-1 text-sm text-gray-500">
                                        Автозаполнено из ЕСИА
                                    </p>
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">
                                        Email
                                    </label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        :disabled="!!esiaUser"
                                    />
                                    <p v-if="esiaUser" class="mt-1 text-sm text-gray-500">
                                        Автозаполнено из ЕСИА
                                    </p>
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">
                                        Телефон
                                    </label>
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        :disabled="!!esiaUser"
                                    />
                                    <p v-if="esiaUser" class="mt-1 text-sm text-gray-500">
                                        Автозаполнено из ЕСИА
                                    </p>
                                </div>

                                <div>
                                    <label for="snils" class="block text-sm font-medium text-gray-700">
                                        СНИЛС
                                    </label>
                                    <input
                                        id="snils"
                                        v-model="form.snils"
                                        type="text"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        :disabled="!!esiaUser"
                                    />
                                    <p v-if="esiaUser" class="mt-1 text-sm text-gray-500">
                                        Автозаполнено из ЕСИА
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label for="subject" class="block text-sm font-medium text-gray-700">
                                    Тема обращения <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="subject"
                                    v-model="form.subject"
                                    type="text"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    required
                                />
                            </div>

                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700">
                                    Текст обращения <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="message"
                                    v-model="form.message"
                                    rows="6"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    required
                                ></textarea>
                                <p class="mt-1 text-sm text-gray-500">
                                    Минимум 10 символов, максимум 5000 символов
                                </p>
                            </div>

                            <div>
                                <label for="attachments" class="block text-sm font-medium text-gray-700">
                                    Прикрепить файлы (максимум 5 файлов, до 5 МБ каждый)
                                </label>
                                <input
                                    id="attachments"
                                    type="file"
                                    multiple
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                    @change="handleFileChange"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Допустимые форматы: PDF, DOC, DOCX, JPG, JPEG, PNG
                                </p>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    :disabled="form.processing"
                                >
                                    Отправить обращение
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
