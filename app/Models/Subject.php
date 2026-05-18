<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'course_code',
        'year_level',
        'semester',
        'type',
        'lecture_units',
        'laboratory_units',
        'total_units',
        'is_active',
    ];

    protected $casts = [
        'lecture_units' => 'integer',
        'laboratory_units' => 'integer',
        'total_units' => 'integer',
        'is_active' => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(SubjectSchedule::class);
    }
}
