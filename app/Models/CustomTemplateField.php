<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomTemplateField extends Model
{
    protected $fillable = [
        'scope',
        'key',
        'label',
        'input_type',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
