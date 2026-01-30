<template>
    <!-- Кнопка для включения/выключения режима для слабовидящих -->
    <div class="accessibility-toggle">
        <button
            @click="toggleSpecialMode"
            :class="{ active: isSpecialMode }"
            title="Версия для слабовидящих"
        >
            <span v-if="isSpecialMode">Обычная версия</span>
            <span v-else>Версия для слабовидящих</span>
        </button>
    </div>

    <!-- Панель настроек режима для слабовидящих -->
    <div v-if="isSpecialMode" class="panel-contrast">
        <div class="wrap-control">
            <div class="font-size">
                <span>Размер шрифта:</span>
                <button
                    v-for="size in fontSizes"
                    :key="size.class"
                    @click="setFontSize(size.class)"
                    :class="['font-btn', size.class, { active: currentFontSize === size.class }]"
                    :data-font-size="size.class"
                >
                    {{ size.label }}
                </button>
            </div>

            <div class="font-family">
                <span>Тип шрифта:</span>
                <button
                    v-for="font in fontFamilies"
                    :key="font.class"
                    @click="setFontFamily(font.class)"
                    :class="['font-btn', font.class, { active: currentFontFamily === font.class }]"
                    :data-font-family="font.class"
                >
                    {{ font.label }}
                </button>
            </div>

            <div class="color">
                <span>Цвета сайта:</span>
                <button
                    v-for="color in colorSchemes"
                    :key="color.class"
                    @click="setColorScheme(color.class)"
                    :class="['color-btn', color.class, { active: currentColorScheme === color.class }]"
                    :data-color="color.class"
                >
                    {{ color.label }}
                </button>
            </div>

            <div class="img-show">
                <button
                    @click="toggleImages"
                    :class="{ active: showImages }"
                >
                    Изображения
                </button>
            </div>

            <div class="settings">
                <button @click="toggleSettingsPanel">
                    Настройки
                </button>
            </div>

            <div v-show="showSettingsPanel" class="settings-panel">
                <div class="wrap">
                    <div class="char-interval">
                        <span>Интервал между буквами (Кернинг):</span>
                        <button
                            v-for="interval in charIntervals"
                            :key="interval.class"
                            @click="setCharInterval(interval.class)"
                            :class="['interval-btn', interval.class, { active: currentCharInterval === interval.class }]"
                            :data-interval="interval.class"
                        >
                            {{ interval.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const isSpecialMode = ref(false)
const showSettingsPanel = ref(false)

const currentFontSize = ref('small-font')
const currentFontFamily = ref('san-serif')
const currentColorScheme = ref('white')
const showImages = ref(true)
const currentCharInterval = ref('interval-small')

const fontSizes = [
    { class: 'small-font', label: 'A' },
    { class: 'normal-font', label: 'A' },
    { class: 'big-font', label: 'A' }
]

const fontFamilies = [
    { class: 'san-serif', label: 'Arial' },
    { class: 'serif', label: 'Times New Roman' }
]

const colorSchemes = [
    { class: 'white', label: 'Ц' },
    { class: 'black', label: 'Ц' },
    { class: 'blue', label: 'Ц' },
    { class: 'brown', label: 'Ц' }
]

const charIntervals = [
    { class: 'interval-small', label: 'Стандартный' },
    { class: 'interval-medium', label: 'Средний' },
    { class: 'interval-large', label: 'Большой' }
]

// Функции управления настройками
const setFontSize = (size: string) => {
    currentFontSize.value = size
    applyClasses()
    saveCookie('special_fz', size)
}

const setFontFamily = (font: string) => {
    currentFontFamily.value = font
    applyClasses()
    saveCookie('special_ff', font)
}

const setColorScheme = (color: string) => {
    currentColorScheme.value = color
    applyClasses()
    saveCookie('special_color', color)
}

const toggleImages = () => {
    showImages.value = !showImages.value
    applyClasses()
    saveCookie('special_hi', showImages.value ? 'on' : 'off')
}

const setCharInterval = (interval: string) => {
    currentCharInterval.value = interval
    applyClasses()
    saveCookie('special_char_interval', interval)
}

const toggleSettingsPanel = () => {
    showSettingsPanel.value = !showSettingsPanel.value
}

// Переключение режима для слабовидящих
const toggleSpecialMode = () => {
    isSpecialMode.value = !isSpecialMode.value

    // Сохраняем в cookie
    saveCookie('special', isSpecialMode.value ? '1' : '0')

    // Применяем или убираем классы
    if (isSpecialMode.value) {
        loadSettings()
        applyClasses()
    } else {
        // Удаляем все классы специального режима
        const body = document.body
        body.className = body.className.replace(/small-font|normal-font|big-font|san-serif|serif|white|black|blue|brown|hide-img|interval-small|interval-medium|interval-large|special-mode/g, '').trim()
    }
}

// Применение классов к body
const applyClasses = () => {
    const body = document.body

    // Удаляем все классы
    body.className = body.className.replace(/small-font|normal-font|big-font|san-serif|serif|white|black|blue|brown|hide-img|interval-small|interval-medium|interval-large/g, '').trim()

    // Добавляем текущие классы
    if (isSpecialMode.value) {
        body.classList.add(currentFontSize.value)
        body.classList.add(currentFontFamily.value)
        body.classList.add(currentColorScheme.value)
        body.classList.add(currentCharInterval.value)

        if (!showImages.value) {
            body.classList.add('hide-img')
        }
    }
}

// Работа с cookies
const saveCookie = (name: string, value: string) => {
    document.cookie = `${name}=${value}; path=/; max-age=${365 * 24 * 60 * 60}`
}

const getCookie = (name: string): string | null => {
    const value = `; ${document.cookie}`
    const parts = value.split(`; ${name}=`)
    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || null
    }
    return null
}

// Проверка параметра URL и cookies
const checkSpecialMode = () => {
    const urlParams = new URLSearchParams(window.location.search)
    const specialParam = urlParams.get('special')

    if (specialParam === '1') {
        isSpecialMode.value = true
        saveCookie('special', '1')
    } else {
        const specialCookie = getCookie('special')
        isSpecialMode.value = specialCookie === '1'
    }

    if (isSpecialMode.value) {
        loadSettings()
        applyClasses()
    }
}

// Загрузка сохраненных настроек
const loadSettings = () => {
    currentFontSize.value = getCookie('special_fz') || 'small-font'
    currentFontFamily.value = getCookie('special_ff') || 'san-serif'
    currentColorScheme.value = getCookie('special_color') || 'white'
    showImages.value = getCookie('special_hi') !== 'off'
    currentCharInterval.value = getCookie('special_char_interval') || 'interval-small'
}

onMounted(() => {
    checkSpecialMode()
})
</script>

<style scoped>
.accessibility-toggle {
    position: fixed;
    top: 10px;
    right: 10px;
    z-index: 1000;
}

.accessibility-toggle button {
    padding: 8px 16px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

.accessibility-toggle button:hover {
    background: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.accessibility-toggle button.active {
    background: #dc3545;
}

.accessibility-toggle button.active:hover {
    background: #c82333;
}

.panel-contrast {
    background: #f5f5f5;
    border-bottom: 1px solid #ddd;
    padding: 10px 0;
}

.wrap-control {
    max-width: 942px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: center;
}

.font-size, .font-family, .color, .img-show, .settings {
    display: flex;
    align-items: center;
    gap: 8px;
}

.font-btn, .color-btn, .interval-btn {
    padding: 4px 8px;
    border: 1px solid #ccc;
    background: #fff;
    cursor: pointer;
    font-weight: bold;
}

.font-btn.active, .color-btn.active, .interval-btn.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}

.settings-panel {
    width: 100%;
    margin-top: 10px;
    padding: 10px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.char-interval {
    display: flex;
    align-items: center;
    gap: 8px;
}

button {
    padding: 4px 12px;
    border: 1px solid #ccc;
    background: #fff;
    cursor: pointer;
}

button.active {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}

span {
    font-weight: bold;
    margin-right: 5px;
}
</style>
