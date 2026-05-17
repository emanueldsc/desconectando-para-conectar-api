<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['blog_post_id', 'author', 'email', 'content', 'replies'])]
class Comment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'replies' => 'array',
        ];
    }

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}