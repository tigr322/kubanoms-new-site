# DB Schema Map (Legacy CMS/OMS)

## Перенесено в миграциях (создаётся php artisan migrate)
- CMS: `cms_file_folder`, `cms_file`, `cms_menu`, `cms_page`, `cms_menu_item`, `cms_setting`.
- Notifications/VR (из db/cms.sql): `oms_notification_mo_included`, `oms_notification_smo_change`, `oms_notification_smo_included`, `oms_notification_smo_output`, `oms_smo`, `oms_treatment`, `oms_virtual_reception`, `oms_virtual_reception_attachment`, `oms_virtual_reception_treatment`, `orm_routes`.
- OMS/back-office (из db/oms.sql, без auth/session): `oms_ad_groups`, `oms_ad_banners`, `oms_ad_stat`, `oms_ad_target`, `oms_anketa`, `oms_anketa_quest`, `oms_anketa_ans`, `oms_anketa_resp`, `oms_basket`, `oms_ctlcolumns`, `oms_ctltree`, `oms_ctl_catalog1`, `oms_event_cat`, `oms_event_items`, `oms_faq`, `oms_field_access`, `oms_field_types`, `oms_guestbook`, `oms_imagegroups`, `oms_imagegroupitems`, `oms_media`, `oms_navigation`, `oms_propstorage`, `oms_setup`, `oms_smo_info`, `oms_smo_rating`, `oms_smo_smo`, `oms_srchconjunctives`, `oms_srchobj`, `oms_srchparts`, `oms_srchpathes`, `oms_srchwords`, `oms_vote_quest`, `oms_vote_ans`.

## Исключено (используются стандартные таблицы Laravel или не переносим)
- Legacy auth/roles: `cms_user`, `cms_role`, `cms_user_role`, `oms_auth_access`, `oms_auth_aggregates`, `oms_auth_groups`, `oms_auth_user`.
- Legacy sessions/cache/rate-limit: `sessions`, `rate_limits`, `oms_session`.
- Laravel служебные таблицы остаются стандартными: `users`, `password_reset_tokens`, `cache*`, `jobs`, `failed_jobs`, `personal_access_tokens`, `sessions` (laravel), т.д.

## Причины исключения
- Используем Laravel Breeze/стандартную таблицу `users` + spatie permissions (план) вместо legacy auth.
- Сессии/кэш/throttle — через драйверы Laravel (file/redis/database), без legacy таблиц.
- Auth* из OMS — часть старого back-office, не нужна в новой схеме.
