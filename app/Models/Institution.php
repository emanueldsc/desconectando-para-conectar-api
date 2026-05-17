<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'logo', 'image', 'image_position', 'contact', 'status'])]
class Institution extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'contact' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}