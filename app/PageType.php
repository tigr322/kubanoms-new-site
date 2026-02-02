<?php

namespace App;

enum PageType: int
{
    case PAGE = 1;
    case NEWS = 2;
    case DOCUMENT = 3;
    case SITEMAP = 7;

    public function getLabel(): string
    {
        return match($this) {
            self::PAGE => 'Страница',
            self::NEWS => 'Новость',
            self::DOCUMENT => 'Документ',
            self::SITEMAP => 'Карта сайта',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PAGE => 'primary',
            self::NEWS => 'info',
            self::DOCUMENT => 'success',
            self::SITEMAP => 'warning',
        };
    }
}
