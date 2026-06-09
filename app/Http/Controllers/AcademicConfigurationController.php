<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Day;
use App\Models\AppSetting;
use App\Models\CustomTemplateField;
use App\Models\DepartmentHead;
use App\Models\Enrollment;
use App\Models\EnrollmentTemplate;
use App\Models\FeeConfiguration;
use App\Models\IdTemplate;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicConfigurationController extends Controller
{
    public function storeSubject(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateSubject($request);
        $data = $this->applyFixedSubjectUnits($data);

        $subject = Subject::create($data);
        ActivityLog::record('subject_created', $subject, [], $subject->only([
            'code', 'name', 'course_code', 'year_level', 'semester', 'type', 'lecture_units', 'laboratory_units', 'total_units',
        ]), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subject added.',
                'subject' => $this->subjectPayload($subject),
            ], 201);
        }

        return back()->with('success', 'Subject added.');
    }

    public function updateAcademicYear(Request $request): JsonResponse
    {
        $data = $request->validate([
            'academic_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
        ], [
            'academic_year.regex' => 'Use the format YYYY-YYYY, for example 2026-2027.',
        ]);

        $oldAcademicYear = AppSetting::getValue('academic_year', '2026-2027');
        $archiveCounts = ['enrollments' => 0, 'schedules' => 0];

        DB::transaction(function () use ($data, $oldAcademicYear, &$archiveCounts): void {
            if ($oldAcademicYear && $oldAcademicYear !== $data['academic_year']) {
                $archiveCounts['enrollments'] = Enrollment::whereNull('archived_at')
                    ->where('school_year', $oldAcademicYear)
                    ->update([
                        'archived_at' => now(),
                        'archived_school_year' => $oldAcademicYear,
                    ]);

                $archiveCounts['schedules'] = SubjectSchedule::whereNull('archived_at')
                    ->where(fn ($query) => $query
                        ->where('school_year', $oldAcademicYear)
                        ->orWhereNull('school_year'))
                    ->update([
                        'school_year' => $oldAcademicYear,
                        'archived_at' => now(),
                        'archived_school_year' => $oldAcademicYear,
                    ]);
            }

            AppSetting::setValue('academic_year', $data['academic_year']);
        });

        ActivityLog::record('academic_year_updated', null, [
            'academic_year' => $oldAcademicYear,
        ], [
            'academic_year' => $data['academic_year'],
            'archived_enrollments' => $archiveCounts['enrollments'],
            'archived_schedules' => $archiveCounts['schedules'],
        ], $request);

        return response()->json([
            'message' => 'Academic year updated.',
            'academic_year' => $data['academic_year'],
            'archived_enrollments' => $archiveCounts['enrollments'],
            'archived_schedules' => $archiveCounts['schedules'],
        ]);
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse|JsonResponse
    {
        $data = $this->validateSubject($request, $subject);
        $data = $this->applyFixedSubjectUnits($data);

        $oldValues = $subject->only(['code', 'name', 'course_code', 'year_level', 'semester', 'type', 'lecture_units', 'laboratory_units', 'total_units']);
        $subject->update($data);
        ActivityLog::record('subject_updated', $subject, $oldValues, $subject->fresh()->only([
            'code', 'name', 'course_code', 'year_level', 'semester', 'type', 'lecture_units', 'laboratory_units', 'total_units',
        ]), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subject updated.',
                'subject' => $this->subjectPayload($subject->fresh()),
            ]);
        }

        return back()->with('success', 'Subject updated.');
    }

    public function destroySubject(Request $request, Subject $subject): RedirectResponse|JsonResponse
    {
        $oldValues = $subject->only(['code', 'name', 'course_code', 'year_level', 'semester', 'type']);
        $subject->delete();
        ActivityLog::record('subject_removed', $subject, $oldValues, [], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subject removed.',
                'subject_id' => $subject->id,
            ]);
        }

        return back()->with('success', 'Subject removed.');
    }

    public function storeDay(Request $request): RedirectResponse|JsonResponse
    {
        $day = Day::updateOrCreate(
            ['name' => $request->validate(['name' => ['required', 'string', 'max:50']])['name']],
            ['is_active' => true, 'sort_order' => Day::max('sort_order') + 1]
        );
        ActivityLog::record('schedule_day_saved', $day, [], $day->only(['name', 'is_active', 'sort_order']), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Day saved.',
                'day' => ['id' => $day->id, 'name' => $day->name],
            ]);
        }

        return back()->with('success', 'Day saved.');
    }

    public function storeRoom(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $room = Room::updateOrCreate(['name' => $data['name']], $data + ['is_active' => true]);
        ActivityLog::record('schedule_room_saved', $room, [], $room->only(['name', 'is_active']), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Room saved.',
                'room' => [
                    'id' => $room->id,
                    'name' => $room->name,
                ],
            ]);
        }

        return back()->with('success', 'Room saved.');
    }

    public function destroyRoom(Request $request, Room $room): RedirectResponse|JsonResponse
    {
        $oldValues = $room->only(['name', 'is_active']);
        $room->update(['is_active' => false]);
        ActivityLog::record('schedule_room_removed', $room, $oldValues, $room->fresh()->only(['name', 'is_active']), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Room removed.',
                'room_id' => $room->id,
            ]);
        }

        return back()->with('success', 'Room removed.');
    }

    public function storeInstructor(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateInstructor($request);
        $data['name'] = Instructor::displayName($data);
        $this->ensureUniqueInstructorName($data['name'], activeOnly: true);

        $instructor = Instructor::updateOrCreate(['name' => $data['name']], $data + ['is_active' => true]);
        ActivityLog::record('schedule_instructor_saved', $instructor, [], $instructor->only([
            'title', 'first_name', 'middle_initial', 'last_name', 'name', 'is_active',
        ]), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Instructor saved.',
                'instructor' => $this->instructorPayload($instructor),
            ], 201);
        }

        return back()->with('success', 'Instructor saved.');
    }

    public function updateInstructor(Request $request, Instructor $instructor): RedirectResponse|JsonResponse
    {
        $data = $this->validateInstructor($request);
        $data['name'] = Instructor::displayName($data);
        $this->ensureUniqueInstructorName($data['name'], $instructor);

        $oldName = $instructor->name;
        $oldValues = $instructor->only(['title', 'first_name', 'middle_initial', 'last_name', 'name', 'is_active']);
        $instructor->update($data + ['is_active' => true]);

        if ($oldName !== $instructor->name) {
            SubjectSchedule::where('instructor', $oldName)
                ->whereNull('archived_at')
                ->update(['instructor' => $instructor->name]);
        }

        ActivityLog::record('schedule_instructor_updated', $instructor, $oldValues, $instructor->fresh()->only([
            'title', 'first_name', 'middle_initial', 'last_name', 'name', 'is_active',
        ]), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Instructor updated.',
                'instructor' => $this->instructorPayload($instructor->fresh()),
                'old_name' => $oldName,
            ]);
        }

        return back()->with('success', 'Instructor updated.');
    }

    public function destroyInstructor(Request $request, Instructor $instructor): RedirectResponse|JsonResponse
    {
        $oldValues = $instructor->only(['title', 'first_name', 'middle_initial', 'last_name', 'name', 'is_active']);
        $instructor->update(['is_active' => false]);
        ActivityLog::record('schedule_instructor_removed', $instructor, $oldValues, $instructor->fresh()->only([
            'title', 'first_name', 'middle_initial', 'last_name', 'name', 'is_active',
        ]), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Instructor removed.',
                'instructor_id' => $instructor->id,
            ]);
        }

        return back()->with('success', 'Instructor removed.');
    }

    public function storeTimeSlot(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $slot = TimeSlot::updateOrCreate(
            ['start_time' => $data['start_time'], 'end_time' => $data['end_time']],
            $data + [
                'label' => null,
                'is_active' => true,
            ]
        );
        ActivityLog::record('schedule_time_slot_saved', $slot, [], $slot->only(['start_time', 'end_time', 'label', 'is_active']), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Time slot saved.',
                'time_slot' => [
                    'id' => $slot->id,
                    'label' => $slot->label ?? ($slot->start_time . ' - ' . $slot->end_time),
                ],
            ]);
        }

        return back()->with('success', 'Time slot saved.');
    }

    public function storeSchedule(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'day_ids' => ['required', 'array', 'min:1'],
            'day_ids.*' => ['required', 'exists:days,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room_name' => ['required', 'string', 'max:80', Rule::exists('rooms', 'name')->where('is_active', true)],
            'instructor' => ['required', 'string', 'max:120', Rule::exists('instructors', 'name')->where('is_active', true)],
            'schedule_type' => ['required', Rule::in(['LEC', 'LAB'])],
            'schedule_for' => ['nullable', 'string', 'max:80'],
        ]);
        $dayIds = collect($data['day_ids'])->map(fn ($dayId) => (int) $dayId)->unique()->values();
        $data['schedule_for'] = $this->normalizeScheduleFor($data['schedule_for'] ?? null);
        $academicYear = AppSetting::getValue('academic_year', '2026-2027');

        $subject = Subject::findOrFail($data['subject_id']);
        $timeSlot = TimeSlot::updateOrCreate(
            [
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ],
            [
                'label' => null,
                'is_active' => true,
            ]
        );
        $room = Room::where('name', trim($data['room_name']))->where('is_active', true)->firstOrFail();
        $existingSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->where('subject_id', $data['subject_id'])
            ->where('schedule_type', $data['schedule_type'])
            ->where('schedule_for', $data['schedule_for'])
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->where('school_year', $academicYear)->orWhereNull('school_year'))
            ->get();
        $existingSchedule = $existingSchedules->first();
        $shouldOverwrite = $request->boolean('overwrite_schedule');
        $shouldMerge = $request->boolean('merge_schedule');
        $shouldAddAdditional = $request->boolean('additional_schedule');

        $overlappingSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->when($existingSchedules->isNotEmpty() && $shouldOverwrite, fn ($query) => $query->whereNotIn('id', $existingSchedules->pluck('id')))
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->where('school_year', $academicYear)->orWhereNull('school_year'))
            ->whereIn('day_id', $dayIds)
            ->whereHas('timeSlot', function ($query) use ($timeSlot) {
                $query->where('start_time', '<', $timeSlot->end_time)
                    ->where('end_time', '>', $timeSlot->start_time);
            })
            ->get();

        $mergeCandidates = $overlappingSchedules
            ->filter(fn (SubjectSchedule $schedule) => $this->isScheduleMergeCandidate($schedule, $subject, $data, $room, $timeSlot))
            ->values();

        if ($mergeCandidates->isNotEmpty() && ! $shouldMerge) {
            $currentScheduledClasses = $mergeCandidates
                ->map(fn (SubjectSchedule $schedule) => $this->scheduleClassLabel($schedule))
                ->unique()
                ->values()
                ->implode(', ');
            $message = "This schedule matches {$currentScheduledClasses}. Type merge to combine this class with the current scheduled class.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'requires_merge_confirmation' => true,
                    'current_scheduled_class' => $currentScheduledClasses,
                    'schedule' => $this->schedulePayload($mergeCandidates->first()),
                ], 409);
            }

            return back()->withErrors(['schedule' => $message])->withInput();
        }

        $conflicts = $overlappingSchedules
            ->map(function (SubjectSchedule $schedule) use ($data, $subject, $room, $timeSlot, $shouldMerge, $dayIds, $academicYear) {
                $newSubjectLabel = $this->subjectScheduleLabel($subject, $data['schedule_type']);
                $existingSubjectLabel = $this->subjectScheduleLabel($schedule->subject, $schedule->schedule_type);

                if ($shouldMerge && $this->isScheduleMergeCandidate($schedule, $subject, $data, $room, $timeSlot)) {
                    return null;
                }

                if ($this->isCombinedLectureLabCandidate($schedule, $subject, $data, $room, $timeSlot, $dayIds, $academicYear)) {
                    return null;
                }

                if ((int) $schedule->room_id === (int) $room->id) {
                    return "Room {$schedule->room->name} is already used by {$existingSubjectLabel}.";
                }

                if (strtolower(trim((string) $schedule->instructor)) === strtolower(trim($data['instructor']))) {
                    return "{$data['instructor']} is already assigned to {$existingSubjectLabel}.";
                }

                if ((int) $schedule->subject_id === (int) $data['subject_id']) {
                    if ($this->scheduleGroupsConflict($schedule->schedule_for, $data['schedule_for'])) {
                        return "{$newSubjectLabel} for {$data['schedule_for']} already has an overlapping schedule.";
                    }

                    return null;
                }

                $scheduledSubject = $schedule->subject;
                if (
                    $scheduledSubject
                    && $scheduledSubject->course_code === $subject->course_code
                    && $scheduledSubject->year_level === $subject->year_level
                    && $scheduledSubject->semester === $subject->semester
                    && $this->scheduleGroupsConflict($schedule->schedule_for, $data['schedule_for'])
                ) {
                    return "{$subject->course_code} {$subject->year_level} {$subject->semester} {$data['schedule_for']} already has {$existingSubjectLabel} at that time.";
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($conflicts->isNotEmpty()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $conflicts->implode(' '),
                ], 422);
            }

            return back()->withErrors(['schedule' => $conflicts->implode(' ')])->withInput();
        }

        if ($existingSchedule && ! $shouldOverwrite && ! $shouldAddAdditional) {
            $message = $this->subjectScheduleLabel($subject, $data['schedule_type']) . " for {$data['schedule_for']} already has a schedule. Add this as another meeting or replace the existing schedule?";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'requires_additional_confirmation' => true,
                    'schedule' => $this->schedulePayload($existingSchedule),
                ], 409);
            }

            return back()->withErrors(['schedule' => $message])->withInput();
        }

        $removedScheduleIds = [];
        if ($existingSchedules->isNotEmpty() && $shouldOverwrite) {
            $oldValues = $existingSchedules->map(fn (SubjectSchedule $schedule) => [
                'subject' => $schedule->subject->code,
                'day' => $schedule->day->name,
                'time' => $this->scheduleTimeLabel($schedule),
                'room' => $schedule->room->name,
                'instructor' => $schedule->instructor,
                'schedule_type' => $schedule->schedule_type,
                'schedule_for' => $schedule->schedule_for,
            ])->values()->all();
            $removedScheduleIds = $existingSchedules->pluck('id')->values()->all();
            SubjectSchedule::whereIn('id', $removedScheduleIds)->delete();
            $message = 'Schedule replaced.';
            $statusCode = 200;
            $logAction = 'subject_schedule_replaced';
        } elseif ($mergeCandidates->isNotEmpty() && $shouldMerge) {
            $oldValues = $mergeCandidates->map(fn (SubjectSchedule $schedule) => [
                'subject' => $schedule->subject->code,
                'class' => $this->scheduleClassLabel($schedule),
                'day' => $schedule->day->name,
                'time' => $this->scheduleTimeLabel($schedule),
                'room' => $schedule->room->name,
                'instructor' => $schedule->instructor,
                'schedule_type' => $schedule->schedule_type,
                'schedule_for' => $schedule->schedule_for,
            ])->values()->all();
            $message = 'Schedule merged.';
            $statusCode = 201;
            $logAction = 'subject_schedule_merged';
        } else {
            $oldValues = [];
            $message = 'Schedule assigned.';
            $statusCode = 201;
            $logAction = 'subject_schedule_assigned';
        }

        $unitValue = $existingSchedules->isNotEmpty() && $shouldAddAdditional
            ? $this->prepareAdditionalScheduleUnits($existingSchedules, $subject, $data['schedule_type'])
            : $this->initialScheduleUnitValue($subject, $data['schedule_type'], $timeSlot);

        $schedules = $dayIds->map(function (int $dayId) use ($data, $timeSlot, $room, $unitValue) {
            return SubjectSchedule::create([
                'subject_id' => $data['subject_id'],
                'day_id' => $dayId,
                'time_slot_id' => $timeSlot->id,
                'room_id' => $room->id,
                'instructor' => $data['instructor'],
                'schedule_type' => $data['schedule_type'],
                'schedule_for' => $data['schedule_for'],
                'unit_value' => $unitValue,
                'school_year' => AppSetting::getValue('academic_year', '2026-2027'),
            ])->load(['subject', 'day', 'timeSlot', 'room']);
        });
        $schedule = $schedules->first();

        ActivityLog::record($logAction, $schedule, $oldValues, [
            'subject' => $schedule->subject->code,
            'days' => $schedules->pluck('day.name')->implode(', '),
            'time' => $this->scheduleTimeLabel($schedule),
            'room' => $schedule->room->name,
            'instructor' => $schedule->instructor,
            'schedule_type' => $schedule->schedule_type,
            'schedule_for' => $schedule->schedule_for,
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'schedule' => $this->schedulePayload($schedule),
                'schedules' => $schedules->map(fn (SubjectSchedule $schedule) => $this->schedulePayload($schedule))->values(),
                'removed_schedule_ids' => $removedScheduleIds,
                'overwritten' => $existingSchedules->isNotEmpty() && $shouldOverwrite,
                'additional' => $existingSchedules->isNotEmpty() && $shouldAddAdditional,
                'merged' => $mergeCandidates->isNotEmpty() && $shouldMerge,
                'room' => [
                    'id' => $room->id,
                    'name' => $room->name,
                ],
                'time_slot' => [
                    'id' => $timeSlot->id,
                    'label' => $timeSlot->label ?? ($timeSlot->start_time . ' - ' . $timeSlot->end_time),
                ],
            ], $statusCode);
        }

        return back()->with('success', $message);
    }

    public function updateSchedule(Request $request, SubjectSchedule $schedule): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'day_ids' => ['required', 'array', 'min:1'],
            'day_ids.*' => ['required', 'exists:days,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room_name' => ['required', 'string', 'max:80', Rule::exists('rooms', 'name')->where('is_active', true)],
            'instructor' => ['required', 'string', 'max:120', Rule::exists('instructors', 'name')->where('is_active', true)],
            'schedule_type' => ['required', Rule::in(['LEC', 'LAB'])],
            'schedule_for' => ['nullable', 'string', 'max:80'],
        ]);
        $dayIds = collect($data['day_ids'])->map(fn ($dayId) => (int) $dayId)->unique()->values();
        $data['schedule_for'] = $this->normalizeScheduleFor($data['schedule_for'] ?? null);
        $academicYear = AppSetting::getValue('academic_year', '2026-2027');
        $shouldMerge = $request->boolean('merge_schedule');

        $subject = Subject::findOrFail($data['subject_id']);
        $schedule->load(['subject', 'day', 'timeSlot', 'room']);
        $relatedSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->where('subject_id', $schedule->subject_id)
            ->where('schedule_for', $schedule->schedule_for ?: 'Whole Class')
            ->where('time_slot_id', $schedule->time_slot_id)
            ->where('room_id', $schedule->room_id)
            ->where('instructor', $schedule->instructor)
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->where('school_year', $academicYear)->orWhereNull('school_year'))
            ->get();
        $relatedScheduleIds = $relatedSchedules->pluck('id');
        $timeSlot = TimeSlot::updateOrCreate(
            [
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ],
            [
                'label' => null,
                'is_active' => true,
            ]
        );
        $room = Room::where('name', trim($data['room_name']))->where('is_active', true)->firstOrFail();

        $overlappingSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->whereNotIn('id', $relatedScheduleIds)
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->where('school_year', $academicYear)->orWhereNull('school_year'))
            ->whereIn('day_id', $dayIds)
            ->whereHas('timeSlot', function ($query) use ($timeSlot) {
                $query->where('start_time', '<', $timeSlot->end_time)
                    ->where('end_time', '>', $timeSlot->start_time);
            })
            ->get();

        $mergeCandidates = $overlappingSchedules
            ->filter(fn (SubjectSchedule $existingSchedule) => $this->isScheduleMergeCandidate($existingSchedule, $subject, $data, $room, $timeSlot))
            ->values();

        if ($mergeCandidates->isNotEmpty() && ! $shouldMerge) {
            $currentScheduledClasses = $mergeCandidates
                ->map(fn (SubjectSchedule $existingSchedule) => $this->scheduleClassLabel($existingSchedule))
                ->unique()
                ->values()
                ->implode(', ');
            $message = "This schedule matches {$currentScheduledClasses}. Type merge to combine this class with the current scheduled class.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'requires_merge_confirmation' => true,
                    'current_scheduled_class' => $currentScheduledClasses,
                    'schedule' => $this->schedulePayload($mergeCandidates->first()),
                ], 409);
            }

            return back()->withErrors(['schedule' => $message])->withInput();
        }

        $conflicts = $overlappingSchedules
            ->map(function (SubjectSchedule $existingSchedule) use ($data, $subject, $room, $timeSlot, $shouldMerge, $dayIds, $academicYear) {
                $newSubjectLabel = $this->subjectScheduleLabel($subject, $data['schedule_type']);
                $existingSubjectLabel = $this->subjectScheduleLabel($existingSchedule->subject, $existingSchedule->schedule_type);

                if ($shouldMerge && $this->isScheduleMergeCandidate($existingSchedule, $subject, $data, $room, $timeSlot)) {
                    return null;
                }

                if ($this->isCombinedLectureLabCandidate($existingSchedule, $subject, $data, $room, $timeSlot, $dayIds, $academicYear)) {
                    return null;
                }

                if ((int) $existingSchedule->room_id === (int) $room->id) {
                    return "Room {$existingSchedule->room->name} is already used by {$existingSubjectLabel}.";
                }

                if (strtolower(trim((string) $existingSchedule->instructor)) === strtolower(trim($data['instructor']))) {
                    return "{$data['instructor']} is already assigned to {$existingSubjectLabel}.";
                }

                if ((int) $existingSchedule->subject_id === (int) $data['subject_id']) {
                    if ($this->scheduleGroupsConflict($existingSchedule->schedule_for, $data['schedule_for'])) {
                        return "{$newSubjectLabel} for {$data['schedule_for']} already has an overlapping schedule.";
                    }

                    return null;
                }

                $scheduledSubject = $existingSchedule->subject;
                if (
                    $scheduledSubject
                    && $scheduledSubject->course_code === $subject->course_code
                    && $scheduledSubject->year_level === $subject->year_level
                    && $scheduledSubject->semester === $subject->semester
                    && $this->scheduleGroupsConflict($existingSchedule->schedule_for, $data['schedule_for'])
                ) {
                    return "{$subject->course_code} {$subject->year_level} {$subject->semester} {$data['schedule_for']} already has {$existingSubjectLabel} at that time.";
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($conflicts->isNotEmpty()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $conflicts->implode(' '),
                ], 422);
            }

            return back()->withErrors(['schedule' => $conflicts->implode(' ')])->withInput();
        }

        $oldValues = $relatedSchedules->map(fn (SubjectSchedule $schedule) => [
            'subject' => $schedule->subject->code,
            'day' => $schedule->day->name,
            'time' => $this->scheduleTimeLabel($schedule),
            'room' => $schedule->room->name,
            'instructor' => $schedule->instructor,
            'schedule_type' => $schedule->schedule_type,
            'schedule_for' => $schedule->schedule_for,
        ])->values()->all();
        $message = $mergeCandidates->isNotEmpty() && $shouldMerge ? 'Schedule merged.' : 'Schedule updated.';
        $logAction = $mergeCandidates->isNotEmpty() && $shouldMerge ? 'subject_schedule_merged' : 'subject_schedule_updated';
        $removedScheduleIds = $relatedScheduleIds->values()->all();
        SubjectSchedule::whereIn('id', $removedScheduleIds)->delete();

        $scheduleTypes = $relatedSchedules
            ->pluck('schedule_type')
            ->map(fn (?string $type) => $type ?: 'LEC')
            ->uniqueStrict()
            ->values();
        if ($scheduleTypes->count() <= 1) {
            $scheduleTypes = collect([$data['schedule_type']]);
        }

        $schedules = $scheduleTypes->flatMap(function (string $scheduleType) use ($dayIds, $relatedSchedules, $subject, $data, $timeSlot, $room) {
            $unitValue = $relatedSchedules
                ->where('schedule_type', $scheduleType)
                ->pluck('unit_value')
                ->filter(fn ($value) => $value !== null)
                ->first() ?? $this->initialScheduleUnitValue($subject, $scheduleType, $timeSlot);

            return $dayIds->map(function (int $dayId) use ($data, $timeSlot, $room, $scheduleType, $unitValue) {
                return SubjectSchedule::create([
                    'subject_id' => $data['subject_id'],
                    'day_id' => $dayId,
                    'time_slot_id' => $timeSlot->id,
                    'room_id' => $room->id,
                    'instructor' => $data['instructor'],
                    'schedule_type' => $scheduleType,
                    'schedule_for' => $data['schedule_for'],
                    'unit_value' => $unitValue,
                    'school_year' => AppSetting::getValue('academic_year', '2026-2027'),
                ])->load(['subject', 'day', 'timeSlot', 'room']);
            });
        })->values();
        $schedule = $schedules->first();

        ActivityLog::record($logAction, $schedule, $oldValues, [
            'subject' => $schedule->subject->code,
            'days' => $schedules->pluck('day.name')->implode(', '),
            'time' => $this->scheduleTimeLabel($schedule),
            'room' => $schedule->room->name,
            'instructor' => $schedule->instructor,
            'schedule_type' => $schedule->schedule_type,
            'schedule_for' => $schedule->schedule_for,
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'schedule' => $this->schedulePayload($schedule),
                'schedules' => $schedules->map(fn (SubjectSchedule $schedule) => $this->schedulePayload($schedule))->values(),
                'removed_schedule_ids' => $removedScheduleIds,
                'merged' => $mergeCandidates->isNotEmpty() && $shouldMerge,
                'room' => [
                    'id' => $room->id,
                    'name' => $room->name,
                ],
                'time_slot' => [
                    'id' => $timeSlot->id,
                    'label' => $timeSlot->label ?? ($timeSlot->start_time . ' - ' . $timeSlot->end_time),
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    public function destroySchedule(Request $request, SubjectSchedule $schedule): RedirectResponse|JsonResponse
    {
        $schedule->load(['subject', 'day', 'timeSlot', 'room']);
        $relatedSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->where('subject_id', $schedule->subject_id)
            ->where('schedule_for', $schedule->schedule_for ?: 'Whole Class')
            ->where('time_slot_id', $schedule->time_slot_id)
            ->where('room_id', $schedule->room_id)
            ->where('instructor', $schedule->instructor)
            ->whereNull('archived_at')
            ->where(fn ($query) => $query
                ->where('school_year', AppSetting::getValue('academic_year', '2026-2027'))
                ->orWhereNull('school_year'))
            ->get();
        $ids = $relatedSchedules->pluck('id')->values()->all();
        $oldValues = $relatedSchedules->map(fn (SubjectSchedule $schedule) => [
            'subject' => $schedule->subject->code,
            'day' => $schedule->day->name,
            'time' => $this->scheduleTimeLabel($schedule),
            'room' => $schedule->room->name,
            'instructor' => $schedule->instructor,
            'schedule_type' => $schedule->schedule_type,
            'schedule_for' => $schedule->schedule_for,
        ])->values()->all();
        SubjectSchedule::whereIn('id', $ids)->delete();
        ActivityLog::record('subject_schedule_removed', $schedule, $oldValues, [], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Schedule removed.',
                'schedule_id' => $schedule->id,
                'schedule_ids' => $ids,
            ]);
        }

        return back()->with('success', 'Schedule removed.');
    }

    public function downloadSchedulePdf(Request $request)
    {
        $data = $request->validate([
            'course_code' => ['required', 'string', 'max:30'],
            'year_level' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'preview' => ['nullable', 'boolean'],
        ]);

        $academicYear = AppSetting::getValue('academic_year', '2026-2027');
        $schedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->where('school_year', $academicYear)->orWhereNull('school_year'))
            ->whereHas('subject', fn ($query) => $query
                ->where('course_code', $data['course_code'])
                ->where('year_level', $data['year_level'])
                ->where('semester', $data['semester']))
            ->join('days', 'subject_schedules.day_id', '=', 'days.id')
            ->join('time_slots', 'subject_schedules.time_slot_id', '=', 'time_slots.id')
            ->orderBy('days.sort_order')
            ->orderBy('time_slots.start_time')
            ->select('subject_schedules.*')
            ->get();

        $courseName = $this->scheduleCourseName($data['course_code']);

        if ($request->boolean('preview')) {
            $pdfContent = $this->buildSchedulePdf(
                $academicYear,
                $courseName,
                $this->scheduleYearLabel($data['year_level']),
                $this->scheduleSemesterLabel($data['semester']),
                $schedules
            );

            $previewName = str($data['course_code'] . '-' . $data['year_level'] . '-' . $data['semester'] . '-schedule-preview.pdf')
                ->replace([' ', '/'], '-')
                ->lower()
                ->toString();

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $previewName . '"',
            ]);
        }

        $fileName = str($data['course_code'] . '-' . $data['year_level'] . '-' . $data['semester'] . '-schedule.docx')
            ->replace([' ', '/'], '-')
            ->lower()
            ->toString();

        $docxPath = $this->buildScheduleDocx(
            $academicYear,
            $courseName,
            $this->scheduleYearLabel($data['year_level']),
            $this->scheduleSemesterLabel($data['semester']),
            $schedules
        );

        return response()->download(
            $docxPath,
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

    public function downloadInstructorSchedule(Request $request)
    {
        $data = $request->validate([
            'instructor' => ['required', 'string', 'max:120'],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'preview' => ['nullable', 'boolean'],
        ]);

        $academicYear = AppSetting::getValue('academic_year', '2026-2027');
        $instructor = trim($data['instructor']);
        $schedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->where('school_year', $academicYear)->orWhereNull('school_year'))
            ->whereRaw('LOWER(TRIM(instructor)) = ?', [strtolower($instructor)])
            ->whereHas('subject', fn ($query) => $query->where('semester', $data['semester']))
            ->join('days', 'subject_schedules.day_id', '=', 'days.id')
            ->join('time_slots', 'subject_schedules.time_slot_id', '=', 'time_slots.id')
            ->orderBy('days.sort_order')
            ->orderBy('time_slots.start_time')
            ->select('subject_schedules.*')
            ->get();

        if ($request->boolean('preview')) {
            $pdfContent = $this->buildInstructorSchedulePdfPreview(
                $instructor,
                $academicYear,
                $this->scheduleSemesterLabel($data['semester']),
                $schedules
            );
            $previewName = Str::of($instructor . '-' . $data['semester'] . '-' . $academicYear . '-faculty-loading-preview.pdf')
                ->replace([' ', '/', '\\'], '-')
                ->lower()
                ->toString();

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $previewName . '"',
            ]);
        }

        $fileName = Str::of($instructor . '-' . $data['semester'] . '-' . $academicYear . '-faculty-loading.docx')
            ->replace([' ', '/', '\\'], '-')
            ->lower()
            ->toString();

        $docxPath = $this->buildInstructorScheduleDocx(
            $instructor,
            $academicYear,
            $this->scheduleSemesterLabel($data['semester']),
            $schedules
        );

        return response()->download(
            $docxPath,
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

    public function downloadRoomSchedule(Request $request)
    {
        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:80'],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'preview' => ['nullable', 'boolean'],
        ]);

        $academicYear = AppSetting::getValue('academic_year', '2026-2027');
        $roomName = trim($data['room_name']);
        $schedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->where('school_year', $academicYear)->orWhereNull('school_year'))
            ->whereHas('room', fn ($query) => $query->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($roomName)]))
            ->whereHas('subject', fn ($query) => $query->where('semester', $data['semester']))
            ->join('days', 'subject_schedules.day_id', '=', 'days.id')
            ->join('time_slots', 'subject_schedules.time_slot_id', '=', 'time_slots.id')
            ->orderBy('days.sort_order')
            ->orderBy('time_slots.start_time')
            ->select('subject_schedules.*')
            ->get();

        if ($request->boolean('preview')) {
            $pdfContent = $this->buildRoomSchedulePdfPreview(
                $roomName,
                $academicYear,
                $this->scheduleSemesterLabel($data['semester']),
                $schedules
            );
            $previewName = Str::of($roomName . '-' . $data['semester'] . '-' . $academicYear . '-room-schedule-preview.pdf')
                ->replace([' ', '/', '\\'], '-')
                ->lower()
                ->toString();

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $previewName . '"',
            ]);
        }

        $fileName = Str::of($roomName . '-' . $data['semester'] . '-' . $academicYear . '-room-schedule.docx')
            ->replace([' ', '/', '\\'], '-')
            ->lower()
            ->toString();

        $docxPath = $this->buildRoomScheduleDocx(
            $roomName,
            $academicYear,
            $this->scheduleSemesterLabel($data['semester']),
            $schedules
        );

        return response()->download(
            $docxPath,
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

    public function storeDepartmentHead(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'course_code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        $head = DB::transaction(function () use ($data): DepartmentHead {
            DepartmentHead::where('course_code', $data['course_code'])->update(['is_active' => false]);
            return DepartmentHead::create($data + ['is_active' => true]);
        });
        ActivityLog::record('department_head_saved', $head, [], $head->only(['course_code', 'name', 'title', 'is_active']), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Department head updated.',
                'department_head' => [
                    'id' => $head->id,
                    'course_code' => $head->course_code,
                    'name' => $head->name,
                    'title' => $head->title ?? 'Department Head',
                ],
            ]);
        }

        return back()->with('success', 'Department head updated.');
    }

    public function updateFees(Request $request): RedirectResponse|JsonResponse
    {
        $feeTypes = ['tuition_per_unit', 'misc_fee', 'hands_on_fee', 'lab_fee', 'nstp_fee'];
        $data = $request->validate([
            'course_code' => ['required', 'string', 'max:30'],
            'fees' => ['required', 'array'],
            'fees.tuition_per_unit' => ['required', 'numeric', 'min:0'],
            'fees.misc_fee' => ['required', 'numeric', 'min:0'],
            'fees.hands_on_fee' => ['required', 'numeric', 'min:0'],
            'fees.lab_fee' => ['required', 'numeric', 'min:0'],
            'fees.nstp_fee' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($feeTypes as $feeType) {
            FeeConfiguration::updateOrCreate(
                ['course_code' => $data['course_code'], 'fee_type' => $feeType],
                [
                    'name' => str($feeType)->replace('_', ' ')->title()->toString(),
                    'basis' => in_array($feeType, ['tuition_per_unit', 'lab_fee'], true) ? 'per_unit' : 'flat',
                    'amount' => $data['fees'][$feeType],
                    'applies_to' => 'ALL',
                    'is_active' => true,
                ]
            );
        }
        ActivityLog::record('fees_updated', null, [], [
            'course_code' => $data['course_code'],
            'fees' => collect($feeTypes)->mapWithKeys(fn ($feeType) => [
                $feeType => (float) $data['fees'][$feeType],
            ])->all(),
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Fee configuration updated.',
                'course_code' => $data['course_code'],
                'fees' => collect($feeTypes)->mapWithKeys(fn ($feeType) => [
                    $feeType => (float) $data['fees'][$feeType],
                ])->all(),
            ]);
        }

        return back()->with('success', 'Fee configuration updated.');
    }
    public function storeEnrollmentTemplate(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'template_pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $file = $request->file('template_pdf');
        $path = $file->store('enrollment-templates', 'public');
        $fullPath = Storage::disk('public')->path($path);
        $size = $this->pdfFirstPageSize($fullPath);

        $template = DB::transaction(function () use ($data, $file, $path, $size): EnrollmentTemplate {
            EnrollmentTemplate::where('is_active', true)->update(['is_active' => false]);

            return EnrollmentTemplate::create([
                'name' => $data['name'] ?: 'Enrollment Form',
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'page_width' => $size['width'],
                'page_height' => $size['height'],
                'field_mappings' => [],
                'is_active' => true,
            ]);
        });
        ActivityLog::record('enrollment_template_uploaded', $template, [], [
            'name' => $template->name,
            'original_filename' => $template->original_filename,
            'file_path' => $template->file_path,
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Template uploaded.',
                'template' => $this->templatePayload($template),
            ], 201);
        }

        return back()->with('success', 'Template uploaded.');
    }

    public function storeCustomTemplateField(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', Rule::in(['enrollment', 'id'])],
            'label' => ['required', 'string', 'max:80'],
            'is_required' => ['nullable', 'boolean'],
        ]);

        $inputTypeData = $request->validate([
            'input_type' => [
                'required',
                Rule::in($data['scope'] === 'id' ? ['text', 'photo'] : ['text', 'date', 'number']),
            ],
        ]);

        $data['input_type'] = $inputTypeData['input_type'];
        $prefix = $data['scope'] === 'id' ? 'custom_id_' : 'custom_enrollment_';
        $baseKey = $prefix . Str::of($data['label'])->slug('_')->limit(48, '')->toString();
        $baseKey = $baseKey ?: $prefix . 'field';
        $key = $baseKey;
        $suffix = 2;

        while (CustomTemplateField::where('key', $key)->exists()) {
            $key = $baseKey . '_' . $suffix++;
        }

        $field = CustomTemplateField::create([
            'scope' => $data['scope'],
            'key' => $key,
            'label' => $data['label'],
            'input_type' => $data['input_type'],
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_active' => true,
            'sort_order' => (int) CustomTemplateField::where('scope', $data['scope'])->max('sort_order') + 1,
        ]);

        ActivityLog::record('custom_template_field_created', $field, [], $field->only([
            'scope', 'key', 'label', 'input_type', 'is_required',
        ]), $request);

        return response()->json([
            'message' => 'Custom field added.',
            'field' => $this->customTemplateFieldPayload($field),
        ], 201);
    }

    public function updateEnrollmentTemplateMappings(Request $request, EnrollmentTemplate $template): JsonResponse
    {
        $data = $request->validate([
            'mappings' => ['required', 'array'],
            'mappings.*.key' => ['required', 'string', 'max:80'],
            'mappings.*.label' => ['required', 'string', 'max:120'],
            'mappings.*.type' => ['nullable', 'string', 'max:30'],
            'mappings.*.x' => ['required', 'numeric', 'min:0'],
            'mappings.*.y' => ['required', 'numeric', 'min:0'],
            'mappings.*.page' => ['nullable', 'integer', 'min:1'],
            'mappings.*.font_size' => ['nullable', 'numeric', 'min:4', 'max:40'],
            'page_width' => ['nullable', 'numeric', 'min:1'],
            'page_height' => ['nullable', 'numeric', 'min:1'],
        ]);

        $template = $this->syncEnrollmentTemplatePageSize($template);
        $sourceWidth = (float) ($data['page_width'] ?? $template->page_width);
        $sourceHeight = (float) ($data['page_height'] ?? $template->page_height);
        $targetWidth = max(1, (float) $template->page_width);
        $targetHeight = max(1, (float) $template->page_height);
        $scaleX = $sourceWidth > 0 ? $targetWidth / $sourceWidth : 1;
        $scaleY = $sourceHeight > 0 ? $targetHeight / $sourceHeight : 1;

        $normalizedMappings = collect($data['mappings'])->map(function ($mapping) use ($scaleX, $scaleY) {
            return [
                'key' => $mapping['key'],
                'label' => $mapping['label'],
                'type' => $mapping['type'] ?? 'text',
                'x' => round((float) $mapping['x'] * $scaleX, 2),
                'y' => round((float) $mapping['y'] * $scaleY, 2),
                'page' => (int) ($mapping['page'] ?? 1),
                'font_size' => round((float) ($mapping['font_size'] ?? 10), 1),
            ];
        })->values();

        $outsideMappings = $normalizedMappings
            ->filter(fn ($mapping) => $mapping['x'] > $targetWidth + 0.5 || $mapping['y'] > $targetHeight + 0.5)
            ->pluck('label')
            ->values();

        if ($outsideMappings->isNotEmpty()) {
            throw ValidationException::withMessages([
                'mappings' => sprintf(
                    'These mapped fields are outside the current PDF page size (%s x %s): %s. Move them back inside the PDF and save again.',
                    number_format($targetWidth, 2),
                    number_format($targetHeight, 2),
                    $outsideMappings->take(5)->implode(', ') . ($outsideMappings->count() > 5 ? ', and more' : '')
                ),
            ]);
        }

        $oldValues = ['field_count' => count($template->field_mappings ?? [])];
        $template->update([
            'field_mappings' => $normalizedMappings->all(),
        ]);
        ActivityLog::record('enrollment_template_mapping_saved', $template, $oldValues, [
            'field_count' => count($template->field_mappings ?? []),
            'name' => $template->name,
        ], $request);

        return response()->json([
            'message' => 'Template mapping saved.',
            'template' => $this->templatePayload($template->fresh()),
        ]);
    }

    public function showEnrollmentTemplatePdf(EnrollmentTemplate $template)
    {
        $path = $this->templateAbsolutePath($template->file_path);

        abort_unless($path, 404);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function storeIdTemplate(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'side' => ['required', Rule::in(['front', 'back'])],
            'school_year' => ['nullable', 'string', 'max:20'],
            'background_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $file = $request->file('background_image');
        $path = $file->store('id-templates', 'public');
        [$width, $height] = getimagesize(Storage::disk('public')->path($path)) ?: [540, 340];

        $template = DB::transaction(function () use ($data, $path, $width, $height): IdTemplate {
            IdTemplate::where('side', $data['side'])->where('is_active', true)->update(['is_active' => false]);

            return IdTemplate::create([
                'name' => $data['name'] ?: ucfirst($data['side']) . ' ID Template',
                'side' => $data['side'],
                'school_year' => $data['school_year'] ?? null,
                'background_image_path' => $path,
                'layout_config' => [
                    'width' => (float) $width,
                    'height' => (float) $height,
                    'fields' => [],
                ],
                'is_active' => true,
            ]);
        });
        ActivityLog::record('id_template_uploaded', $template, [], [
            'name' => $template->name,
            'side' => $template->side,
            'background_image_path' => $template->background_image_path,
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'ID template uploaded.',
                'template' => $this->idTemplatePayload($template),
            ], 201);
        }

        return back()->with('success', 'ID template uploaded.');
    }

    public function updateIdTemplateLayout(Request $request, IdTemplate $template): JsonResponse
    {
        $data = $request->validate([
            'width' => ['required', 'numeric', 'min:1'],
            'height' => ['required', 'numeric', 'min:1'],
            'fields' => ['present', 'array'],
            'fields.*.key' => ['required', 'string', 'max:80'],
            'fields.*.label' => ['required', 'string', 'max:120'],
            'fields.*.type' => ['required', Rule::in(['text', 'image'])],
            'fields.*.x' => ['required', 'numeric', 'min:0'],
            'fields.*.y' => ['required', 'numeric', 'min:0'],
            'fields.*.width' => ['required', 'numeric', 'min:1'],
            'fields.*.height' => ['required', 'numeric', 'min:1'],
            'fields.*.font_size' => ['nullable', 'numeric', 'min:4', 'max:80'],
            'fields.*.font_family' => ['nullable', 'string', 'max:80'],
            'fields.*.font_weight' => ['nullable', 'string', 'max:20'],
            'fields.*.font_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'fields.*.text_align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'fields.*.shape' => ['nullable', Rule::in(['rectangle', 'rounded', 'circle', 'oval', 'hexagon'])],
            'fields.*.object_fit' => ['nullable', Rule::in(['cover', 'contain'])],
            'fields.*.locked_shape' => ['nullable', 'boolean'],
        ]);

        $oldValues = [
            'field_count' => count($template->layout_config['fields'] ?? []),
            'width' => $template->layout_config['width'] ?? null,
            'height' => $template->layout_config['height'] ?? null,
        ];
        $template->update([
            'layout_config' => [
                'width' => round((float) $data['width'], 2),
                'height' => round((float) $data['height'], 2),
                'fields' => collect($data['fields'])->map(fn ($field) => [
                    'key' => $field['key'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'x' => round((float) $field['x'], 2),
                    'y' => round((float) $field['y'], 2),
                    'width' => round((float) $field['width'], 2),
                    'height' => round((float) $field['height'], 2),
                    'font_size' => round((float) ($field['font_size'] ?? 12), 1),
                    'font_family' => $field['font_family'] ?? 'Arial',
                    'font_weight' => $field['font_weight'] ?? '700',
                    'font_color' => $field['font_color'] ?? '#111827',
                    'text_align' => $field['text_align'] ?? 'left',
                    'shape' => $field['shape'] ?? 'rectangle',
                    'object_fit' => $field['object_fit'] ?? 'cover',
                    'locked_shape' => (bool) ($field['locked_shape'] ?? false),
                ])->values()->all(),
            ],
        ]);
        ActivityLog::record('id_template_layout_saved', $template, $oldValues, [
            'field_count' => count($template->layout_config['fields'] ?? []),
            'width' => $template->layout_config['width'] ?? null,
            'height' => $template->layout_config['height'] ?? null,
            'side' => $template->side,
        ], $request);

        return response()->json([
            'message' => 'ID template layout saved.',
            'template' => $this->idTemplatePayload($template->fresh()),
        ]);
    }

    public function storeIdTemplateFont(Request $request): JsonResponse
    {
        $data = $request->validate([
            'font_file' => ['required', 'file', 'max:5120'],
        ], [
            'font_file.required' => 'Choose a font file to upload.',
        ]);

        $file = $data['font_file'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['ttf', 'otf', 'woff', 'woff2'], true)) {
            throw ValidationException::withMessages([
                'font_file' => 'Upload a TTF, OTF, WOFF, or WOFF2 font file.',
            ]);
        }

        $baseName = Str::of(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            ->slug('-')
            ->limit(80, '')
            ->toString() ?: 'id-font';
        $path = $file->storeAs('id-template-fonts', $baseName . '.' . $extension, 'public');
        ActivityLog::record('id_template_font_uploaded', null, [], [
            'family' => Str::of($baseName)->headline()->toString(),
            'path' => $path,
            'extension' => $extension,
        ], $request);

        return response()->json([
            'message' => 'Font uploaded.',
            'font' => [
                'family' => Str::of($baseName)->headline()->toString(),
                'url' => '/storage/' . ltrim($path, '/'),
                'extension' => $extension,
            ],
        ], 201);
    }

    public function showIdTemplateBackground(IdTemplate $template)
    {
        $path = $this->templateAbsolutePath($template->background_image_path);

        abort_unless($path, 404);

        return response()->file($path);
    }

    private function templateAbsolutePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'templates/')) {
            $publicPath = public_path($path);

            return file_exists($publicPath) ? $publicPath : null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->path($path)
            : null;
    }

    private function applyFixedSubjectUnits(array $data): array
    {
        $data['lecture_units'] = (int) $data['lecture_units'];
        $data['laboratory_units'] = in_array($data['type'], ['LAB', 'BOTH'], true) ? 1 : 0;
        $data['total_units'] = $data['lecture_units'] + $data['laboratory_units'];

        return $data;
    }
    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('subjects', 'code')
                    ->where(fn ($query) => $query->where('course_code', $request->input('course_code')))
                    ->ignore($subject?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'course_code' => ['required', 'string', 'max:30'],
            'year_level' => ['required', Rule::in(['1', '2', '3', '4'])],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'type' => ['required', Rule::in(['LEC', 'LAB', 'BOTH'])],
            'lecture_units' => ['required', 'integer', 'min:0', 'max:9'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function validateInstructor(Request $request): array
    {
        return $request->validate([
            'title' => ['required', Rule::in(['Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Prof.', 'Engr.', 'Mx.'])],
            'first_name' => ['required', 'string', 'max:80'],
            'middle_initial' => ['nullable', 'string', 'max:10'],
            'last_name' => ['required', 'string', 'max:80'],
        ]);
    }

    private function ensureUniqueInstructorName(string $name, ?Instructor $instructor = null, bool $activeOnly = false): void
    {
        $exists = Instructor::where('name', $name)
            ->when($instructor, fn ($query) => $query->whereKeyNot($instructor->id))
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'first_name' => 'An instructor with this full name already exists.',
            ]);
        }
    }

    private function subjectPayload(Subject $subject): array
    {
        return [
            'id' => $subject->id,
            'code' => $subject->code,
            'name' => $subject->name,
            'course_code' => $subject->course_code,
            'year_level' => $subject->year_level,
            'semester' => $subject->semester,
            'type' => $subject->type,
            'lecture_units' => (int) $subject->lecture_units,
            'laboratory_units' => (int) $subject->laboratory_units,
            'total_units' => (int) $subject->total_units,
        ];
    }

    private function instructorPayload(Instructor $instructor): array
    {
        return [
            'id' => $instructor->id,
            'title' => $instructor->title,
            'first_name' => $instructor->first_name,
            'middle_initial' => $instructor->middle_initial,
            'last_name' => $instructor->last_name,
            'name' => $instructor->name,
        ];
    }

    private function customTemplateFieldPayload(CustomTemplateField $field): array
    {
        $isImage = $field->scope === 'id' && $field->input_type === 'photo';

        return [
            'id' => $field->id,
            'key' => $field->key,
            'label' => $field->label,
            'type' => $isImage ? 'image' : 'text',
            'input_type' => $field->input_type,
            'is_required' => (bool) $field->is_required,
            'is_custom' => true,
            'width' => $isImage ? 120 : 180,
            'height' => $isImage ? 140 : 28,
            'font_size' => 14,
            'font_family' => 'Arial',
            'font_weight' => '600',
            'shape' => $isImage ? 'rectangle' : null,
            'object_fit' => $isImage ? 'cover' : null,
        ];
    }

    private function schedulePayload(SubjectSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'subject' => [
                'id' => $schedule->subject->id,
                'code' => $schedule->subject->code,
                'name' => $schedule->subject->name,
                'course_code' => $schedule->subject->course_code,
                'year_level' => $schedule->subject->year_level,
                'semester' => $schedule->subject->semester,
                'type' => $schedule->subject->type,
            ],
            'day_id' => $schedule->day_id,
            'day' => $schedule->day->name,
            'time' => $this->scheduleTimeLabel($schedule),
            'start_time' => substr((string) $schedule->timeSlot->start_time, 0, 5),
            'end_time' => substr((string) $schedule->timeSlot->end_time, 0, 5),
            'room_id' => $schedule->room_id,
            'room' => $schedule->room->name,
            'instructor' => $schedule->instructor ?: 'Unassigned',
            'schedule_type' => $schedule->schedule_type ?: 'LEC',
            'schedule_for' => $schedule->schedule_for ?: 'Whole Class',
            'school_year' => $schedule->school_year,
            'archived_school_year' => $schedule->archived_school_year,
            'archived_at' => $schedule->archived_at?->toDateTimeString(),
            'subject_display_name' => $this->scheduleSubjectName($schedule),
            'update_url' => route('academic.schedules.update', $schedule),
            'delete_url' => route('academic.schedules.destroy', $schedule),
        ];
    }

    private function scheduleSubjectName(SubjectSchedule $schedule): string
    {
        $type = $schedule->schedule_type ?: ($schedule->subject->type === 'LAB' ? 'LAB' : 'LEC');

        return $schedule->subject->name . ' - ' . $type;
    }

    private function scheduleSubjectNameForRow(array $row): string
    {
        $schedule = $row['schedule'];
        $type = $row['type_label'] ?? ($schedule->schedule_type ?: ($schedule->subject->type === 'LAB' ? 'LAB' : 'LEC'));

        return $schedule->subject->name . ' - ' . $type;
    }

    private function prepareAdditionalScheduleUnits($existingSchedules, Subject $subject, string $scheduleType): int
    {
        $baseUnits = $this->subjectScheduleUnitValue($subject, $scheduleType);
        $usedUnits = $existingSchedules
            ->groupBy(fn (SubjectSchedule $schedule) => implode('|', [
                $schedule->time_slot_id,
                $schedule->room_id,
                strtolower(trim((string) $schedule->instructor)),
            ]))
            ->sortBy(fn ($group) => optional($group->first()->created_at)->timestamp ?? 0)
            ->sum(function ($group): int {
                $schedule = $group->first();
                $unitValue = $this->splitMeetingUnitValue($schedule->timeSlot);

                SubjectSchedule::whereIn('id', $group->pluck('id')->all())
                    ->update(['unit_value' => $unitValue]);

                return $unitValue;
            });

        return max($baseUnits - $usedUnits, 0);
    }

    private function initialScheduleUnitValue(Subject $subject, string $scheduleType, TimeSlot $timeSlot): int
    {
        return $this->exactHourUnitValue($timeSlot) ?? $this->subjectScheduleUnitValue($subject, $scheduleType);
    }

    private function splitMeetingUnitValue(?TimeSlot $timeSlot): int
    {
        return $this->exactHourUnitValue($timeSlot) ?? 1;
    }

    private function exactHourUnitValue(?TimeSlot $timeSlot): ?int
    {
        if (! $timeSlot?->start_time || ! $timeSlot?->end_time) {
            return null;
        }

        $minutes = (strtotime((string) $timeSlot->end_time) - strtotime((string) $timeSlot->start_time)) / 60;

        return match ((int) $minutes) {
            60 => 1,
            120 => 2,
            default => null,
        };
    }

    private function subjectScheduleUnitValue(Subject $subject, string $scheduleType): int
    {
        return match ($scheduleType) {
            'LAB' => (int) $subject->laboratory_units,
            'LEC' => (int) $subject->lecture_units,
            default => (int) $subject->total_units,
        };
    }

    private function scheduleUnits(SubjectSchedule $schedule): int
    {
        return $schedule->unit_value ?? $this->subjectScheduleUnitValue($schedule->subject, $schedule->schedule_type);
    }

    private function scheduleGroupUnits($group): int
    {
        return $group
            ->groupBy(fn (SubjectSchedule $schedule) => $schedule->schedule_type ?: 'LEC')
            ->sum(fn ($typedSchedules) => $typedSchedules->max(fn (SubjectSchedule $schedule) => $this->scheduleUnits($schedule)));
    }

    private function scheduleCourseGroup(SubjectSchedule $schedule): string
    {
        return trim($schedule->subject->course_code . ' ' . $schedule->subject->year_level);
    }

    private function scheduleClassLabel(SubjectSchedule $schedule): string
    {
        return trim(implode('/', array_filter([
            $schedule->subject->course_code,
            $schedule->subject->year_level,
            $schedule->subject->semester,
            $schedule->schedule_for ?: 'Whole Class',
        ])));
    }

    private function isScheduleMergeCandidate(
        SubjectSchedule $schedule,
        Subject $subject,
        array $data,
        Room $room,
        TimeSlot $timeSlot
    ): bool {
        return (int) $schedule->subject_id !== (int) $subject->id
            && (int) $schedule->room_id === (int) $room->id
            && strtolower(trim((string) $schedule->instructor)) === strtolower(trim((string) $data['instructor']))
            && strtolower(trim((string) $schedule->subject?->code)) === strtolower(trim((string) $subject->code))
            && ($schedule->schedule_type ?: 'LEC') === ($data['schedule_type'] ?: 'LEC')
            && (string) $schedule->timeSlot?->start_time === (string) $timeSlot->start_time
            && (string) $schedule->timeSlot?->end_time === (string) $timeSlot->end_time;
    }

    private function isCombinedLectureLabCandidate(
        SubjectSchedule $schedule,
        Subject $subject,
        array $data,
        Room $room,
        TimeSlot $timeSlot,
        $dayIds,
        string $academicYear
    ): bool {
        if (! ((int) $schedule->room_id === (int) $room->id
            && strtolower(trim((string) $schedule->instructor)) === strtolower(trim((string) $data['instructor']))
            && strtolower(trim((string) $schedule->subject?->code)) === strtolower(trim((string) $subject->code))
            && ($schedule->schedule_type ?: 'LEC') !== ($data['schedule_type'] ?: 'LEC')
            && in_array($schedule->schedule_type ?: 'LEC', ['LEC', 'LAB'], true)
            && in_array($data['schedule_type'] ?: 'LEC', ['LEC', 'LAB'], true)
            && (string) $schedule->timeSlot?->start_time === (string) $timeSlot->start_time
            && (string) $schedule->timeSlot?->end_time === (string) $timeSlot->end_time)) {
            return false;
        }

        $existingDayIds = SubjectSchedule::where('subject_id', $schedule->subject_id)
            ->where('schedule_type', $schedule->schedule_type ?: 'LEC')
            ->where('schedule_for', $schedule->schedule_for ?: 'Whole Class')
            ->where('time_slot_id', $schedule->time_slot_id)
            ->where('room_id', $schedule->room_id)
            ->where('instructor', $schedule->instructor)
            ->whereNull('archived_at')
            ->where(fn ($query) => $query->where('school_year', $academicYear)->orWhereNull('school_year'))
            ->pluck('day_id')
            ->map(fn ($dayId) => (int) $dayId)
            ->unique()
            ->sort()
            ->values();

        return $existingDayIds->all() === $dayIds->sort()->values()->all();
    }

    private function scheduleTypeLabelForGroup($group): string
    {
        $types = $group
            ->pluck('schedule_type')
            ->map(fn (?string $type) => $type ?: 'LEC')
            ->uniqueStrict()
            ->sort()
            ->values();

        return $types->contains('LEC') && $types->contains('LAB')
            ? 'LEC/LAB'
            : (string) ($types->first() ?: 'LEC');
    }

    private function subjectScheduleLabel(?Subject $subject, ?string $type): string
    {
        if (! $subject) {
            return 'Selected subject';
        }

        $resolvedType = $type ?: ($subject->type === 'LAB' ? 'LAB' : 'LEC');

        return $subject->name . ' - ' . $resolvedType;
    }

    private function normalizeScheduleFor(?string $value): string
    {
        $value = trim((string) $value);

        return $value === '' ? 'Whole Class' : preg_replace('/\s+/', ' ', $value);
    }

    private function scheduleGroupsConflict(?string $existing, ?string $incoming): bool
    {
        $existing = strtolower($this->normalizeScheduleFor($existing));
        $incoming = strtolower($this->normalizeScheduleFor($incoming));

        return $existing === 'whole class'
            || $incoming === 'whole class'
            || $existing === $incoming;
    }

    private function scheduleTimeLabel(SubjectSchedule $schedule): string
    {
        if ($schedule->timeSlot?->label) {
            return $schedule->timeSlot->label;
        }

        $start = $schedule->timeSlot?->start_time;
        $end = $schedule->timeSlot?->end_time;

        if (! $start || ! $end) {
            return '';
        }

        return date('g:i A', strtotime((string) $start)) . ' - ' . date('g:i A', strtotime((string) $end));
    }

    private function scheduleDayLabel(SubjectSchedule $schedule): string
    {
        return match (strtolower((string) $schedule->day?->name)) {
            'monday' => 'M',
            'tuesday' => 'T',
            'wednesday' => 'W',
            'thursday' => 'TH',
            'friday' => 'F',
            'saturday' => 'SAT',
            'sunday' => 'SUN',
            default => strtoupper((string) $schedule->day?->name),
        };
    }

    private function scheduleDayLabels($schedules): string
    {
        return $schedules
            ->sortBy(fn (SubjectSchedule $schedule) => $schedule->day?->sort_order ?? 999)
            ->map(fn (SubjectSchedule $schedule) => $this->scheduleDayLabel($schedule))
            ->uniqueStrict()
            ->implode('');
    }

    private function scheduleYearLabel(string $yearLevel): string
    {
        return match ((string) $yearLevel) {
            '1' => 'FIRST YEAR',
            '2' => 'SECOND YEAR',
            '3' => 'THIRD YEAR',
            '4' => 'FOURTH YEAR',
            default => strtoupper($yearLevel),
        };
    }

    private function scheduleSemesterLabel(string $semester): string
    {
        return match ($semester) {
            '1st' => 'FIRST SEMESTER',
            '2nd' => 'SECOND SEMESTER',
            'Summer' => 'SUMMER',
            default => strtoupper($semester),
        };
    }

    private function scheduleCourseName(string $courseCode): string
    {
        return [
            'BSIT' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'BSCS' => 'BACHELOR OF SCIENCE IN COMPUTER SCIENCE',
            'ACT' => 'ASSOCIATE IN COMPUTER TECHNOLOGY',
            'BSA' => 'BACHELOR OF SCIENCE IN ACCOUNTANCY',
            'BSBA' => 'BACHELOR OF SCIENCE IN BUSINESS ADMINISTRATION',
            'BSOM' => 'BACHELOR OF SCIENCE IN OFFICE MANAGEMENT',
        ][strtoupper($courseCode)] ?? strtoupper($courseCode);
    }

    private function buildScheduleDocx(
        string $academicYear,
        string $courseName,
        string $yearLabel,
        string $semesterLabel,
        $schedules
    ): string {
        $scheduleRows = $this->scheduleDocumentRows($schedules);
        $logoPath = public_path('images/logo1.png');
        $hasLogo = file_exists($logoPath);
        $docxPath = storage_path('app/schedule-' . Str::uuid() . '.docx');

        $zip = new \ZipArchive();
        if ($zip->open($docxPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create schedule document.');
        }

        $zip->addFromString('[Content_Types].xml', $this->docxContentTypes($hasLogo));
        $zip->addFromString('_rels/.rels', $this->docxRootRelationships());
        $zip->addFromString('word/_rels/document.xml.rels', $this->docxDocumentRelationships($hasLogo));
        $zip->addFromString('word/styles.xml', $this->docxStyles());
        $zip->addFromString('word/document.xml', $this->docxScheduleDocument(
            $academicYear,
            $courseName,
            $yearLabel,
            $semesterLabel,
            $scheduleRows,
            $hasLogo
        ));

        if ($hasLogo) {
            $zip->addFile($logoPath, 'word/media/logo1.png');
        }

        $zip->close();

        return $docxPath;
    }

    private function buildInstructorScheduleDocx(
        string $instructor,
        string $academicYear,
        string $semesterLabel,
        $schedules
    ): string {
        $scheduleRows = $this->mergedScheduleDocumentRows($schedules);
        $logoPath = public_path('images/logo1.png');
        $hasLogo = file_exists($logoPath);
        $docxPath = storage_path('app/instructor-schedule-' . Str::uuid() . '.docx');

        $zip = new \ZipArchive();
        if ($zip->open($docxPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create instructor schedule document.');
        }

        $zip->addFromString('[Content_Types].xml', $this->docxContentTypes($hasLogo));
        $zip->addFromString('_rels/.rels', $this->docxRootRelationships());
        $zip->addFromString('word/_rels/document.xml.rels', $this->docxDocumentRelationships($hasLogo));
        $zip->addFromString('word/styles.xml', $this->docxStyles());
        $zip->addFromString('word/document.xml', $this->docxInstructorScheduleDocument(
            $instructor,
            $academicYear,
            $semesterLabel,
            $scheduleRows,
            $hasLogo
        ));

        if ($hasLogo) {
            $zip->addFile($logoPath, 'word/media/logo1.png');
        }

        $zip->close();

        return $docxPath;
    }

    private function buildRoomScheduleDocx(
        string $roomName,
        string $academicYear,
        string $semesterLabel,
        $schedules
    ): string {
        $scheduleRows = $this->mergedScheduleDocumentRows($schedules);
        $logoPath = public_path('images/logo1.png');
        $hasLogo = file_exists($logoPath);
        $docxPath = storage_path('app/room-schedule-' . Str::uuid() . '.docx');

        $zip = new \ZipArchive();
        if ($zip->open($docxPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create room schedule document.');
        }

        $zip->addFromString('[Content_Types].xml', $this->docxContentTypes($hasLogo));
        $zip->addFromString('_rels/.rels', $this->docxRootRelationships());
        $zip->addFromString('word/_rels/document.xml.rels', $this->docxDocumentRelationships($hasLogo));
        $zip->addFromString('word/styles.xml', $this->docxStyles());
        $zip->addFromString('word/document.xml', $this->docxRoomScheduleDocument(
            $roomName,
            $academicYear,
            $semesterLabel,
            $scheduleRows,
            $hasLogo
        ));

        if ($hasLogo) {
            $zip->addFile($logoPath, 'word/media/logo1.png');
        }

        $zip->close();

        return $docxPath;
    }

    private function scheduleDocumentRows($schedules)
    {
        return $schedules
            ->groupBy(fn (SubjectSchedule $schedule) => implode('|', [
                $schedule->subject_id,
                $schedule->schedule_for ?: 'Whole Class',
                $schedule->time_slot_id,
                $schedule->room_id,
                strtolower(trim((string) $schedule->instructor)),
            ]))
            ->map(fn ($group) => [
                'schedule' => $group->first(),
                'day_label' => $this->scheduleDayLabels($group),
                'type_label' => $this->scheduleTypeLabelForGroup($group),
                'units' => $this->scheduleGroupUnits($group),
            ])
            ->values();
    }

    private function mergedScheduleDocumentRows($schedules)
    {
        return $schedules
            ->groupBy(fn (SubjectSchedule $schedule) => implode('|', [
                strtolower(trim((string) $schedule->subject?->code)),
                $schedule->schedule_for ?: 'Whole Class',
                $schedule->time_slot_id,
                $schedule->room_id,
                strtolower(trim((string) $schedule->instructor)),
            ]))
            ->map(fn ($group) => [
                'schedule' => $group->first(),
                'day_label' => $this->scheduleDayLabels($group),
                'type_label' => $this->scheduleTypeLabelForGroup($group),
                'units' => $this->scheduleGroupUnits($group),
                'course_label' => $group
                    ->map(fn (SubjectSchedule $schedule) => $this->scheduleCourseGroup($schedule))
                    ->uniqueStrict()
                    ->values()
                    ->implode('/'),
            ])
            ->values();
    }

    private function docxContentTypes(bool $hasLogo): string
    {
        $png = $hasLogo ? '<Default Extension="png" ContentType="image/png"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . $png
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '</Types>';
    }

    private function docxRootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    private function docxDocumentRelationships(bool $hasLogo): string
    {
        $logo = $hasLogo
            ? '<Relationship Id="rIdLogo" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/logo1.png"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . $logo
            . '</Relationships>';
    }

    private function docxStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="22"/></w:rPr></w:style>'
            . '</w:styles>';
    }

    private function docxScheduleDocument(
        string $academicYear,
        string $courseName,
        string $yearLabel,
        string $semesterLabel,
        $scheduleRows,
        bool $hasLogo
    ): string {
        $tableWidth = 15440;
        $columns = [
            'code' => 1250,
            'subject' => 7100,
            'day' => 850,
            'time' => 2600,
            'room' => 1000,
            'instructor' => 2640,
        ];

        $rows = $scheduleRows->isEmpty()
            ? $this->docxTableRow([
                ['No schedules found for this class.', $tableWidth, ['align' => 'center', 'colspan' => 6]],
            ])
            : $scheduleRows->map(function (array $row): string {
                $schedule = $row['schedule'];
                $subjectName = $this->scheduleSubjectNameForRow($row);
                if (($schedule->schedule_for ?: 'Whole Class') !== 'Whole Class') {
                    $subjectName .= ' (' . $schedule->schedule_for . ')';
                }

                return $this->docxTableRow([
                    [$schedule->subject->code, 1250, ['align' => 'center']],
                    [$subjectName, 7100],
                    [$row['day_label'], 850, ['align' => 'center']],
                    [$this->scheduleTimeLabel($schedule), 2600, ['align' => 'center']],
                    [$schedule->room->name, 1000, ['align' => 'center']],
                    [$schedule->instructor ?: '', 2640],
                ]);
            })->implode('');

        $heading = $this->docxParagraph([
            ['text' => $yearLabel, 'bold' => true, 'color' => 'FF0000', 'underline' => true, 'size' => 24, 'font' => 'Times New Roman'],
            ['text' => '  |  ', 'bold' => true, 'size' => 24, 'font' => 'Times New Roman'],
            ['text' => $semesterLabel, 'bold' => true, 'color' => '082256', 'size' => 24, 'font' => 'Times New Roman'],
            ['text' => '  |  AY ' . $academicYear, 'bold' => true, 'size' => 24, 'font' => 'Times New Roman'],
        ], 'center');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<w:body>'
            . $this->docxHeaderTable($hasLogo)
            . $heading
            . $this->docxParagraph([['text' => $courseName, 'bold' => true, 'size' => 22, 'font' => 'Times New Roman']], 'center')
            . $this->docxParagraph([['text' => '', 'size' => 5]], 'center')
            . '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="' . $tableWidth . '" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:top w:val="single" w:sz="10"/><w:left w:val="single" w:sz="10"/><w:bottom w:val="single" w:sz="10"/><w:right w:val="single" w:sz="10"/><w:insideH w:val="single" w:sz="10"/><w:insideV w:val="single" w:sz="10"/></w:tblBorders></w:tblPr>'
            . '<w:tblGrid><w:gridCol w:w="' . $columns['code'] . '"/><w:gridCol w:w="' . $columns['subject'] . '"/><w:gridCol w:w="' . $columns['day'] . '"/><w:gridCol w:w="' . $columns['time'] . '"/><w:gridCol w:w="' . $columns['room'] . '"/><w:gridCol w:w="' . $columns['instructor'] . '"/></w:tblGrid>'
            . $this->docxTableRow([
                ['Code', $columns['code'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Subjects', $columns['subject'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Day', $columns['day'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Time', $columns['time'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Room', $columns['room'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Instructor', $columns['instructor'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
            ])
            . $rows
            . '</w:tbl>'
            . '<w:p><w:pPr><w:sectPr><w:pgSz w:orient="landscape" w:w="16838" w:h="11906"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="0" w:footer="0" w:gutter="0"/><w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr></w:pPr></w:p>'
            . '</w:body></w:document>';
    }

    private function docxInstructorScheduleDocument(
        string $instructor,
        string $academicYear,
        string $semesterLabel,
        $scheduleRows,
        bool $hasLogo
    ): string {
        $tableWidth = 15440;
        $columns = [
            'code' => 1250,
            'description' => 6500,
            'units' => 850,
            'day' => 900,
            'time' => 2500,
            'room' => 1100,
            'course' => 2340,
        ];

        $rows = $scheduleRows->isEmpty()
            ? $this->docxTableRow([
                ['No schedules found for this instructor.', $tableWidth, ['align' => 'center', 'colspan' => 7]],
            ])
            : $scheduleRows->map(function (array $row): string {
                $schedule = $row['schedule'];
                $description = $this->scheduleSubjectNameForRow($row);
                if (($schedule->schedule_for ?: 'Whole Class') !== 'Whole Class') {
                    $description .= ' (' . $schedule->schedule_for . ')';
                }

                return $this->docxTableRow([
                    [$schedule->subject->code, 1250, ['align' => 'center']],
                    [$description, 6500],
                    [$row['units'] ?? $this->scheduleUnits($schedule), 850, ['align' => 'center']],
                    [$row['day_label'], 900, ['align' => 'center']],
                    [$this->scheduleTimeLabel($schedule), 2500, ['align' => 'center']],
                    [$schedule->room->name, 1100, ['align' => 'center']],
                    [$row['course_label'] ?? $this->scheduleCourseGroup($schedule), 2340, ['align' => 'center']],
                ]);
            })->implode('');

        $totalUnits = $scheduleRows->sum(fn (array $row) => $row['units'] ?? $this->scheduleUnits($row['schedule']));

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<w:body>'
            . $this->docxHeaderTable($hasLogo)
            . $this->docxParagraph([['text' => 'COLLEGE FACULTY LOADING', 'bold' => true, 'size' => 14, 'font' => 'Arial']], 'center')
            . '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="' . $tableWidth . '" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:top w:val="single" w:sz="8"/><w:left w:val="single" w:sz="8"/><w:bottom w:val="single" w:sz="8"/><w:right w:val="single" w:sz="8"/><w:insideH w:val="single" w:sz="8"/><w:insideV w:val="single" w:sz="8"/></w:tblBorders></w:tblPr>'
            . '<w:tblGrid><w:gridCol w:w="2600"/><w:gridCol w:w="5120"/><w:gridCol w:w="2600"/><w:gridCol w:w="5120"/></w:tblGrid>'
            . $this->docxTableRow([
                ['Name of Instructor', 2600, ['bold' => true]],
                [$instructor, 5120],
                ['Academic Year', 2600, ['bold' => true]],
                [$academicYear, 5120],
            ])
            . $this->docxTableRow([
                ['Semester', 2600, ['bold' => true]],
                [$semesterLabel, 12840, ['colspan' => 3]],
            ])
            . '</w:tbl>'
            . $this->docxParagraph([['text' => '', 'size' => 4]], 'center')
            . '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="' . $tableWidth . '" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:top w:val="single" w:sz="10"/><w:left w:val="single" w:sz="10"/><w:bottom w:val="single" w:sz="10"/><w:right w:val="single" w:sz="10"/><w:insideH w:val="single" w:sz="10"/><w:insideV w:val="single" w:sz="10"/></w:tblBorders></w:tblPr>'
            . '<w:tblGrid><w:gridCol w:w="' . $columns['code'] . '"/><w:gridCol w:w="' . $columns['description'] . '"/><w:gridCol w:w="' . $columns['units'] . '"/><w:gridCol w:w="' . $columns['day'] . '"/><w:gridCol w:w="' . $columns['time'] . '"/><w:gridCol w:w="' . $columns['room'] . '"/><w:gridCol w:w="' . $columns['course'] . '"/></w:tblGrid>'
            . $this->docxTableRow([
                ['Code', $columns['code'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Description', $columns['description'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Units', $columns['units'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Day', $columns['day'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Time', $columns['time'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Room', $columns['room'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Course', $columns['course'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
            ])
            . $rows
            . $this->docxTableRow([
                ['Total Units', 7750, ['align' => 'right', 'bold' => true, 'colspan' => 2]],
                [(string) $totalUnits, 850, ['align' => 'center', 'bold' => true]],
                ['', 900],
                ['', 2500],
                ['', 1100],
                ['', 2340],
            ])
            . '</w:tbl>'
            . '<w:p><w:pPr><w:sectPr><w:pgSz w:orient="landscape" w:w="16838" w:h="11906"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="0" w:footer="0" w:gutter="0"/><w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr></w:pPr></w:p>'
            . '</w:body></w:document>';
    }

    private function docxRoomScheduleDocument(
        string $roomName,
        string $academicYear,
        string $semesterLabel,
        $scheduleRows,
        bool $hasLogo
    ): string {
        $tableWidth = 15440;
        $columns = [
            'day' => 850,
            'time' => 2500,
            'code' => 1250,
            'description' => 6800,
            'course' => 1440,
            'instructor' => 2600,
        ];

        $rows = $scheduleRows->isEmpty()
            ? $this->docxTableRow([
                ['No schedules found for this room.', $tableWidth, ['align' => 'center', 'colspan' => 7]],
            ])
            : $scheduleRows->map(function (array $row): string {
                $schedule = $row['schedule'];
                $description = $this->scheduleSubjectNameForRow($row);
                if (($schedule->schedule_for ?: 'Whole Class') !== 'Whole Class') {
                    $description .= ' (' . $schedule->schedule_for . ')';
                }

                return $this->docxTableRow([
                    [$row['day_label'], 850, ['align' => 'center']],
                    [$this->scheduleTimeLabel($schedule), 2500, ['align' => 'center']],
                    [$schedule->subject->code, 1250, ['align' => 'center']],
                    [$description, 6800],
                    [$row['course_label'] ?? $this->scheduleCourseGroup($schedule), 1440, ['align' => 'center']],
                    [$schedule->instructor ?: '', 2600],
                ]);
            })->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<w:body>'
            . $this->docxHeaderTable($hasLogo)
            . $this->docxParagraph([['text' => 'ROOM SCHEDULE', 'bold' => true, 'size' => 14, 'font' => 'Arial']], 'center')
            . '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="' . $tableWidth . '" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:top w:val="single" w:sz="8"/><w:left w:val="single" w:sz="8"/><w:bottom w:val="single" w:sz="8"/><w:right w:val="single" w:sz="8"/><w:insideH w:val="single" w:sz="8"/><w:insideV w:val="single" w:sz="8"/></w:tblBorders></w:tblPr>'
            . '<w:tblGrid><w:gridCol w:w="2600"/><w:gridCol w:w="5120"/><w:gridCol w:w="2600"/><w:gridCol w:w="5120"/></w:tblGrid>'
            . $this->docxTableRow([
                ['Room', 2600, ['bold' => true]],
                [$roomName, 5120],
                ['Academic Year', 2600, ['bold' => true]],
                [$academicYear, 5120],
            ])
            . $this->docxTableRow([
                ['Semester', 2600, ['bold' => true]],
                [$semesterLabel, 12840, ['colspan' => 3]],
            ])
            . '</w:tbl>'
            . $this->docxParagraph([['text' => '', 'size' => 4]], 'center')
            . '<w:tbl>'
            . '<w:tblPr><w:tblW w:w="' . $tableWidth . '" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:top w:val="single" w:sz="10"/><w:left w:val="single" w:sz="10"/><w:bottom w:val="single" w:sz="10"/><w:right w:val="single" w:sz="10"/><w:insideH w:val="single" w:sz="10"/><w:insideV w:val="single" w:sz="10"/></w:tblBorders></w:tblPr>'
            . '<w:tblGrid><w:gridCol w:w="' . $columns['day'] . '"/><w:gridCol w:w="' . $columns['time'] . '"/><w:gridCol w:w="' . $columns['code'] . '"/><w:gridCol w:w="' . $columns['description'] . '"/><w:gridCol w:w="' . $columns['course'] . '"/><w:gridCol w:w="' . $columns['instructor'] . '"/></w:tblGrid>'
            . $this->docxTableRow([
                ['Day', $columns['day'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Time', $columns['time'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Code', $columns['code'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Description', $columns['description'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Course', $columns['course'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
                ['Instructor', $columns['instructor'], ['align' => 'center', 'bold' => true, 'color' => 'FFFFFF', 'fill' => '000000', 'size' => 11]],
            ])
            . $rows
            . '</w:tbl>'
            . '<w:p><w:pPr><w:sectPr><w:pgSz w:orient="landscape" w:w="16838" w:h="11906"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="0" w:footer="0" w:gutter="0"/><w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr></w:pPr></w:p>'
            . '</w:body></w:document>';
    }

    private function docxHeaderTable(bool $hasLogo): string
    {
        $logo = $hasLogo ? $this->docxImageRun('rIdLogo', 2915415, 1252739) : '';

        return '<w:tbl><w:tblPr><w:tblW w:w="15440" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblBorders><w:bottom w:val="single" w:sz="24" w:color="808080"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="5000"/><w:gridCol w:w="10440"/></w:tblGrid>'
            . '<w:tr>'
            . '<w:tc><w:tcPr><w:tcW w:w="5000" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r>' . $logo . '</w:r></w:p></w:tc>'
            . '<w:tc><w:tcPr><w:tcW w:w="10440" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>'
            . $this->docxParagraph([['text' => 'COMTEQ COMPUTER AND BUSINESS COLLEGE, INC.', 'bold' => true, 'color' => '8497D2', 'size' => 16]], 'left')
            . $this->docxParagraph([['text' => '#63 Fendler st., East Tapinac, Olongapo City, Philippines', 'bold' => true, 'color' => '6E6E6E', 'size' => 10]], 'left')
            . $this->docxParagraph([['text' => 'Mobile no.: 09428197810 | Tel No.: (047) 602-4778 | www.comteq.edu.ph', 'bold' => true, 'color' => '6E6E6E', 'size' => 10]], 'left')
            . '</w:tc></w:tr></w:tbl>';
    }

    private function docxParagraph(array $runs, string $align = 'left'): string
    {
        $content = collect($runs)->map(fn (array $run) => $this->docxTextRun($run))->implode('');

        return '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/><w:jc w:val="' . $align . '"/></w:pPr>' . $content . '</w:p>';
    }

    private function docxTextRun(array $run): string
    {
        $font = $this->docxEscape($run['font'] ?? 'Arial');
        $size = (int) (($run['size'] ?? 22) * 2);
        $color = $this->docxEscape($run['color'] ?? '000000');
        $bold = ! empty($run['bold']) ? '<w:b/>' : '';
        $underline = ! empty($run['underline']) ? '<w:u w:val="single"/>' : '';

        return '<w:r><w:rPr><w:rFonts w:ascii="' . $font . '" w:hAnsi="' . $font . '"/>' . $bold . $underline . '<w:color w:val="' . $color . '"/><w:sz w:val="' . $size . '"/></w:rPr><w:t xml:space="preserve">' . $this->docxEscape((string) ($run['text'] ?? '')) . '</w:t></w:r>';
    }

    private function docxTableRow(array $cells): string
    {
        $row = '<w:tr>';
        foreach ($cells as $cell) {
            [$text, $width, $options] = [$cell[0], $cell[1], $cell[2] ?? []];
            $row .= $this->docxTableCell((string) $text, (int) $width, $options);
        }

        return $row . '</w:tr>';
    }

    private function docxTableCell(string $text, int $width, array $options = []): string
    {
        $fill = isset($options['fill']) ? '<w:shd w:fill="' . $this->docxEscape($options['fill']) . '"/>' : '';
        $gridSpan = isset($options['colspan']) ? '<w:gridSpan w:val="' . (int) $options['colspan'] . '"/>' : '';
        $align = $options['align'] ?? 'left';
        $color = $options['color'] ?? '000000';
        $bold = ! empty($options['bold']);

        return '<w:tc><w:tcPr><w:tcW w:w="' . $width . '" w:type="dxa"/>' . $gridSpan . $fill . '<w:tcMar><w:top w:w="60" w:type="dxa"/><w:left w:w="60" w:type="dxa"/><w:bottom w:w="60" w:type="dxa"/><w:right w:w="60" w:type="dxa"/></w:tcMar><w:vAlign w:val="center"/></w:tcPr>'
            . $this->docxParagraph([['text' => $text, 'bold' => $bold, 'color' => $color, 'size' => $options['size'] ?? 10]], $align)
            . '</w:tc>';
    }

    private function docxImageRun(string $relationshipId, int $widthEmu, int $heightEmu): string
    {
        return '<w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $widthEmu . '" cy="' . $heightEmu . '"/><wp:docPr id="1" name="COMTEQ Logo"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="1" name="logo1.png"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="' . $relationshipId . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $widthEmu . '" cy="' . $heightEmu . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';
    }

    private function docxEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function buildInstructorSchedulePdfPreview(
        string $instructor,
        string $academicYear,
        string $semesterLabel,
        $schedules
    ): string {
        $scheduleRows = $this->mergedScheduleDocumentRows($schedules);
        $pdf = $this->scheduleReportPdf('Faculty Schedule Preview');
        $this->drawScheduleReportHeader($pdf, 'COLLEGE FACULTY LOADING', [
            ['Name of Instructor', $instructor],
            ['Academic Year', $academicYear],
            ['Semester', $semesterLabel],
        ]);

        $columns = [
            ['Code', 24],
            ['Description', 100],
            ['Units', 14],
            ['Day', 18],
            ['Time', 45],
            ['Room', 22],
            ['Course', 60],
        ];
        $this->drawPdfTableHeader($pdf, $columns);

        if ($scheduleRows->isEmpty()) {
            $pdf->Cell(283, 9, 'No schedules found for this instructor.', 1, 1, 'C');

            return $pdf->Output('', 'S');
        }

        $totalUnits = 0;
        foreach ($scheduleRows as $row) {
            $schedule = $row['schedule'];
            $description = $this->scheduleSubjectNameForRow($row);
            if (($schedule->schedule_for ?: 'Whole Class') !== 'Whole Class') {
                $description .= ' (' . $schedule->schedule_for . ')';
            }

            $units = $row['units'] ?? $this->scheduleUnits($schedule);
            $totalUnits += $units;
            $this->drawPdfTableRow($pdf, [
                [$schedule->subject->code, 24, 'C'],
                [$description, 100, 'L'],
                [(string) $units, 14, 'C'],
                [$row['day_label'], 18, 'C'],
                [$this->scheduleTimeLabel($schedule), 45, 'C'],
                [$schedule->room->name, 22, 'C'],
                [$row['course_label'] ?? $this->scheduleCourseGroup($schedule), 60, 'C'],
            ]);
        }

        $this->drawPdfTableRow($pdf, [
            ['Total Units', 124, 'R'],
            [(string) $totalUnits, 14, 'C'],
            ['', 145, 'C'],
        ], true);

        return $pdf->Output('', 'S');
    }

    private function buildRoomSchedulePdfPreview(
        string $roomName,
        string $academicYear,
        string $semesterLabel,
        $schedules
    ): string {
        $scheduleRows = $this->mergedScheduleDocumentRows($schedules);
        $pdf = $this->scheduleReportPdf('Room Schedule Preview');
        $this->drawScheduleReportHeader($pdf, 'ROOM SCHEDULE', [
            ['Room', $roomName],
            ['Academic Year', $academicYear],
            ['Semester', $semesterLabel],
        ]);

        $columns = [
            ['Day', 18],
            ['Time', 45],
            ['Code', 24],
            ['Description', 100],
            ['Course', 45],
            ['Instructor', 51],
        ];
        $this->drawPdfTableHeader($pdf, $columns);

        if ($scheduleRows->isEmpty()) {
            $pdf->Cell(283, 9, 'No schedules found for this room.', 1, 1, 'C');

            return $pdf->Output('', 'S');
        }

        foreach ($scheduleRows as $row) {
            $schedule = $row['schedule'];
            $description = $this->scheduleSubjectNameForRow($row);
            if (($schedule->schedule_for ?: 'Whole Class') !== 'Whole Class') {
                $description .= ' (' . $schedule->schedule_for . ')';
            }

            $this->drawPdfTableRow($pdf, [
                [$row['day_label'], 18, 'C'],
                [$this->scheduleTimeLabel($schedule), 45, 'C'],
                [$schedule->subject->code, 24, 'C'],
                [$description, 100, 'L'],
                [$row['course_label'] ?? $this->scheduleCourseGroup($schedule), 45, 'C'],
                [$schedule->instructor ?: '', 51, 'L'],
            ]);
        }

        return $pdf->Output('', 'S');
    }

    private function scheduleReportPdf(string $title): \TCPDF
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('COMTEQ Enrollment System');
        $pdf->SetTitle($title);
        $pdf->SetMargins(7, 7, 7);
        $pdf->SetAutoPageBreak(true, 8);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        return $pdf;
    }

    private function drawScheduleReportHeader(\TCPDF $pdf, string $title, array $details): void
    {
        $logoPath = public_path('images/logo1-schedule.jpg');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, -1, 78, 0, 'JPG');
        }

        $pdf->SetTextColor(132, 151, 210);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetXY(88, 7);
        $pdf->Cell(200, 8, 'COMTEQ COMPUTER AND BUSINESS COLLEGE, INC.', 0, 1, 'L');
        $pdf->SetTextColor(110, 110, 110);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX(88);
        $pdf->Cell(200, 5, '#63 Fendler st., East Tapinac, Olongapo City, Philippines', 0, 1, 'L');
        $pdf->SetX(88);
        $pdf->Cell(200, 5, 'Mobile no.: 09428197810 | Tel No.: (047) 602-4778 | www.comteq.edu.ph', 0, 1, 'L');
        $pdf->SetDrawColor(120, 120, 120);
        $pdf->Line(7, 29, 290, 29);

        $pdf->SetY(33);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 7, $title, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        foreach ($details as [$label, $value]) {
            $pdf->Cell(45, 6, $label . ':', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, $value, 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
        }
        $pdf->Ln(2);
    }

    private function drawPdfTableHeader(\TCPDF $pdf, array $columns): void
    {
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        foreach ($columns as [$label, $width]) {
            $pdf->Cell($width, 8, $label, 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8.5);
    }

    private function drawPdfTableRow(\TCPDF $pdf, array $cells, bool $bold = false): void
    {
        if ($pdf->GetY() > 190) {
            $pdf->AddPage();
        }

        $pdf->SetFont('helvetica', $bold ? 'B' : '', 8.5);
        $rowHeight = 8;
        foreach ($cells as [$text, $width]) {
            $rowHeight = max($rowHeight, (int) ceil($pdf->GetStringWidth((string) $text) / max(1, ($width - 3))) * 5);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $offset = 0;
        foreach ($cells as [$text, $width, $align]) {
            $pdf->MultiCell($width, $rowHeight, (string) $text, 1, $align, false, 0, $x + $offset, $y, true, 0, false, true, $rowHeight, 'M');
            $offset += $width;
        }
        $pdf->Ln($rowHeight);
    }

    private function buildSchedulePdf(
        string $academicYear,
        string $courseName,
        string $yearLabel,
        string $semesterLabel,
        $schedules
    ): string {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('COMTEQ Enrollment System');
        $pdf->SetTitle('Class Schedule');
        $pdf->SetMargins(7, 5, 7);
        $pdf->SetAutoPageBreak(true, 8);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $drawHeader = function () use ($pdf, $academicYear, $courseName, $yearLabel, $semesterLabel): void {
            $pdf->SetY(6);
            $logoPath = public_path('images/logo1-schedule.jpg');
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, 10, -1, 78, 0, 'JPG');
            }

            $pdf->SetTextColor(132, 151, 210);
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->SetXY(88, 5);
            $pdf->Cell(200, 8, 'COMTEQ COMPUTER AND BUSINESS COLLEGE, INC.', 0, 1, 'L');

            $pdf->SetTextColor(110, 110, 110);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetX(88);
            $pdf->Cell(200, 6, '#63 Fendler st., East Tapinac, Olongapo City, Philippines', 0, 1, 'L');
            $pdf->SetX(88);
            $pdf->Cell(200, 6, 'Mobile no.: 09428197810 | Tel No.: (047) 602-4778 | www.comteq.edu.ph', 0, 1, 'L');

            $pdf->SetDrawColor(120, 120, 120);
            $pdf->SetLineWidth(0.7);
            $pdf->Line(7, 29, 290, 29);

            $pdf->SetY(32);
            $pdf->SetFont('times', 'BU', 20);
            $pdf->SetTextColor(230, 0, 0);
            $pdf->Cell(101, 8, $yearLabel, 0, 0, 'R');
            $pdf->SetFont('times', 'B', 20);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(8, 8, '|', 0, 0, 'C');
            $pdf->SetTextColor(8, 34, 86);
            $pdf->Cell(64, 8, $semesterLabel, 0, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(8, 8, '|', 0, 0, 'C');
            $pdf->Cell(65, 8, 'AY ' . $academicYear, 0, 1, 'L');

            $pdf->SetFont('times', 'B', 20);
            $pdf->Cell(0, 7, $courseName, 0, 1, 'C');
            $pdf->Ln(2);
        };

        $drawTableHeader = function () use ($pdf): void {
            $pdf->SetFillColor(0, 0, 0);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.3);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(24, 9, 'Code', 1, 0, 'C', true);
            $pdf->Cell(139, 9, 'Subjects', 1, 0, 'C', true);
            $pdf->Cell(16, 9, 'Day', 1, 0, 'C', true);
            $pdf->Cell(51, 9, 'Time', 1, 0, 'C', true);
            $pdf->Cell(18, 9, 'Room', 1, 0, 'C', true);
            $pdf->Cell(35, 9, 'Instructor', 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
        };

        $pdf->AddPage();
        $drawHeader();
        $drawTableHeader();

        $pdf->SetFont('helvetica', '', 10.5);
        if ($schedules->isEmpty()) {
            $pdf->Cell(283, 9, 'No schedules found for this class.', 1, 1, 'C');

            return $pdf->Output('', 'S');
        }

        $scheduleRows = $schedules
            ->groupBy(fn (SubjectSchedule $schedule) => implode('|', [
                $schedule->subject_id,
                $schedule->schedule_for ?: 'Whole Class',
                $schedule->time_slot_id,
                $schedule->room_id,
                strtolower(trim((string) $schedule->instructor)),
            ]))
            ->map(fn ($group) => [
                'schedule' => $group->first(),
                'day_label' => $this->scheduleDayLabels($group),
                'type_label' => $this->scheduleTypeLabelForGroup($group),
                'units' => $this->scheduleGroupUnits($group),
            ])
            ->values();

        foreach ($scheduleRows as $row) {
            $schedule = $row['schedule'];
            if ($pdf->GetY() > 190) {
                $pdf->AddPage();
                $drawHeader();
                $drawTableHeader();
                $pdf->SetFont('helvetica', '', 10.5);
            }

            $subjectName = $this->scheduleSubjectNameForRow($row);
            if (($schedule->schedule_for ?: 'Whole Class') !== 'Whole Class') {
                $subjectName .= ' (' . $schedule->schedule_for . ')';
            }
            $lineCount = max(1, (int) ceil($pdf->GetStringWidth($subjectName) / 132));
            $rowHeight = max(8, $lineCount * 6);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->MultiCell(24, $rowHeight, $schedule->subject->code, 1, 'C', false, 0, $x, $y, true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(139, $rowHeight, $subjectName, 1, 'L', false, 0, $x + 24, $y, true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(16, $rowHeight, $row['day_label'], 1, 'C', false, 0, $x + 163, $y, true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(51, $rowHeight, $this->scheduleTimeLabel($schedule), 1, 'C', false, 0, $x + 179, $y, true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(18, $rowHeight, $schedule->room->name, 1, 'C', false, 0, $x + 230, $y, true, 0, false, true, $rowHeight, 'M');
            $pdf->MultiCell(35, $rowHeight, $schedule->instructor ?: '', 1, 'L', false, 1, $x + 248, $y, true, 0, false, true, $rowHeight, 'M');
        }

        return $pdf->Output('', 'S');
    }

    private function pdfFirstPageSize(string $path): array
    {
        $pdf = new Fpdi();
        $pdf->setSourceFile($path);
        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);

        return [
            'width' => (float) ($size['width'] ?? 381),
            'height' => (float) ($size['height'] ?? 508),
        ];
    }

    private function syncEnrollmentTemplatePageSize(EnrollmentTemplate $template): EnrollmentTemplate
    {
        $path = $this->templateAbsolutePath($template->file_path);

        if (! $path) {
            return $template;
        }

        try {
            $size = $this->pdfFirstPageSize($path);
        } catch (\Throwable) {
            return $template;
        }

        $oldWidth = max(1, (float) $template->page_width);
        $oldHeight = max(1, (float) $template->page_height);
        $newWidth = max(1, (float) $size['width']);
        $newHeight = max(1, (float) $size['height']);

        if (abs($oldWidth - $newWidth) < 0.01 && abs($oldHeight - $newHeight) < 0.01) {
            return $template;
        }

        $scaleX = $newWidth / $oldWidth;
        $scaleY = $newHeight / $oldHeight;
        $mappings = collect($template->field_mappings ?? [])
            ->map(function ($mapping) use ($scaleX, $scaleY) {
                $mapping['x'] = round((float) ($mapping['x'] ?? 0) * $scaleX, 2);
                $mapping['y'] = round((float) ($mapping['y'] ?? 0) * $scaleY, 2);

                return $mapping;
            })
            ->values()
            ->all();

        $template->update([
            'page_width' => $newWidth,
            'page_height' => $newHeight,
            'field_mappings' => $mappings,
        ]);

        return $template->fresh() ?? $template;
    }

    private function templatePayload(EnrollmentTemplate $template): array
    {
        $template = $this->syncEnrollmentTemplatePageSize($template);

        return [
            'id' => $template->id,
            'name' => $template->name,
            'original_filename' => $template->original_filename,
            'page_width' => (float) $template->page_width,
            'page_height' => (float) $template->page_height,
            'field_mappings' => $template->field_mappings ?? [],
            'pdf_url' => route('academic.templates.pdf', $template),
            'save_url' => route('academic.templates.mappings.update', $template),
        ];
    }

    private function idTemplatePayload(IdTemplate $template): array
    {
        $layout = $template->layout_config ?? [];

        return [
            'id' => $template->id,
            'name' => $template->name,
            'side' => $template->side,
            'school_year' => $template->school_year,
            'background_url' => route('academic.id-templates.background', $template),
            'save_url' => route('academic.id-templates.layout.update', $template),
            'width' => (float) ($layout['width'] ?? 540),
            'height' => (float) ($layout['height'] ?? 340),
            'fields' => $layout['fields'] ?? [],
        ];
    }
}
