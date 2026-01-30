<?php

namespace Database\Factories;

use App\Models\Cms\CmsPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CmsPage>
 */
class CmsPageFactory extends Factory
{
    protected $model = CmsPage::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'title' => $title,
            'title_short' => $this->faker->words(2, true),
            'meta_description' => $this->faker->sentence(8),
            'meta_keywords' => $this->faker->words(5, true),
            'publication_date' => now(),
            'content' => '<p>'.$this->faker->paragraph(3).'</p>',
            'page_status' => 3,
            'page_of_type' => 1,
            'url' => '/'.str($title)->slug().'.html',
            'template' => 'default',
            'images' => [],
            'attachments' => [],
        ];
    }
}
