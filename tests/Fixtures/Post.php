<?php

namespace EscuelaIT\Test\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use EscuelaIT\Test\Database\Factories\PostFactory;
use EscuelaIT\Test\Fixtures\Comment;

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

        $keyword = '%' . $keyword . '%';
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', $keyword)
                ->orWhere('status', 'like', $keyword);
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
