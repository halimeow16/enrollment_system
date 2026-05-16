<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = ['start_time', 'end_time', 'label', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
