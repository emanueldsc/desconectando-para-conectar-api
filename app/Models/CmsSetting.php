<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['banners', 'phrases', 'contact', 'socials', 'hero_button', 'home_reality', 'monthly_goal', 'pix'])]
class CmsSetting extends Model
{
    protected function casts(): array
    {
        return [
            'banners' => 'array',
            'phrases' => 'array',
            'contact' => 'array',
            'socials' => 'array',
            'hero_button' => 'array',
            'home_reality' => 'array',
            'monthly_goal' => 'decimal:2',
            'pix' => 'array',
        ];
    }
}
