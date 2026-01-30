# DB Inventory

## A) CMS (контент)
- `cms_page`: дерево страниц/новостей/документов/публикаций (`parent_id`, `title`, `title_short`, `meta_*`, `publication_date`, `content`, `page_status`, `page_of_type`, `url`, `template`, `path`).
- `cms_menu`: именованные меню (NAVBAR, SIDEBAR, CURRENT_INFORMATION).
- `cms_menu_item`: элементы меню (`menu_id`, `parent_id`, `page_id`, `url`, `sort_order`, `visible`).
- `cms_setting`: HTML/настройки по имени (`name`, `content`, `visibility`).
- `cms_file_folder`: древовидные папки.
- `cms_file`: файлы (`file_folder_id`, `original_name`, `path`, `mime_type`, `extension`, `description`, audit даты).
- `cms_user`: пользователи админки (legacy auth — НЕ переносим в новую схему).
- `cms_role`: роли админки (legacy, исключено из миграций).
- `cms_user_role`: pivot пользователь–роль (legacy, исключено).

## B) Virtual Reception (обращения)
- `oms_virtual_reception`: обращения (fio, birthdate, address/email/phone, contents, status, flags `only_email`, audit).
- `oms_virtual_reception_attachment`: вложения к обращению (`virtual_reception_id`, path, audit).
- `oms_virtual_reception_treatment`: связь обращений с `oms_treatment` (`virtual_reception_id`, `treatment_id`, audit).
- `oms_treatment`: справочник лечебных учреждений/третей записи (title, address/phone/email/web).
- `rate_limits`: защита от частых запросов (`ip_address`, `blocked_until`, `requests`, `last_request_at`) — legacy storage, не переносим.
- `sessions`: legacy сессии (`sess_id`, `sess_data`, `sess_time`, `sess_lifetime`, `verification_id`) — не переносим, используем Laravel sessions.

## C) Notifications (СМО/МО)
- `oms_notification_mo_included`: уведомления МО (smo info, numbers, dates, grounds).
- `oms_notification_smo_included`: уведомления о включении СМО (smo_full_name, director, include_date/reason, documents, audit).
- `oms_notification_smo_change`: уведомления об изменениях (smo names, numbers, date, reason).
- `oms_notification_smo_output`: уведомления об исключении (smo_full_name, exclude_date/reason, director, application_date).
- `oms_smo`: справочник СМО (names, address, inn, kpp, ogrn, phones/emails, region, order info).

## D) Legacy OMS back-office (oms.sql)
- Реклама/баннеры: `oms_ad_groups`, `oms_ad_banners`, `oms_ad_target`, `oms_ad_stat`.
- Опросы/анкеты: `oms_anketa`, `oms_anketa_quest`, `oms_anketa_ans`, `oms_anketa_resp`.
- Auth/ACL: `oms_auth_user`, `oms_auth_groups`, `oms_auth_aggregates`, `oms_auth_access`.
- Корзина/контент: `oms_basket`, `oms_propstorage`, `oms_navigation`.
- Каталоги/метаданные: `oms_ctl_catalog1`, `oms_ctltree`, `oms_ctlcolumns`, `oms_field_types`, `oms_field_access`.
- События/новости/FAQ: `oms_event_cat`, `oms_event_items`, `oms_faq`, `oms_media`, `oms_imagegroups`, `oms_imagegroupitems`.
- Гостевая/опросы: `oms_guestbook`, `oms_vote_quest`, `oms_vote_ans`.
- СМО-данные: `oms_smo_info`, `oms_smo_rating`, `oms_smo_smo` (видимо разные источники/агрегаты).
- Поиск/статистика: `oms_srchconjunctives`, `oms_srchobj`, `oms_srchparts`, `oms_srchpathes`, `oms_srchwords`.
- Сессии/настройки: `oms_session` (legacy sessions, не переносим), `oms_setup`.

## Неясные таблицы / Архив
- `orm_routes`: ORM-кеш маршрутов (не используется в новой архитектуре).
- `oms_basket`, `oms_propstorage`, `oms_navigation`, `oms_ctl*`, `oms_srch*`, `oms_auth_*`: требуют уточнения назначения, оставлены как read-only.
- `oms_session`, `oms_setup`: служебные таблицы legacy, пока не используются напрямую.
