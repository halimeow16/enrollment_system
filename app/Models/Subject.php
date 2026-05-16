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
        'description',
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
        'lecture_units' => 'decimal:1',
        'laboratory_units' => 'decimal:1',
        'total_units' => 'decimal:1',
        'is_active' => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(SubjectSchedule::class);
    }
}
