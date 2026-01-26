<?php

namespace EscuelaIT\Test\Database\Factories;

use EscuelaIT\Test\Fixtures\Comment;
use EscuelaIT\Test\Fixtures\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition()
    {
        return [
            'post_id' => Post::factory(),
            'content' => $this->faker->sentence,
        ];
    }
}
