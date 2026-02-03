<?php

namespace Database\Seeders;

use App\Models\Cms\CmsMenu;
use App\Models\Cms\CmsMenuItem;
use App\Models\Cms\CmsPage;
use App\Models\Cms\CmsSetting;
use App\Models\User;
use App\PageStatus;
use App\PageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const string SYSTEM_USER = 'system';

    public function run(): void
    {
        Model::unguard();

        $this->seedUsers();

        $menus = $this->seedMenus();
        $pagesByUrl = $this->seedPages();

        $this->seedMenuTree(
            menu: $menus['navbar'],
            pagesByUrl: $pagesByUrl,
            tree: $this->navbarMenuTree(),
        );

        $this->seedMenuTree(
            menu: $menus['sidebar'],
            pagesByUrl: $pagesByUrl,
            tree: $this->sidebarMenuTree(),
        );

        $this->seedMenuTree(
            menu: $menus['current_information'],
            pagesByUrl: $pagesByUrl,
            tree: $this->currentInformationMenuTree(),
        );

        $this->seedSettings();
    }

    private function seedUsers(): void
    {
        $adminUsers = [
            [
                'email' => 'admin@admin.com',
                'name' => 'Admin User',
                'password' => Hash::make('YzoLPoW4t1sbTD'),
                'role' => 'admin',
            ],
            [
                'email' => 'root@admin.com',
                'name' => 'Admin User',
                'password' => Hash::make('cf1IOgW1QRPXKj'),
                'role' => 'admin',
            ],
            [
                'email' => 'editor@admin.com',
                'name' => 'Admin User',
                'password' => Hash::make('ekkqZxwbOe3cOR'),
                'role' => 'admin',
            ],
        ];

        foreach ($adminUsers as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $user['password'],
                    'role' => $user['role'],
                ],
            );
        }

        User::updateOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'Editor User',
                'password' => Hash::make('password123'),
                'role' => 'editor',
            ],
        );
    }

    /**
     * @return array{navbar: CmsMenu, sidebar: CmsMenu, current_information: CmsMenu}
     */
    private function seedMenus(): array
    {
        return [
            'navbar' => $this->upsertMenu(
                name: 'NAVBAR',
                title: 'Горизонтальное меню',
                maxDepth: 2,
                description: 'Основное меню в шапке сайта.',
            ),
            'sidebar' => $this->upsertMenu(
                name: 'SIDEBAR',
                title: 'Вертикальное меню',
                maxDepth: 3,
                description: 'Меню в левой колонке.',
            ),
            'current_information' => $this->upsertMenu(
                name: 'CURRENT_INFORMATION',
                title: 'Актуальная информация',
                maxDepth: 1,
                description: 'Блок ссылок справа (Актуальная информация).',
            ),
        ];
    }

    private function upsertMenu(string $name, string $title, int $maxDepth, ?string $description = null): CmsMenu
    {
        $menu = CmsMenu::query()->firstOrNew(['name' => $name]);

        $menu->fill([
            'title' => $title,
            'max_depth' => $maxDepth,
            'description' => $description,
            'update_date' => now(),
            'update_user' => self::SYSTEM_USER,
        ]);

        if (! $menu->exists) {
            $menu->fill([
                'create_date' => now(),
                'create_user' => self::SYSTEM_USER,
            ]);
        }

        $menu->save();

        return $menu;
    }

    /**
     * Создаем базовую структуру разделов сайта и подстраниц.
     *
     * Важно: URL в старом сайте часто лежали в корне (например, /polis.html),
     * но логически относятся к разделу "Гражданам". Поэтому мы создаем вложенность
     * через parent_id, даже если URL не содержит префикса раздела.
     *
     * @return array<string, CmsPage> pages indexed by url
     */
    private function seedPages(): array
    {
        $pagesByUrl = [];

        foreach ($this->pageTree() as $node) {
            $this->upsertPage($node, parent: null, pagesByUrl: $pagesByUrl);
        }

        return $pagesByUrl;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageTree(): array
    {
        return [
            [
                'url' => '/',
                'title' => 'Территориальный фонд обязательного медицинского страхования Краснодарского края',
                'title_short' => 'Главная',
                'meta_description' => 'Официальный сайт ТФОМС Краснодарского края.',
                'meta_keywords' => 'ТФОМС, ОМС, Краснодарский край',
                'template' => 'home',
                'page_of_type' => PageType::PAGE->value,
                'page_status' => PageStatus::PUBLISHED->value,
                'content' => '<p>Добро пожаловать на официальный сайт ТФОМС Краснодарского края.</p>',
            ],

            [
                'url' => '/grazhd.html',
                'title' => 'Гражданам',
                'title_short' => 'Гражданам',
                'template' => 'default',
                'page_of_type' => PageType::PAGE->value,
                'page_status' => PageStatus::PUBLISHED->value,
                'content' => '<p>Информация для граждан.</p>',
                'children' => [
                    [
                        'url' => '/polis.html',
                        'title' => 'Получение полиса ОМС',
                        'title_short' => 'Полис ОМС',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Как получить полис ОМС.</p>',
                    ],
                    [
                        'url' => '/choose-mo.html',
                        'title' => 'Выбор медицинской организации',
                        'title_short' => 'Выбор МО',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Как выбрать медицинскую организацию.</p>',
                    ],
                    [
                        'url' => '/faq',
                        'title' => 'Вопросы и ответы',
                        'title_short' => 'FAQ',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Раздел вопросов и ответов.</p>',
                    ],
                ],
            ],

            [
                'url' => '/mo.html',
                'title' => 'Медицинским организациям',
                'title_short' => 'МО',
                'template' => 'default',
                'page_of_type' => PageType::PAGE->value,
                'page_status' => PageStatus::PUBLISHED->value,
                'content' => '<p>Раздел для медицинских организаций.</p>',
                'children' => [
                    [
                        // пример старого URL
                        'url' => '/uchr_oms.html',
                        'title' => 'Реестр МО',
                        'title_short' => 'Реестр МО',
                        'template' => 'document',
                        'page_of_type' => PageType::DOCUMENT->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Загрузите документы реестра в админке: Контент → Страницы → Документы.</p>',
                    ],
                    [
                        // пример таблицы документов как на старом сайте
                        'url' => '/zakon9.html',
                        'title' => 'Нормативные документы (таблица)',
                        'title_short' => 'Нормативные',
                        'template' => 'document',
                        'page_of_type' => PageType::DOCUMENT->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Страница для большой таблицы документов (10–100000 файлов).</p>',
                    ],
                ],
            ],

            [
                'url' => '/smedorg.html',
                'title' => 'Страховым медицинским организациям',
                'title_short' => 'СМО',
                'template' => 'default',
                'page_of_type' => PageType::PAGE->value,
                'page_status' => PageStatus::PUBLISHED->value,
                'content' => '<p>Информация для страховых медицинских организаций.</p>',
                'children' => [
                    [
                        'url' => '/reestr-smo.html',
                        'title' => 'Реестр СМО',
                        'title_short' => 'Реестр СМО',
                        'template' => 'document',
                        'page_of_type' => PageType::DOCUMENT->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Загрузите документы реестра СМО в админке.</p>',
                    ],
                ],
            ],

            [
                'url' => '/tfoms.html',
                'title' => 'ТФОМС',
                'title_short' => 'ТФОМС',
                'template' => 'default',
                'page_of_type' => PageType::PAGE->value,
                'page_status' => PageStatus::PUBLISHED->value,
                'content' => '<p>Сведения о территориальном фонде ОМС.</p>',
                'children' => [
                    [
                        'url' => '/contacts.html',
                        'title' => 'Контакты',
                        'title_short' => 'Контакты',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Контактная информация.</p>',
                    ],
                    [
                        'url' => '/anti-corruption.html',
                        'title' => 'Противодействие коррупции',
                        'title_short' => 'Антикоррупция',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Информация о противодействии коррупции.</p>',
                    ],
                ],
            ],

            [
                'url' => '/ktfoms.html',
                'title' => 'КТФОМС',
                'title_short' => 'КТФОМС',
                'template' => 'default',
                'page_of_type' => PageType::PAGE->value,
                'page_status' => PageStatus::PUBLISHED->value,
                'content' => '<p>Краевой ТФОМС.</p>',
            ],

            [
                'url' => '/dispans',
                'title' => 'Диспансеризация',
                'title_short' => 'Диспансеризация',
                'template' => 'default',
                'page_of_type' => PageType::PAGE->value,
                'page_status' => PageStatus::PUBLISHED->value,
                'content' => '<p>Информация о диспансеризации.</p>',
                'children' => [
                    [
                        'url' => '/dispans/info.html',
                        'title' => 'Что такое диспансеризация',
                        'title_short' => 'Что это',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Описание программы диспансеризации.</p>',
                    ],
                    [
                        'url' => '/dispans/gde-proyti.html',
                        'title' => 'Где пройти диспансеризацию',
                        'title_short' => 'Где пройти',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Информация о местах прохождения.</p>',
                    ],
                ],
            ],

            [
                'url' => '/press',
                'title' => 'Пресс-центр',
                'title_short' => 'Пресс-центр',
                'template' => 'default',
                'page_of_type' => PageType::PAGE->value,
                'page_status' => PageStatus::PUBLISHED->value,
                'content' => '<p>Новости, документы и публикации.</p>',
                'children' => [
                    [
                        'url' => '/news',
                        'title' => 'Новости',
                        'title_short' => 'Новости',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Раздел новостей. Новости создавайте как тип "Новость" и указывайте родителем эту страницу.</p>',
                        'children' => [
                            [
                                'url' => '/news/demo-news.html',
                                'title' => 'Демонстрационная новость',
                                'title_short' => 'Демо новость',
                                'template' => 'news',
                                'page_of_type' => PageType::NEWS->value,
                                'page_status' => PageStatus::PUBLISHED->value,
                                'content' => '<p>Это пример новости для проверки верстки.</p>',
                                'images' => [],
                                'attachments' => [],
                            ],
                        ],
                    ],
                    [
                        'url' => '/documents',
                        'title' => 'Документы',
                        'title_short' => 'Документы',
                        'template' => 'default',
                        'page_of_type' => PageType::PAGE->value,
                        'page_status' => PageStatus::PUBLISHED->value,
                        'content' => '<p>Раздел документов. Документы создавайте как тип "Документ" и указывайте родителем эту страницу.</p>',
                        'children' => [
                            [
                                'url' => '/documents/demo-document.html',
                                'title' => 'Демонстрационный документ',
                                'title_short' => 'Документ',
                                'template' => 'document',
                                'page_of_type' => PageType::DOCUMENT->value,
                                'page_status' => PageStatus::PUBLISHED->value,
                                'content' => '<p>Пример страницы документа с таблицей загруженных файлов.</p>',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, CmsPage>  $pagesByUrl
     */
    private function upsertPage(array $data, ?CmsPage $parent, array &$pagesByUrl): CmsPage
    {
        /** @var string $url */
        $url = $this->normalizePageUrl((string) $data['url']);

        $candidates = [$url];

        if ($url !== '/') {
            $candidates[] = $url.'/';
        }

        $page = CmsPage::query()
            ->whereIn('url', $candidates)
            ->orderBy('id')
            ->first() ?? CmsPage::query()->make();

        $page->url = $url;
        $page->parent_id = $parent?->id;
        $page->title = (string) ($data['title'] ?? '');
        $page->title_short = $data['title_short'] ?? null;
        $page->meta_description = $data['meta_description'] ?? null;
        $page->meta_keywords = $data['meta_keywords'] ?? null;
        $page->template = $data['template'] ?? 'default';
        $pageTypeValue = (int) ($data['page_of_type'] ?? PageType::PAGE->value);
        $pageStatusValue = (int) ($data['page_status'] ?? PageStatus::PUBLISHED->value);

        $page->page_of_type = PageType::tryFrom($pageTypeValue) ?? PageType::PAGE;
        $page->page_status = PageStatus::tryFrom($pageStatusValue) ?? PageStatus::PUBLISHED;

        if (array_key_exists('images', $data) && is_array($data['images'])) {
            $page->images = $data['images'];
        }

        if (array_key_exists('attachments', $data) && is_array($data['attachments'])) {
            $page->attachments = $data['attachments'];
        }

        $content = $data['content'] ?? null;
        if (is_string($content) && blank($page->content)) {
            $page->content = $content;
        }

        if (! $page->exists) {
            $page->create_date = now();
            $page->create_user = self::SYSTEM_USER;
        }

        $page->save();

        $pagesByUrl[$url] = $page;

        /** @var array<int, array<string, mixed>> $children */
        $children = $data['children'] ?? [];

        foreach ($children as $childNode) {
            $this->upsertPage($childNode, parent: $page, pagesByUrl: $pagesByUrl);
        }

        return $page;
    }

    private function normalizePageUrl(string $url): string
    {
        $normalized = '/'.ltrim(trim($url), '/');

        if ($normalized === '/') {
            return '/';
        }

        return rtrim($normalized, '/');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function navbarMenuTree(): array
    {
        return [
            [
                'title' => 'Главная',
                'page_url' => '/',
            ],
            [
                'title' => 'Гражданам',
                'page_url' => '/grazhd.html',
                'children' => [
                    ['title' => 'Получение полиса ОМС', 'page_url' => '/polis.html'],
                    ['title' => 'Выбор медицинской организации', 'page_url' => '/choose-mo.html'],
                    ['title' => 'Вопросы и ответы', 'page_url' => '/faq'],
                ],
            ],
            [
                'title' => 'Медицинским организациям',
                'page_url' => '/mo.html',
                'children' => [
                    ['title' => 'Реестр МО', 'page_url' => '/uchr_oms.html'],
                    ['title' => 'Нормативные документы', 'page_url' => '/zakon9.html'],
                ],
            ],
            [
                'title' => 'СМО',
                'page_url' => '/smedorg.html',
                'children' => [
                    ['title' => 'Реестр СМО', 'page_url' => '/reestr-smo.html'],
                ],
            ],
            [
                'title' => 'ТФОМС',
                'page_url' => '/tfoms.html',
                'children' => [
                    ['title' => 'Контакты', 'page_url' => '/contacts.html'],
                    ['title' => 'Противодействие коррупции', 'page_url' => '/anti-corruption.html'],
                ],
            ],
            [
                'title' => 'Пресс-центр',
                'page_url' => '/press',
                'children' => [
                    ['title' => 'Новости', 'page_url' => '/news'],
                    ['title' => 'Документы', 'page_url' => '/documents'],
                    ['title' => 'RSS-канал', 'url' => '/rss.xml'],
                ],
            ],
            [
                'title' => 'Филиалы',
                'url' => '/branches',
            ],
            [
                'title' => 'Виртуальная приёмная',
                'url' => '/virtual-reception',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sidebarMenuTree(): array
    {
        return [
            [
                'title' => 'Гражданам',
                'page_url' => '/grazhd.html',
                'children' => [
                    ['title' => 'Получение полиса ОМС', 'page_url' => '/polis.html'],
                    ['title' => 'Выбор медицинской организации', 'page_url' => '/choose-mo.html'],
                    ['title' => 'Вопросы и ответы', 'page_url' => '/faq'],
                ],
            ],
            [
                'title' => 'Медицинским организациям',
                'page_url' => '/mo.html',
                'children' => [
                    ['title' => 'Реестр МО', 'page_url' => '/uchr_oms.html'],
                    ['title' => 'Нормативные документы', 'page_url' => '/zakon9.html'],
                ],
            ],
            [
                'title' => 'Страховым медицинским организациям',
                'page_url' => '/smedorg.html',
                'children' => [
                    ['title' => 'Реестр СМО', 'page_url' => '/reestr-smo.html'],
                ],
            ],
            [
                'title' => 'ТФОМС',
                'page_url' => '/tfoms.html',
                'children' => [
                    ['title' => 'Контакты', 'page_url' => '/contacts.html'],
                    ['title' => 'Противодействие коррупции', 'page_url' => '/anti-corruption.html'],
                ],
            ],
            [
                'title' => 'КТФОМС',
                'page_url' => '/ktfoms.html',
            ],
            [
                'title' => 'Диспансеризация',
                'page_url' => '/dispans',
                'children' => [
                    ['title' => 'Что такое диспансеризация', 'page_url' => '/dispans/info.html'],
                    ['title' => 'Где пройти', 'page_url' => '/dispans/gde-proyti.html'],
                ],
            ],
            [
                'title' => 'Пресс-центр',
                'page_url' => '/press',
                'children' => [
                    ['title' => 'Новости', 'page_url' => '/news'],
                    ['title' => 'Документы', 'page_url' => '/documents'],
                ],
            ],
            [
                'title' => 'Филиалы',
                'url' => '/branches',
            ],
            [
                'title' => 'Виртуальная приёмная',
                'url' => '/virtual-reception',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function currentInformationMenuTree(): array
    {
        return [
            [
                'title' => 'Новости',
                'page_url' => '/news',
            ],
            [
                'title' => 'Документы',
                'page_url' => '/documents',
            ],
            [
                'title' => 'Поиск по сайту',
                'url' => '/search',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @param  array<string, CmsPage>  $pagesByUrl
     */
    private function seedMenuTree(CmsMenu $menu, array $pagesByUrl, array $tree): void
    {
        // For a clean, predictable structure (no duplicates / wrong nesting),
        // we recreate menu items for seeded menus.
        CmsMenuItem::query()->where('menu_id', $menu->id)->delete();

        $sortOrder = 1;

        foreach ($tree as $node) {
            $this->upsertMenuItem($menu, $pagesByUrl, $node, sortOrder: $sortOrder, parent: null);
            $sortOrder++;
        }
    }

    /**
     * @param  array<string, CmsPage>  $pagesByUrl
     * @param  array<string, mixed>  $node
     */
    private function upsertMenuItem(CmsMenu $menu, array $pagesByUrl, array $node, int $sortOrder, ?CmsMenuItem $parent): CmsMenuItem
    {
        $title = (string) ($node['title'] ?? '');

        $pageId = null;

        if (isset($node['page_url'])) {
            $pageUrl = $this->normalizePageUrl((string) $node['page_url']);
            $pageId = $pagesByUrl[$pageUrl]->id ?? null;
        }

        $url = $node['url'] ?? null;
        $visible = array_key_exists('visible', $node) ? (bool) $node['visible'] : true;

        $item = CmsMenuItem::query()->firstOrNew([
            'menu_id' => $menu->id,
            'parent_id' => $parent?->id,
            'title' => $title,
        ]);

        $item->fill([
            'menu_id' => $menu->id,
            'parent_id' => $parent?->id,
            'title' => $title,
            'page_id' => $pageId,
            'url' => is_string($url) ? $url : null,
            'sort_order' => $sortOrder,
            'visible' => $visible,
            'update_date' => now(),
            'update_user' => self::SYSTEM_USER,
        ]);

        if (! $item->exists) {
            $item->fill([
                'create_date' => now(),
                'create_user' => self::SYSTEM_USER,
            ]);
        }

        $item->save();

        /** @var array<int, array<string, mixed>> $children */
        $children = $node['children'] ?? [];

        $childSortOrder = 1;

        foreach ($children as $childNode) {
            $this->upsertMenuItem($menu, $pagesByUrl, $childNode, sortOrder: $childSortOrder, parent: $item);
            $childSortOrder++;
        }

        return $item;
    }

    private function seedSettings(): void
    {
        $settings = [
            [
                'name' => 'LEFT_SIDEBAR_BANNERS',
                'description' => 'Баннеры в левом сайдбаре',
                'content' => '<a href="#" target="_blank" rel="noopener"><img src="/storage/cms/banners/image.png" alt="Баннер" /></a>',
                'visibility' => true,
            ],
            [
                'name' => 'RIGHT_SIDEBAR_BANNERS',
                'description' => 'Баннеры в правом сайдбаре',
                'content' => '<img src="/storage/cms/banners/image.png" alt="Баннер" />',
                'visibility' => true,
            ],
            [
                'name' => 'BOTTOM_BANNERS',
                'description' => 'Баннеры внизу страницы',
                'content' => '<img src="/storage/cms/banners/image.png" alt="Баннер" />',
                'visibility' => true,
            ],
            [
                'name' => 'RIGHT_SIDEBAR_MENU',
                'description' => 'Меню в правом сайдбаре',
                'content' => '<div class="content actual"><h4>Актуально</h4><ul><li><a href="/branches">Филиалы</a></li><li><a href="/contacts.html">Контакты</a></li></ul></div>',
                'visibility' => true,
            ],
            [
                'name' => 'MAP',
                'description' => 'HTML-блок на главной',
                'content' => '<p>Здесь может быть карта/контакты/баннеры.</p>',
                'visibility' => true,
            ],
            [
                'name' => 'EXTERNAL_LINKS',
                'description' => 'Внешние ссылки',
                'content' => '<ul><li><a href="#"><img src="/legacy/image/healthy_russia.gif" alt="Здоровая Россия" /></a></li></ul>',
                'visibility' => true,
            ],
            [
                'name' => 'LEFT_COLUMN',
                'description' => 'Левая колонка футера',
                'content' => '<h4>Контакты</h4><p>350020, г. Краснодар, ул. Красная, 178</p><p>Контакт-центр: 8-800-200-60-50</p>',
                'visibility' => true,
            ],
            [
                'name' => 'CENTER_COLUMN',
                'description' => 'Центральная колонка футера',
                'content' => '<h4>Разделы сайта</h4><ul><li><a href="/grazhd.html">Гражданам</a></li><li><a href="/mo.html">МО</a></li><li><a href="/press/">Пресс-центр</a></li></ul>',
                'visibility' => true,
            ],
            [
                'name' => 'RIGHT_COLUMN',
                'description' => 'Правая колонка футера',
                'content' => '<h4>Дополнительно</h4><p>RSS: <a href="/rss.xml" target="_blank" rel="noopener">/rss.xml</a></p>',
                'visibility' => true,
            ],
            [
                'name' => 'contact_phone',
                'description' => 'Контактный телефон',
                'content' => '8-800-200-60-50',
                'visibility' => true,
            ],
            [
                'name' => 'contact_email',
                'description' => 'Контактный email',
                'content' => 'info@kubanoms.ru',
                'visibility' => true,
            ],
            [
                'name' => 'contact_address',
                'description' => 'Контактный адрес',
                'content' => '350020, г. Краснодар, ул. Красная, 178',
                'visibility' => true,
            ],
            [
                'name' => 'contact_work_time',
                'description' => 'Время работы',
                'content' => 'Пн–Чт: 9:00–18:00, Пт: 9:00–16:45',
                'visibility' => true,
            ],
        ];

        foreach ($settings as $setting) {
            CmsSetting::updateOrCreate(
                ['name' => $setting['name']],
                [
                    'description' => $setting['description'],
                    'content' => $setting['content'],
                    'visibility' => (bool) $setting['visibility'],
                ],
            );
        }
    }
}
