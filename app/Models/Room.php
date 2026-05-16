<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'building', 'capacity', 'is_active'];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];
}
