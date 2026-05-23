<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'slug',
    'description',
    'full_description',
    'image',
    'gallery',
    'goal',
    'current',
    'status',
    'draw_date',
    'extraction_number',
    'category',
    'ticket_price',
    'tickets_available',
    'tickets_sold',
    'reservation_timeout_minutes',
    'organization_id',
    'rules',
    'numbers',
    'winner_info',
    'featured',
    'meta_description',
    'meta_keywords',
])]
class Raffle extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'numbers' => 'array',
            'winner_info' => 'array',
            'meta_keywords' => 'array',
            'goal' => 'decimal:2',
            'current' => 'decimal:2',
            'ticket_price' => 'decimal:2',
            'reservation_timeout_minutes' => 'integer',
            'draw_date' => 'datetime',
            'extraction_number' => 'integer',
            'featured' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'organization_id');
    }
}