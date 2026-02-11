<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/public/PublicLayout.vue';

const props = defineProps<{
    menus: {
        navbar: Array<{
            id: number;
            title: string;
            url: string | null;
            children: Array<any>;
        }>;
        sidebar: Array<{
            id: number;
            title: string;
            url: string | null;
            children: Array<any>;
        }>;
        current_information?: Array<{
            id: number;
            title: string;
            url: string | null;
            children: Array<any>;
        }>;
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
}>();
</script>

<template>
    <PublicLayout title="Филиалы Территориального фонда ОМС Краснодарского края" :menus="menus" :special="special" :settings="settings">
        <Head title="Филиалы Территориального фонда ОМС Краснодарского края" />
        <div class="content">
            <h1>Филиалы Территориального фонда ОМС Краснодарского края</h1>
            
            <div class="branch-map-container">
                <div class="map-description">
                    <p>На карте представлены филиалы Территориального фонда обязательного медицинского страхования Краснодарского края. Вы можете найти ближайший к вам филиал и получить необходимую информацию.</p>
                </div>
                
                <div class="map-image-container">
                    <img 
                        src="/images/branches/map.png" 
                        alt="Карта филиалов Территориального фонда ОМС Краснодарского края"
                        class="branch-map"
                        title="Филиалы Территориального фонда ОМС Краснодарского края"
                    />
                </div>
                
                <div class="map-legend">
                    <h3>Легенда карты</h3>
                    <ul>
                        <li><span class="legend-marker main-office"></span>Главный офис (г. Краснодар)</li>
                        <li><span class="legend-marker branch-office"></span>Филиалы в районах края</li>
                    </ul>
                </div>
                
                <div class="contact-info">
                    <h3>Контактная информация</h3>
                    <p><strong>Главный офис:</strong><br>
                    350020, г. Краснодар, ул. Красная, 178<br>
                    Телефон: (861) 262-44-22<br>
                    E-mail: info@tfomskr.ru</p>
                    
                    <p><strong>График работы:</strong><br>
                    Понедельник - Пятница: 9:00 - 18:00<br>
                    Перерыв: 13:00 - 14:00<br>
                    Суббота, Воскресенье: выходной</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.branch-map-container {
    max-width: 100%;
    margin: 0 auto;
}

.map-description {
    margin-bottom: 20px;
    font-size: 16px;
    line-height: 1.6;
}

.map-image-container {
    text-align: center;
    margin: 30px 0;
    border: 2px solid #e0e4e7;
    border-radius: 8px;
    padding: 20px;
    background-color: #f8f9fa;
}

.branch-map {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.map-legend {
    margin: 30px 0;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #0e517e;
}

.map-legend h3 {
    margin-top: 0;
    color: #0e517e;
    font-size: 18px;
}

.map-legend ul {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
}

.map-legend li {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
}

.legend-marker {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: 10px;
    display: inline-block;
}

.legend-marker.main-office {
    background-color: #dc3545;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #dc3545;
}

.legend-marker.branch-office {
    background-color: #007bff;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #007bff;
}

.contact-info {
    margin-top: 30px;
    padding: 20px;
    background-color: #eef2f5;
    border-radius: 8px;
}

.contact-info h3 {
    margin-top: 0;
    color: #0e517e;
    font-size: 18px;
}

.contact-info p {
    margin-bottom: 15px;
    line-height: 1.6;
}

.contact-info strong {
    color: #0e517e;
}

@media (max-width: 768px) {
    .map-image-container {
        padding: 10px;
    }
    
    .map-legend,
    .contact-info {
        padding: 15px;
    }
    
    .map-legend li,
    .contact-info p {
        font-size: 13px;
    }
}
</style>
