<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentId extends Model
{
    protected $fillable = [
        'enrollment_id',
        'school_year',
        'photo_path',
        'photo_mime_type',
        'signature_path',
        'signature_mime_type',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_number',
        'front_output_path',
        'back_output_path',
        'front_template_id',
        'back_template_id',
        'status',
        'requirements_status',
        'submitted_at',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'generated_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
