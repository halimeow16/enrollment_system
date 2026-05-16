<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Models\DepartmentHead;
use App\Models\Room;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcademicConfigurationController extends Controller
{
    public function storeSubject(Request $request): RedirectResponse
    {
        $data = $this->validateSubject($request);
        $data['total_units'] = (float) $data['lecture_units'] + (float) $data['laboratory_units'];

        Subject::create($data);

        return back()->with('success', 'Subject added.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $data = $this->validateSubject($request, $subject);
        $data['total_units'] = (float) $data['lecture_units'] + (float) $data['laboratory_units'];

        $subject->update($data);

        return back()->with('success', 'Subject updated.');
    }

    public function destroySubject(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('success', 'Subject removed.');
    }

    public function storeDay(Request $request): RedirectResponse
    {
        Day::updateOrCreate(
            ['name' => $request->validate(['name' => ['required', 'string', 'max:50']])['name']],
            ['is_active' => true, 'sort_order' => Day::max('sort_order') + 1]
        );

        return back()->with('success', 'Day saved.');
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'building' => ['nullable', 'string', 'max:80'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        Room::updateOrCreate(['name' => $data['name']], $data + ['is_active' => true]);

        return back()->with('success', 'Room saved.');
    }

    public function storeTimeSlot(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'label' => ['nullable', 'string', 'max:80'],
        ]);

        TimeSlot::updateOrCreate(
            ['start_time' => $data['start_time'], 'end_time' => $data['end_time']],
            $data + ['is_active' => true]
        );

        return back()->with('success', 'Time slot saved.');
    }

    public function storeSchedule(Request $request): RedirectResponse
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
            return back()->withErrors(['schedule' => 'That room is already assigned for the selected day and time.'])->withInput();
        }

        SubjectSchedule::create($data);

        return back()->with('success', 'Schedule assigned.');
    }

    public function destroySchedule(SubjectSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('success', 'Schedule removed.');
    }

    public function storeDepartmentHead(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($data): void {
            DepartmentHead::where('course_code', $data['course_code'])->update(['is_active' => false]);
            DepartmentHead::create($data + ['is_active' => true]);
        });

        return back()->with('success', 'Department head updated.');
    }

    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('subjects', 'code')->ignore($subject?->id)],
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
}
