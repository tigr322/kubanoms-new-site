<?php

namespace Database\Seeders;

use App\Models\Cms\CmsMenu;
use App\Models\Cms\CmsMenuItem;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ],
        );

        User::updateOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'Editor User',
                'password' => Hash::make('password123'),
                'role' => 'editor',
            ],
        );

        $navbar = CmsMenu::firstOrCreate(
            ['name' => 'NAVBAR'],
            [
                'title' => 'Горизонтальное меню',
                'max_depth' => 2,
                'description' => null,
            ]
        );

        $sidebar = CmsMenu::firstOrCreate(
            ['name' => 'SIDEBAR'],
            [
                'title' => 'Вертикальное меню',
                'max_depth' => 3,
                'description' => null,
            ]
        );

        $currentInfo = CmsMenu::firstOrCreate(
            ['name' => 'CURRENT_INFORMATION'],
            [
                'title' => 'Актуальная информация',
                'max_depth' => 1,
                'description' => null,
            ]
        );

        // Главная страница
        $home = CmsPage::firstOrCreate(
            ['url' => '/'],
            [
                'parent_id' => null,
                'title' => 'Территориальный фонд ОМС Краснодарского края',
                'title_short' => 'Главная',
                'meta_description' => 'Главная страница',
                'meta_keywords' => 'ОМС, Краснодар',
                'publication_date' => now(),
                'content' => '<p>Добро пожаловать в новый сайт ТФ ОМС Краснодарского края.</p>',
                'page_status' => 3,
                'page_of_type' => 2,
                'url' => '/',
                'template' => 'home',
                'images' => '["cms/news/images/image.png","cms/news/images/image.png"]',
                'attachments' => '[]',
            ]
        );

        // Гражданам
        $citizens = CmsPage::firstOrCreate(
            ['url' => '/grazhd.html'],
            [
                'parent_id' => null,
                'title' => 'Гражданам',
                'title_short' => 'Гражданам',
                'publication_date' => now(),
                'content' => '<p>Информация для граждан.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/grazhd.html',
                'template' => 'default',
            ]
        );

        // Пресс-центр
        $press = CmsPage::firstOrCreate(
            ['url' => '/press/'],
            [
                'parent_id' => null,
                'title' => 'Пресс-центр',
                'title_short' => 'Пресс-центр',
                'publication_date' => now(),
                'content' => '<p>Новости и публикации пресс-центра.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/press/',
                'template' => 'default',
            ]
        );

        // Демонстрационная новость
        $demoNews = CmsPage::firstOrCreate(
            ['url' => '/news/demo-news.html'],
            [
                'parent_id' => null,
                'title' => 'Демонстрационная новость',
                'title_short' => 'Демо новость',
                'publication_date' => now(),
                'content' => '<p>Это пример новости для проверки верстки.</p>',
                'page_status' => 3,
                'page_of_type' => 2,
                'url' => '/news/demo-news.html',
                'template' => 'news',
                'path' => null,
            ]
        );

        // Демонстрационный документ
        $demoDoc = CmsPage::firstOrCreate(
            ['url' => '/documents/demo-document.html'],
            [
                'parent_id' => null,
                'title' => 'Демонстрационный документ',
                'title_short' => 'Документ',
                'publication_date' => now(),
                'content' => '<p>Пример документа с возможностью скачивания.</p>',
                'page_status' => 3,
                'page_of_type' => 3,
                'url' => '/documents/demo-document.html',
                'template' => 'document',
            ]
        );

        // Медицинские организации
        $mo = CmsPage::firstOrCreate(
            ['url' => '/mo.html'],
            [
                'parent_id' => null,
                'title' => 'Медицинские организации',
                'title_short' => 'МО',
                'publication_date' => now(),
                'content' => '<p>Раздел для медицинских организаций.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/mo.html',
                'template' => 'default',
            ]
        );

        // Страховые медицинские организации
        $smo = CmsPage::firstOrCreate(
            ['url' => '/smedorg.html'],
            [
                'parent_id' => null,
                'title' => 'Страховые медицинские организации',
                'title_short' => 'СМО',
                'publication_date' => now(),
                'content' => '<p>Информация для СМО.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/smedorg.html',
                'template' => 'default',
            ]
        );

        // ТФОМС
        $tfoms = CmsPage::firstOrCreate(
            ['url' => '/tfoms.html'],
            [
                'parent_id' => null,
                'title' => 'ТФОМС',
                'title_short' => 'ТФОМС',
                'publication_date' => now(),
                'content' => '<p>Сведения о территориальном фонде ОМС.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/tfoms.html',
                'template' => 'default',
            ]
        );

        // КТФОМС
        $ktfoms = CmsPage::firstOrCreate(
            ['url' => '/ktfoms.html'],
            [
                'parent_id' => null,
                'title' => 'КТФОМС',
                'title_short' => 'КТФОМС',
                'publication_date' => now(),
                'content' => '<p>Краевой ТФОМС информация.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/ktfoms.html',
                'template' => 'default',
            ]
        );

        // Диспансеризация
        $dispans = CmsPage::firstOrCreate(
            ['url' => '/dispans/'],
            [
                'parent_id' => null,
                'title' => 'Диспансеризация',
                'title_short' => 'Диспансеризация',
                'publication_date' => now(),
                'content' => '<p>Программа диспансеризации граждан.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/dispans/',
                'template' => 'default',
            ]
        );

        // Виртуальная приёмная
        $virtualReception = CmsPage::firstOrCreate(
            ['url' => '/virtual-reception/'],
            [
                'parent_id' => null,
                'title' => 'Виртуальная приёмная',
                'title_short' => 'Виртуальная приёмная',
                'publication_date' => now(),
                'content' => '<p>Онлайн форма обращения.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/virtual-reception/',
                'template' => 'vr',
            ]
        );

        // Филиалы
        $branches = CmsPage::firstOrCreate(
            ['url' => '/branches.html'],
            [
                'parent_id' => null,
                'title' => 'Филиалы',
                'title_short' => 'Филиалы',
                'publication_date' => now(),
                'content' => '<p>Информация о филиалах ТФОМС.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/branches.html',
                'template' => 'default',
            ]
        );

        // Получение полиса ОМС
        $polis = CmsPage::firstOrCreate(
            ['url' => '/polis.html'],
            [
                'parent_id' => null,
                'title' => 'Получение полиса ОМС',
                'title_short' => 'Получение полиса ОМС',
                'publication_date' => now(),
                'content' => '<p>Как получить полис ОМС.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/polis.html',
                'template' => 'default',
            ]
        );

        // Выбор медицинской организации
        $chooseMo = CmsPage::firstOrCreate(
            ['url' => '/choose-mo.html'],
            [
                'parent_id' => null,
                'title' => 'Выбор медицинской организации',
                'title_short' => 'Выбор МО',
                'publication_date' => now(),
                'content' => '<p>Как выбрать медицинскую организацию.</p>',
                'page_status' => 3,
                'page_of_type' => 1,
                'url' => '/choose-mo.html',
                'template' => 'default',
            ]
        );

        // Карта сайта
        $sitemap = CmsPage::firstOrCreate(
            ['url' => '/sitemap.html'],
            [
                'parent_id' => null,
                'title' => 'Карта сайта',
                'title_short' => 'Карта сайта',
                'publication_date' => now(),
                'content' => '<p>Карта сайта.</p>',
                'page_status' => 3,
                'page_of_type' => 7,
                'url' => '/sitemap.html',
                'template' => 'default',
            ]
        );

        // Навигационные элементы
        $this->createMenuItems($navbar, $sidebar, $home, $press, $virtualReception, $citizens, $mo, $smo, $tfoms, $ktfoms, $dispans, $branches, $polis, $chooseMo);

        // Настройки CMS
        $this->createCmsSettings();
    }

    private function createMenuItems($navbar, $sidebar, $home, $press, $virtualReception, $citizens, $mo, $smo, $tfoms, $ktfoms, $dispans, $branches, $polis, $chooseMo): void
    {
        // Навигационная панель
        CmsMenuItem::firstOrCreate(
            ['menu_id' => $navbar->id, 'title' => 'Главная'],
            [
                'page_id' => $home->id,
                'sort_order' => 1,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $navbar->id, 'title' => 'Пресс-центр'],
            [
                'page_id' => $press->id,
                'sort_order' => 2,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $navbar->id, 'title' => 'Виртуальная приёмная'],
            [
                'page_id' => $virtualReception->id,
                'sort_order' => 3,
                'visible' => true,
            ]
        );

        // Боковая панель
        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'Гражданам'],
            [
                'page_id' => $citizens->id,
                'sort_order' => 1,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'Медицинским организациям'],
            [
                'page_id' => $mo->id,
                'sort_order' => 2,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'Страховым медицинским организациям'],
            [
                'page_id' => $smo->id,
                'sort_order' => 3,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'ТФОМС'],
            [
                'page_id' => $tfoms->id,
                'sort_order' => 4,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'КТФОМС'],
            [
                'page_id' => $ktfoms->id,
                'sort_order' => 5,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'Диспансеризация'],
            [
                'page_id' => $dispans->id,
                'sort_order' => 6,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'Виртуальная приёмная'],
            [
                'page_id' => $virtualReception->id,
                'sort_order' => 7,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'Филиалы'],
            [
                'page_id' => $branches->id,
                'sort_order' => 8,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'Получение полиса ОМС'],
            [
                'page_id' => $polis->id,
                'sort_order' => 9,
                'visible' => true,
            ]
        );

        CmsMenuItem::firstOrCreate(
            ['menu_id' => $sidebar->id, 'title' => 'Выбор медицинской организации'],
            [
                'page_id' => $chooseMo->id,
                'sort_order' => 10,
                'visible' => true,
            ]
        );
    }

    private function createCmsSettings(): void
    {
        // Баннеры
        CmsSetting::firstOrCreate(
            ['name' => 'LEFT_SIDEBAR_BANNERS'],
            [
                'description' => 'Баннеры в левом сайдбаре',
                'content' => '<a href="#" target="_blank" rel="noopener"><img src="/storage/cms/banners/image.png" alt="Баннер" /></a>',
                'visibility' => true,
            ]
        );

        CmsSetting::firstOrCreate(
            ['name' => 'RIGHT_SIDEBAR_BANNERS'],
            [
                'description' => 'Баннеры в правом сайдбаре',
                'content' => '<img src="/storage/cms/banners/image.png" alt="Баннер" />',
                'visibility' => true,
            ]
        );

        CmsSetting::firstOrCreate(
            ['name' => 'BOTTOM_BANNERS'],
            [
                'description' => 'Баннеры внизу страницы',
                'content' => '<img src="/storage/cms/banners/image.png" alt="Баннер" />',
                'visibility' => true,
            ]
        );

        // Меню
        CmsSetting::firstOrCreate(
            ['name' => 'RIGHT_SIDEBAR_MENU'],
            [
                'description' => 'Меню в правом сайдбаре',
                'content' => '<div class="content actual"><h4>Актуально</h4><ul><li><a href="#">Режим работы</a></li><li><a href="#">Контакты</a></li></ul></div>',
                'visibility' => true,
            ]
        );

        // MAP с контактами
        CmsSetting::firstOrCreate(
            ['name' => 'MAP'],
            [
                'description' => 'Карта сайта / блок на главной',
                'content' => '<div class="contacts-section" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0;"><h2 style="color: #333; margin-bottom: 20px; font-size: 24px; border-bottom: 2px solid #007bff; padding-bottom: 10px;">Контакты</h2><div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;"><div style="padding: 10px; background: white; border-radius: 6px; border-left: 4px solid #007bff;"><strong style="color: #007bff; display: block; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Телефон</strong>+7 (861) 222-22-22</div><div style="padding: 10px; background: white; border-radius: 6px; border-left: 4px solid #007bff;"><strong style="color: #007bff; display: block; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Email</strong>info@tfoms.ru</div><div style="padding: 10px; background: white; border-radius: 6px; border-left: 4px solid #007bff;"><strong style="color: #007bff; display: block; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Адрес</strong>350000, г. Краснодар, ул. Красная, 1</div><div style="padding: 10px; background: white; border-radius: 6px; border-left: 4px solid #007bff;"><strong style="color: #007bff; display: block; margin-bottom: 5px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Время работы</strong>Пн-Пт: 9:00-18:00, Сб: 9:00-13:00</div></div></div>',
                'visibility' => true,
            ]
        );

        // Внешние ссылки
        CmsSetting::firstOrCreate(
            ['name' => 'EXTERNAL_LINKS'],
            [
                'description' => 'Внешние ссылки',
                'content' => '<ul><li><a href="#"><img src="/legacy/image/healthy_russia.gif" alt="Здоровая Россия" /></a></li></ul>',
                'visibility' => true,
            ]
        );

        // Колонки
        CmsSetting::firstOrCreate(
            ['name' => 'LEFT_COLUMN'],
            [
                'description' => 'Левая колонка',
                'content' => '<h4>Контакты</h4><p>350000, г. Краснодар, ул. Красная, 1</p><p>Тел: +7 (861) 222-22-22</p>',
                'visibility' => true,
            ]
        );

        CmsSetting::firstOrCreate(
            ['name' => 'CENTER_COLUMN'],
            [
                'description' => 'Центральная колонка',
                'content' => '<h4>Разделы сайта</h4><ul><li><a href="/">Главная</a></li><li><a href="/press/">Пресс-центр</a></li></ul>',
                'visibility' => true,
            ]
        );

        CmsSetting::firstOrCreate(
            ['name' => 'RIGHT_COLUMN'],
            [
                'description' => 'Правая колонка',
                'content' => '<h4>Дополнительно</h4><p>Информация будет добавлена позднее.</p>',
                'visibility' => true,
            ]
        );

        // Контакты
        CmsSetting::firstOrCreate(
            ['name' => 'contact_phone'],
            [
                'description' => 'Контактный телефон',
                'content' => '+7 (861) 222-22-22',
                'visibility' => true,
            ]
        );

        CmsSetting::firstOrCreate(
            ['name' => 'contact_email'],
            [
                'description' => 'Контактный email',
                'content' => 'info@tfoms.ru',
                'visibility' => true,
            ]
        );

        CmsSetting::firstOrCreate(
            ['name' => 'contact_address'],
            [
                'description' => 'Контактный адрес',
                'content' => '350000, г. Краснодар, ул. Красная, 1',
                'visibility' => true,
            ]
        );

        CmsSetting::firstOrCreate(
            ['name' => 'contact_work_time'],
            [
                'description' => 'Время работы',
                'content' => 'Пн-Пт: 9:00-18:00, Сб: 9:00-13:00',
                'visibility' => true,
            ]
        );
    }
}
