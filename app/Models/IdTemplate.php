<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdTemplate extends Model
{
    protected $fillable = [
        'name',
        'side',
        'school_year',
        'layout_config',
        'background_image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'layout_config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
