<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'preview_image',
        'css_variables',
        'font_family',
        'is_default',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'css_variables' => 'array',
            'is_default' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
