# Feature Map

| Таблица | Функционал | Область | Статус |
| --- | --- | --- | --- |
| cms_page | Страницы/новости/документы/публикации, meta, шаблоны | Публично + Админ | Каркас (публичные маршруты, модели, миграции) |
| cms_menu / cms_menu_item | Меню NAVBAR/SIDEBAR/CURRENT_INFORMATION | Публично + Админ | Каркас (модели, миграции) |
| cms_setting | HTML-блоки/баннеры | Админ | Каркас |
| cms_file_folder / cms_file | Хранилище файлов | Админ | Каркас |
| cms_user / cms_role / cms_user_role | Legacy админ пользователи/роли | Админ | Исключено (используем Laravel users + spatie) |
| oms_virtual_reception (+attachments, treatments) | Виртуальная приёмная: обращение + вложения + связка с лечебными учреждениями | Публично (форма) + Админ | Каркас (модели/миграции; контроллеры-каркасы требуются) |
| oms_treatment | Справочник лечебных учреждений | Админ | Каркас |
| rate_limits | Rate-limit по IP | Middleware/сервис | Исключено, используем Laravel throttle/cache |
| sessions (legacy) | Legacy сессии (под VR/ESIA) | Сервис | Исключено, используем Laravel sessions |
| oms_notification_* (mo/smo) | Уведомления о включении/исключении/изменении | Админ/API | Каркас |
| oms_smo | Справочник СМО | Админ | Каркас |
| orm_routes | Кеш маршрутов ORM | Сервис | Каркас (read-only) |
| oms_ad_* | Реклама/баннеры/таргетинг/статистика | Админ | Read-only каркас |
| oms_anketa* | Анкеты/опросы | Публично + Админ | Read-only каркас |
| oms_auth_* | ACL legacy back-office | Админ | Исключено (перевод на Laravel auth) |
| oms_basket | Корзина/объекты legacy | Админ | Read-only |
| oms_ctl* | Каталоги/метаданные | Админ | Read-only |
| oms_event_* | События/новости legacy | Публично? | Read-only |
| oms_faq | FAQ legacy | Публично? | Read-only |
| oms_field_* | Типы/доступ к полям | Сервис | Read-only |
| oms_guestbook | Гостевая книга | Публично | Read-only |
| oms_image* / oms_media | Медиа-галереи | Админ | Read-only |
| oms_navigation | Навигация legacy | Админ | Read-only |
| oms_propstorage | KV-хранилище свойств | Сервис | Read-only |
| oms_session | Legacy сессии (отличная от cms sessions) | Сервис | Исключено, используем Laravel sessions |
| oms_setup | Настройки системы | Сервис | Read-only |
| oms_smo_* (info/rating/smo) | Информация и рейтинги СМО | Админ/API | Read-only |
| oms_srch* | Поисковый индекс legacy | Сервис | Read-only |
| oms_vote_* | Голосования | Публично + Админ | Read-only |

## Эндпоинты/страницы (должны быть)
- Публичные: `/`, `/rss.xml`, `/search`, `/{url}` (catch-all), `/virtual-reception` (форма), `/sitemap` (заглушка).
- Админ: CRUD для cms_pages/menus/menu_items/settings/files; просмотр/управление VR и уведомлениями; read-only для legacy OMS таблиц.
- API/служебные: rate-limit middleware, legacy sessions (для совместимости VR/ESIA), RSS.

Статусы: перечисленные как “каркас” имеют миграции/модели; помеченные “исключено” оставлены вне схемы (заменены Laravel auth/sessions/throttle). Где неясно использование (блок “read-only”) — требует уточнения с заказчиком.
