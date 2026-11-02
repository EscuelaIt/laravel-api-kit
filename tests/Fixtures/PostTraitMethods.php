<?php

declare(strict_types=1);

namespace EscuelaIT\Test\Fixtures;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait PostTraitMethods
{
    public function untypedWipe()
    {
        return static::query()->delete();
    }

    public function typedComments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
