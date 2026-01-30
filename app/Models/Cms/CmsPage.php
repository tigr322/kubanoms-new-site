<?php

namespace App\Models\Cms;

use App\PageStatus;
use App\PageType;
use Database\Factories\CmsPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CmsPage extends Model
{
    use HasFactory;

    protected $table = 'cms_page';

    protected $guarded = [];

    protected $casts = [
        'publication_date' => 'datetime',
        'create_date' => 'datetime',
        'update_date' => 'datetime',
        'delete_date' => 'datetime',
        'images' => 'array',
        'attachments' => 'array',
        'is_visible' => 'boolean',
        'page_status' => PageStatus::class,
        'page_of_type' => PageType::class,
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        // Автоматическая генерация URL при создании
        static::creating(function ($model) {
            if (empty($model->url) && !empty($model->title)) {
                $model->url = $model->generateUrlFromTitle();
            }

            // Автоматическая установка даты публикации
            if (empty($model->publication_date)) {
                $model->publication_date = Carbon::now();
            }

            // Автоматическая установка даты создания
            if (empty($model->create_date)) {
                $model->create_date = Carbon::now();
                $model->create_user = Auth::user()?->name ?? 'system';
            }
        });

        // Обновление URL при изменении заголовка (если URL не установлен вручную)
        static::updating(function ($model) {
            if (empty($model->url) && !empty($model->title)) {
                $model->url = $model->generateUrlFromTitle();
            }

            // Автоматическое обновление даты изменения
            $model->update_date = Carbon::now();
            $model->update_user = Auth::user()?->name ?? 'system';
        });
    }

    /**
     * Генерирует URL из заголовка
     */
    private function generateUrlFromTitle(): string
    {
        $slug = Str::slug($this->title, '-', 'ru');

        // Определяем префикс в зависимости от шаблона
        $prefix = match($this->template) {
            'news' => '/news/',
            'document' => '/documents/',
            'publication' => '/publications/',
            default => '/',
        };

        // Добавляем расширение .html для страниц новостей
        $extension = ($this->template === 'news') ? '.html' : '';

        return $prefix . $slug . $extension;
    }

    /**
     * Проверяет корректность URL
     */
    public function hasValidUrl(): bool
    {
        if (empty($this->url)) {
            return false;
        }

        // URL должен начинаться с /
        if (!str_starts_with($this->url, '/')) {
            return false;
        }

        // Для новостей должен быть правильный формат
        if ($this->template === 'news') {
            return str_starts_with($this->url, '/news/') && str_ends_with($this->url, '.html');
        }

        return true;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CmsPageDocument::class, 'page_id')
            ->where('is_visible', true)
            ->orderBy('order');
    }

    protected function casts(): array
    {
        return [
            'publication_date' => 'datetime',
            'images' => 'array',
            'attachments' => 'array',
        ];
    }

    protected static function newFactory(): CmsPageFactory
    {
        return CmsPageFactory::new();
    }
}
