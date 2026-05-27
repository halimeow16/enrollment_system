<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'day_id',
        'time_slot_id',
        'room_id',
        'instructor',
        'schedule_type',
        'school_year',
        'archived_at',
        'archived_school_year',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(Day::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
