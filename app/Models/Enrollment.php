<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_number', 'date_filed', 'school_year',
        'first_name', 'middle_name', 'last_name',
        'cellphone', 'email', 'last_school',
        'present_address', 'barangay', 'city', 'province',
        'date_of_birth', 'age', 'place_of_birth',
        'civil_status', 'gender', 'religion',
        'father_name', 'father_address', 'father_cpNumber',
        'mother_name', 'mother_address', 'mother_cpNumber',
        'course_code', 'course_name', 'year_level', 'semester',
        'department_head_name',
        'enrollment_status',
        'credentials',
    ];

    protected $casts = [
        'date_filed'    => 'date',
        'date_of_birth' => 'date',
        'age'           => 'integer',
        'credentials'   => 'array',
    ];

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'enrollment_subjects')
            ->withPivot(['lecture_units', 'laboratory_units', 'total_units'])
            ->withTimestamps();
    }

    public function studentId(): HasOne
    {
        return $this->hasOne(StudentId::class);
    }
}
