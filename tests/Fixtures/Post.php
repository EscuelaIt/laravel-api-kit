<?php

namespace EscuelaIT\Test\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use EscuelaIT\Test\Database\Factories\PostFactory;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';

    protected $guarded = [];

    public function scopeGreaterThanId($query, $id)
    {
        return $query->where('id', '>', $id);
    }

    protected static function newFactory()
    {
        return PostFactory::new();
    }
}
