<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentTemplate extends Model
{
    protected $fillable = [
        'name',
        'file_path',
        'original_filename',
        'page_width',
        'page_height',
        'field_mappings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'page_width' => 'float',
            'page_height' => 'float',
            'field_mappings' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
