<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeConfiguration extends Model
{
    protected $fillable = [
        'course_code',
        'fee_type',
        'name',
        'basis',
        'amount',
        'subject_id',
        'applies_to',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}