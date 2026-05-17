<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Models\DepartmentHead;
use App\Models\Room;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicConfigurationController extends Controller
{
    public function storeSubject(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateSubject($request);
        $data['total_units'] = (float) $data['lecture_units'] + (float) $data['laboratory_units'];

        $subject = Subject::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Subject added.',
                'subject' => $this->subjectPayload($subject),
            ], 201);
        }

        return back()->with('success', 'Subject added.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse|JsonResponse
    {
        $data = $this->validateSubject($request, $subject);
        $data['total_units'] = (float) $data['lecture_units'] + (float) $data['laboratory_units'];

        $subject->update($data);

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
        $subject->delete();

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
            'label' => ['nullable', 'string', 'max:80'],
        ]);

        $slot = TimeSlot::updateOrCreate(
            ['start_time' => $data['start_time'], 'end_time' => $data['end_time']],
            $data + ['is_active' => true]
        );

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
            'day_id' => ['required', 'exists:days,id'],
            'time_slot_id' => ['required', 'exists:time_slots,id'],
            'room_id' => ['required', 'exists:rooms,id'],
        ]);

        $roomConflict = SubjectSchedule::query()
            ->where('day_id', $data['day_id'])
            ->where('time_slot_id', $data['time_slot_id'])
            ->where('room_id', $data['room_id'])
            ->exists();

        if ($roomConflict) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'That room is already assigned for the selected day and time.',
                ], 422);
            }

            return back()->withErrors(['schedule' => 'That room is already assigned for the selected day and time.'])->withInput();
        }

        $schedule = SubjectSchedule::create($data)->load(['subject', 'day', 'timeSlot', 'room']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Schedule assigned.',
                'schedule' => $this->schedulePayload($schedule),
            ], 201);
        }

        return back()->with('success', 'Schedule assigned.');
    }

    public function destroySchedule(Request $request, SubjectSchedule $schedule): RedirectResponse|JsonResponse
    {
        $id = $schedule->id;
        $schedule->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Schedule removed.',
                'schedule_id' => $id,
            ]);
        }

        return back()->with('success', 'Schedule removed.');
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
            'description' => ['nullable', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:30'],
            'year_level' => ['required', Rule::in(['1', '2', '3', '4'])],
            'semester' => ['required', Rule::in(['1st', '2nd', 'Summer'])],
            'type' => ['required', Rule::in(['LEC', 'LAB', 'BOTH'])],
            'lecture_units' => ['required', 'numeric', 'min:0', 'max:9'],
            'laboratory_units' => ['required', 'numeric', 'min:0', 'max:9'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function subjectPayload(Subject $subject): array
    {
        return [
            'id' => $subject->id,
            'code' => $subject->code,
            'name' => $subject->name,
            'description' => $subject->description,
            'course_code' => $subject->course_code,
            'year_level' => $subject->year_level,
            'semester' => $subject->semester,
            'type' => $subject->type,
            'lecture_units' => (float) $subject->lecture_units,
            'laboratory_units' => (float) $subject->laboratory_units,
            'total_units' => (float) $subject->total_units,
        ];
    }

    private function schedulePayload(SubjectSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'subject' => [
                'code' => $schedule->subject->code,
                'name' => $schedule->subject->name,
            ],
            'day' => $schedule->day->name,
            'time' => $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . ' - ' . $schedule->timeSlot->end_time),
            'room' => $schedule->room->name,
        ];
    }
}
