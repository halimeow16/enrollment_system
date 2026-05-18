<?php

namespace App\Http\Controllers;

use App\Models\Day;
use App\Models\DepartmentHead;
use App\Models\EnrollmentTemplate;
use App\Models\FeeConfiguration;
use App\Models\Room;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $data = $this->applyFixedSubjectUnits($data);

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

        return response()->json([
            'message' => 'Template mapping saved.',
            'template' => $this->templatePayload($template->fresh()),
        ]);
    }

    public function showEnrollmentTemplatePdf(EnrollmentTemplate $template)
    {
        abort_unless(Storage::disk('public')->exists($template->file_path), 404);

        return response()->file(Storage::disk('public')->path($template->file_path), [
            'Content-Type' => 'application/pdf',
        ]);
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
                'code' => $schedule->subject->code,
                'name' => $schedule->subject->name,
            ],
            'day' => $schedule->day->name,
            'time' => $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . ' - ' . $schedule->timeSlot->end_time),
            'room' => $schedule->room->name,
        ];
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
}
