<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'slug',
    'content',
    'excerpt',
    'featured_image',
    'image_alt',
    'eyebrow',
    'category',
    'tags',
    'author_id',
    'meta_description',
    'meta_keywords',
    'views',
    'published_at',
])]
class BlogPost extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'meta_keywords' => 'array',
            'published_at' => 'datetime',
            'views' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}