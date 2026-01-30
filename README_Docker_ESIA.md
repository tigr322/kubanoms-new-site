# Docker и ЕСИА Интеграция

## Docker развертывание

### Требования
- Docker 20.10+
- Docker Compose 2.0+

### Быстрый старт

1. **Клонирование и настройка:**
```bash
git clone <repository-url>
cd kubanoms-new-site
cp .env.example .env
```

2. **Запуск через Docker Compose:**
```bash
docker-compose up -d
```

3. **Установка зависимостей и миграции:**
```bash
docker-compose exec app composer install
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan storage:link
```

4. **Сборка фронтенда:**
```bash
docker-compose exec app npm run build
```

### Доступ к приложению
- **Сайт:** http://localhost:80
- **Админ-панель:** http://localhost:80/admin
- **PHP-FPM:** localhost:9000

### Структура Docker
- `Dockerfile` - PHP 8.5.1 Alpine с необходимыми расширениями
- `docker-compose.yml` - Оркестрация сервисов
- `docker/nginx/` - Конфигурация Nginx

## ЕСИА Интеграция

### Обзор
Интеграция с esia-mini для обязательной авторизации через ЕСИА перед подачей жалоб в виртуальной приемной.

### Архитектура

1. **Middleware проверки ЕСИА:**
   - `EsiaAuthMiddleware` - перенаправляет на esia-mini при отсутствии авторизации
   - Применяется к маршрутам виртуальной приемной

2. **Контроллер виртуальной приемной:**
   - `VirtualReceptionController` - обработка формы и callback от ЕСИА
   - Автозаполнение полей данными из ЕСИА

3. **Vue компонент:**
   - `VirtualReception/Create.vue` - форма с предзаполненными данными
   - Блокировка полей при авторизации через ЕСИА

### Настройка

1. **Основной сайт (.env):**
```env
# ЕСИА Интеграция
ESIA_MINI_URL=http://localhost:3001
ESIA_CLIENT_ID=your_client_id
ESIA_CLIENT_SECRET=your_client_secret
ESIA_REDIRECT_URI=http://localhost:8000/virtual-reception/callback
```

2. **esia-mini (.env):**
```env
# ЕСИА Конфигурация
ESIA_CLIENT_ID=230A03
ESIA_REDIRECT_URL=http://localhost:8000/virtual-reception/callback
ESIA_PORTAL_URL=https://esia-portal1.test.gosuslugi.ru/
ESIA_CERT_PATH=../resources/ekapusta.gost.test.cer
ESIA_PRIVATE_KEY_PATH=../resources/ekapusta.gost.test.key
ESIA_TOKEN=secret

# Настройки сервера
PORT=3001
```

3. **Настройка esia-mini:**
   ```bash
   cd esia-mini
   cp .env.example .env
   npm install
   npm run dev
   ```

### Процесс работы

1. **Пользователь нажимает "Виртуальная приемная"**
2. **Middleware проверяет наличие данных ЕСИА в сессии**
3. **Если данных нет - редирект на esia-mini:**
   ```
   http://localhost:3001/auth?callback=http://localhost:8000/virtual-reception/callback
   ```
4. **esia-mini читает настройки из .env и перенаправляет на ЕСИА портал**
5. **Пользователь авторизуется в ЕСИА**
6. **esia-mini возвращает данные пользователя на callback**
7. **Данные сохраняются в сессию и форма открывается с предзаполненными полями**
8. **Пользователь может редактировать только тему и текст обращения**

### Данные из ЕСИА

- Фамилия (lastName)
- Имя (firstName)
- Отчество (middleName)
- Email
- Телефон
- СНИЛС
- Дата рождения
- Пол

### Маршруты

- `GET /virtual-reception/` - форма с проверкой ЕСИА
- `GET /virtual-reception/callback` - callback от esia-mini
- `POST /virtual-reception/` - сохранение обращения

### Безопасность

- Все данные ЕСИА хранятся только в сессии
- Обязательная авторизация перед подачей жалобы
- Валидация данных на стороне сервера
- Защита от CSRF атак

### Тестирование

1. **Проверка без авторизации:**
   ```bash
   curl http://localhost/virtual-reception/
   # Должен перенаправить на esia-mini
   ```

2. **Проверка с авторизацией:**
   ```bash
   # После авторизации в ЕСИА
   curl -b "esia_user=..." http://localhost/virtual-reception/
   # Должен показать форму с предзаполненными данными
   ```

## Разработка

### Локальная разработка

1. **Запуск esia-mini:**
   ```bash
   cd esia-mini
   cp .env.example .env
   npm install
   npm run dev
   ```

2. **Запуск основного сайта:**
   ```bash
   npm run dev
   php artisan serve
   ```

### Отладка

1. **Проверка middleware:**
   ```php
   php artisan route:list --middleware=esia.auth
   ```

2. **Проверка сессии:**
   ```php
   php artisan tinker
   session()->get('esia_user')
   ```

3. **Проверка переменных окружения:**
   ```bash
   # В основном сайте
   php artisan tinker
   config('services.esia.url')
   
   # В esia-mini
   php -r "var_dump(\$_ENV['ESIA_CLIENT_ID']);"
   ```

## Поддержка

При возникновении проблем:
1. Проверьте логи Docker: `docker-compose logs app`
2. Проверьте настройки esia-mini (.env файл)
3. Убедитесь, что callback URL доступен
4. Проверьте переменные окружения в .env файлах
5. Убедитесь, что esia-mini запущен на порту 3001
