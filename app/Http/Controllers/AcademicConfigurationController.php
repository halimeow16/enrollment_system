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
            'label' => ['nullable', 'string', 'max:80'],
        ]);

        $slot = TimeSlot::updateOrCreate(
            ['start_time' => $data['start_time'], 'end_time' => $data['end_time']],
            $data + ['is_active' => true]
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
        ActivityLog::record('subject_schedule_assigned', $schedule, [], [
            'subject' => $schedule->subject->code,
            'day' => $schedule->day->name,
            'time' => $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . ' - ' . $schedule->timeSlot->end_time),
            'room' => $schedule->room->name,
        ], $request);

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
        $schedule->load(['subject', 'day', 'timeSlot', 'room']);
        $oldValues = [
            'subject' => $schedule->subject->code,
            'day' => $schedule->day->name,
            'time' => $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . ' - ' . $schedule->timeSlot->end_time),
            'room' => $schedule->room->name,
        ];
        $schedule->delete();
        ActivityLog::record('subject_schedule_removed', $schedule, $oldValues, [], $request);

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
