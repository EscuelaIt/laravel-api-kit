<?php

declare(strict_types=1);

namespace EscuelaIT\Test\Database\Factories;

use EscuelaIT\Test\Fixtures\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'status' => 'published',
        ];
    }
}
