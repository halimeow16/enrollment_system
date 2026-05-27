<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Day;
use App\Models\AppSetting;
use App\Models\DepartmentHead;
use App\Models\EnrollmentTemplate;
use App\Models\FeeConfiguration;
use App\Models\IdTemplate;
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

        $oldAcademicYear = AppSetting::getValue('academic_year');
        AppSetting::setValue('academic_year', $data['academic_year']);
        ActivityLog::record('academic_year_updated', null, [
            'academic_year' => $oldAcademicYear,
        ], [
            'academic_year' => $data['academic_year'],
        ], $request);

        return response()->json([
            'message' => 'Academic year updated.',
            'academic_year' => $data['academic_year'],
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
            'building' => ['nullable', 'string', 'max:80'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        $room = Room::updateOrCreate(['name' => $data['name']], $data + ['is_active' => true]);
        ActivityLog::record('schedule_room_saved', $room, [], $room->only(['name', 'building', 'capacity', 'is_active']), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Room saved.',
                'room' => ['id' => $room->id, 'name' => $room->name],
            ]);
        }

        return back()->with('success', 'Room saved.');
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
            'room_name' => ['required', 'string', 'max:80'],
            'instructor' => ['required', 'string', 'max:120'],
            'schedule_type' => ['required', Rule::in(['LEC', 'LAB'])],
        ]);
        $dayIds = collect($data['day_ids'])->map(fn ($dayId) => (int) $dayId)->unique()->values();

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
        $room = Room::updateOrCreate(
            ['name' => trim($data['room_name'])],
            ['is_active' => true]
        );
        $existingSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->where('subject_id', $data['subject_id'])
            ->where('schedule_type', $data['schedule_type'])
            ->get();
        $existingSchedule = $existingSchedules->first();
        $shouldOverwrite = $request->boolean('overwrite_schedule');

        if ($existingSchedule && ! $shouldOverwrite) {
            $message = "{$subject->code} - {$data['schedule_type']} already has a schedule. Do you want to replace it?";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'requires_confirmation' => true,
                    'schedule' => $this->schedulePayload($existingSchedule),
                ], 409);
            }

            return back()->withErrors(['schedule' => $message])->withInput();
        }

        $overlappingSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->when($existingSchedules->isNotEmpty() && $shouldOverwrite, fn ($query) => $query->whereNotIn('id', $existingSchedules->pluck('id')))
            ->whereIn('day_id', $dayIds)
            ->whereHas('timeSlot', function ($query) use ($timeSlot) {
                $query->where('start_time', '<', $timeSlot->end_time)
                    ->where('end_time', '>', $timeSlot->start_time);
            })
            ->get();

        $conflicts = $overlappingSchedules
            ->map(function (SubjectSchedule $schedule) use ($data, $subject, $room) {
                if ((int) $schedule->room_id === (int) $room->id) {
                    return "Room {$schedule->room->name} is already used by {$schedule->subject->code}.";
                }

                if (strtolower(trim((string) $schedule->instructor)) === strtolower(trim($data['instructor']))) {
                    return "{$data['instructor']} is already assigned to {$schedule->subject->code}.";
                }

                if ((int) $schedule->subject_id === (int) $data['subject_id']) {
                    return "{$subject->code} already has an overlapping schedule.";
                }

                $scheduledSubject = $schedule->subject;
                if (
                    $scheduledSubject
                    && $scheduledSubject->course_code === $subject->course_code
                    && $scheduledSubject->year_level === $subject->year_level
                    && $scheduledSubject->semester === $subject->semester
                ) {
                    return "{$subject->course_code} {$subject->year_level} {$subject->semester} already has {$scheduledSubject->code} at that time.";
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

        $removedScheduleIds = [];
        if ($existingSchedules->isNotEmpty() && $shouldOverwrite) {
            $oldValues = $existingSchedules->map(fn (SubjectSchedule $schedule) => [
                'subject' => $schedule->subject->code,
                'day' => $schedule->day->name,
                'time' => $this->scheduleTimeLabel($schedule),
                'room' => $schedule->room->name,
                'instructor' => $schedule->instructor,
                'schedule_type' => $schedule->schedule_type,
            ])->values()->all();
            $removedScheduleIds = $existingSchedules->pluck('id')->values()->all();
            SubjectSchedule::whereIn('id', $removedScheduleIds)->delete();
            $message = 'Schedule replaced.';
            $statusCode = 200;
            $logAction = 'subject_schedule_replaced';
        } else {
            $oldValues = [];
            $message = 'Schedule assigned.';
            $statusCode = 201;
            $logAction = 'subject_schedule_assigned';
        }

        $schedules = $dayIds->map(function (int $dayId) use ($data, $timeSlot, $room) {
            return SubjectSchedule::create([
                'subject_id' => $data['subject_id'],
                'day_id' => $dayId,
                'time_slot_id' => $timeSlot->id,
                'room_id' => $room->id,
                'instructor' => $data['instructor'],
                'schedule_type' => $data['schedule_type'],
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
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'schedule' => $this->schedulePayload($schedule),
                'schedules' => $schedules->map(fn (SubjectSchedule $schedule) => $this->schedulePayload($schedule))->values(),
                'removed_schedule_ids' => $removedScheduleIds,
                'overwritten' => $existingSchedules->isNotEmpty() && $shouldOverwrite,
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
            'room_name' => ['required', 'string', 'max:80'],
            'instructor' => ['required', 'string', 'max:120'],
            'schedule_type' => ['required', Rule::in(['LEC', 'LAB'])],
        ]);
        $dayIds = collect($data['day_ids'])->map(fn ($dayId) => (int) $dayId)->unique()->values();

        $subject = Subject::findOrFail($data['subject_id']);
        $schedule->load(['subject', 'day', 'timeSlot', 'room']);
        $relatedSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->where('subject_id', $schedule->subject_id)
            ->where('schedule_type', $schedule->schedule_type)
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
        $room = Room::updateOrCreate(
            ['name' => trim($data['room_name'])],
            ['is_active' => true]
        );

        $overlappingSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->whereNotIn('id', $relatedScheduleIds)
            ->whereIn('day_id', $dayIds)
            ->whereHas('timeSlot', function ($query) use ($timeSlot) {
                $query->where('start_time', '<', $timeSlot->end_time)
                    ->where('end_time', '>', $timeSlot->start_time);
            })
            ->get();

        $conflicts = $overlappingSchedules
            ->map(function (SubjectSchedule $existingSchedule) use ($data, $subject, $room) {
                if ((int) $existingSchedule->room_id === (int) $room->id) {
                    return "Room {$existingSchedule->room->name} is already used by {$existingSchedule->subject->code}.";
                }

                if (strtolower(trim((string) $existingSchedule->instructor)) === strtolower(trim($data['instructor']))) {
                    return "{$data['instructor']} is already assigned to {$existingSchedule->subject->code}.";
                }

                if ((int) $existingSchedule->subject_id === (int) $data['subject_id']) {
                    return "{$subject->code} already has an overlapping schedule.";
                }

                $scheduledSubject = $existingSchedule->subject;
                if (
                    $scheduledSubject
                    && $scheduledSubject->course_code === $subject->course_code
                    && $scheduledSubject->year_level === $subject->year_level
                    && $scheduledSubject->semester === $subject->semester
                ) {
                    return "{$subject->course_code} {$subject->year_level} {$subject->semester} already has {$scheduledSubject->code} at that time.";
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
        ])->values()->all();
        $removedScheduleIds = $relatedScheduleIds->values()->all();
        SubjectSchedule::whereIn('id', $removedScheduleIds)->delete();

        $schedules = $dayIds->map(function (int $dayId) use ($data, $timeSlot, $room) {
            return SubjectSchedule::create([
                'subject_id' => $data['subject_id'],
                'day_id' => $dayId,
                'time_slot_id' => $timeSlot->id,
                'room_id' => $room->id,
                'instructor' => $data['instructor'],
                'schedule_type' => $data['schedule_type'],
            ])->load(['subject', 'day', 'timeSlot', 'room']);
        });
        $schedule = $schedules->first();

        ActivityLog::record('subject_schedule_updated', $schedule, $oldValues, [
            'subject' => $schedule->subject->code,
            'days' => $schedules->pluck('day.name')->implode(', '),
            'time' => $this->scheduleTimeLabel($schedule),
            'room' => $schedule->room->name,
            'instructor' => $schedule->instructor,
            'schedule_type' => $schedule->schedule_type,
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Schedule updated.',
                'schedule' => $this->schedulePayload($schedule),
                'schedules' => $schedules->map(fn (SubjectSchedule $schedule) => $this->schedulePayload($schedule))->values(),
                'removed_schedule_ids' => $removedScheduleIds,
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

        return back()->with('success', 'Schedule updated.');
    }

    public function destroySchedule(Request $request, SubjectSchedule $schedule): RedirectResponse|JsonResponse
    {
        $schedule->load(['subject', 'day', 'timeSlot', 'room']);
        $relatedSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->where('subject_id', $schedule->subject_id)
            ->where('schedule_type', $schedule->schedule_type)
            ->where('time_slot_id', $schedule->time_slot_id)
            ->where('room_id', $schedule->room_id)
            ->where('instructor', $schedule->instructor)
            ->get();
        $ids = $relatedSchedules->pluck('id')->values()->all();
        $oldValues = $relatedSchedules->map(fn (SubjectSchedule $schedule) => [
            'subject' => $schedule->subject->code,
            'day' => $schedule->day->name,
            'time' => $this->scheduleTimeLabel($schedule),
            'room' => $schedule->room->name,
            'instructor' => $schedule->instructor,
            'schedule_type' => $schedule->schedule_type,
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
        ]);

        $schedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
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

        $academicYear = AppSetting::getValue('academic_year', '2026-2027');
        $courseName = $this->scheduleCourseName($data['course_code']);
        $fileName = str($data['course_code'] . '-' . $data['year_level'] . '-' . $data['semester'] . '-schedule.pdf')
            ->replace([' ', '/'], '-')
            ->lower()
            ->toString();

        $pdfContent = $this->buildSchedulePdf(
            $academicYear,
            $courseName,
            $this->scheduleYearLabel($data['year_level']),
            $this->scheduleSemesterLabel($data['semester']),
            $schedules
        );

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
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
        ]);

        $oldValues = ['field_count' => count($template->field_mappings ?? [])];
        $template->update([
            'field_mappings' => collect($data['mappings'])->map(fn ($mapping) => [
                'key' => $mapping['key'],
                'label' => $mapping['label'],
                'type' => $mapping['type'] ?? 'text',
                'x' => round((float) $mapping['x'], 2),
                'y' => round((float) $mapping['y'], 2),
                'page' => (int) ($mapping['page'] ?? 1),
                'font_size' => round((float) ($mapping['font_size'] ?? 10), 1),
            ])->values()->all(),
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
                $schedule->schedule_type,
                $schedule->time_slot_id,
                $schedule->room_id,
                strtolower(trim((string) $schedule->instructor)),
            ]))
            ->map(fn ($group) => [
                'schedule' => $group->first(),
                'day_label' => $this->scheduleDayLabels($group),
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

            $subjectName = $this->scheduleSubjectName($schedule);
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

    private function templatePayload(EnrollmentTemplate $template): array
    {
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
