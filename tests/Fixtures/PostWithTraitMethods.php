<?php

declare(strict_types=1);

namespace EscuelaIT\Test\Fixtures;

class PostWithTraitMethods extends Post
{
    use PostTraitMethods;

    public function getForeignKey(): string
    {
        return 'post_id';
    }

    public function untypedOwnWipe()
    {
        return static::query()->delete();
    }

    public function untypedComments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function typedWipe(): void
    {
        static::query()->delete();
    }
}
