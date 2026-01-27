<?php

declare(strict_types=1);

namespace EscuelaIT\Test\Fixtures;

use EscuelaIT\Test\Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $guarded = [];

    public function scopeGreaterThanId($query, $id)
    {
        return $query->where('id', '>', $id);
    }

    public function scopeSimilar($query, $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        $keyword = '%'.$keyword.'%';

        return $query->where(function ($q) use ($keyword): void {
            $q->where('title', 'like', $keyword)
                ->orWhere('status', 'like', $keyword)
            ;
        });
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    protected static function newFactory()
    {
        return PostFactory::new();
    }
}
