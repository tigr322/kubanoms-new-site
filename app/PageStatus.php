<?php

namespace App;

enum PageStatus: int
{
    case DRAFT = 1;
    case MODERATION = 2;
    case PUBLISHED = 3;

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::MODERATION => 'На модерации',
            self::PUBLISHED => 'Опубликовано',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::MODERATION => 'warning',
            self::PUBLISHED => 'success',
        };
    }
}
