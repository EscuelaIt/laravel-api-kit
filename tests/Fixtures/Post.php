<?php

declare(strict_types=1);

namespace EscuelaIT\Test\Fixtures;

use EscuelaIT\APIKit\BuildsLikeQueries;
use EscuelaIT\Test\Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use BuildsLikeQueries;
    use HasFactory;

    protected $table = 'posts';

    protected $guarded = [];

    public function scopeGreaterThanId($query, $id)
    {
        return $query->where('id', '>', $id);
    }

    public function scopeSimilar($query, $keyword)
    {
        return $this->whereContains($query, ['title', 'status'], $keyword);
    }

    public function scopeTitleStartsWith($query, $value)
    {
        return $this->whereStartsWith($query, 'title', $value);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    protected static function newFactory()
    {
        return PostFactory::new();
    }
}
