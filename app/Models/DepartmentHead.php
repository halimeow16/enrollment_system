<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentHead extends Model
{
    use HasFactory;

    protected $fillable = ['course_code', 'name', 'title', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
