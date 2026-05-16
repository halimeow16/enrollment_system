<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Models\Enrollment;
use App\Models\DepartmentHead;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function create()
    {
        $subjects = Subject::with(['schedules.day', 'schedules.timeSlot', 'schedules.room'])
            ->where('is_active', true)
            ->orderBy('course_code')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('code')
            ->get();

        $departmentHeads = DepartmentHead::where('is_active', true)
            ->get()
            ->keyBy('course_code');

        return view('enrollment.create', compact('subjects', 'departmentHeads'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_number'   => 'nullable|string|max:50',
            'date_filed'       => 'nullable|date',
            'school_year'      => 'nullable|string',
            'first_name'       => 'nullable|string|max:100',
            'middle_name'      => 'nullable|string|max:100',
            'last_name'        => 'nullable|string|max:100',
            'cellphone'        => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:100',
            'last_school'      => 'nullable|string|max:150',
            'present_address'  => 'nullable|string',
            'barangay'         => 'nullable|string',
            'city'             => 'nullable|string',
            'province'         => 'nullable|string',
            'date_of_birth'    => 'nullable|date',
            'age'              => 'nullable|integer|min:1',
            'place_of_birth'   => 'nullable|string',
            'civil_status'     => 'nullable|string',
            'gender'           => 'nullable|string',
            'religion'         => 'nullable|string',
            'father_name'      => 'nullable|string',
            'father_address'   => 'nullable|string',
            'father_cpNumber'  => 'nullable|string',
            'mother_name'      => 'nullable|string',
            'mother_address'   => 'nullable|string',
            'mother_cpNumber'  => 'nullable|string',
            'course_code'      => 'nullable|string',
            'course_name'      => 'nullable|string',
            'year_level'       => 'nullable|string',
            'semester'         => 'nullable|string',
            'department_head_name' => 'nullable|string|max:120',
            'subject_ids'      => 'nullable|array',
            'subject_ids.*'    => 'integer|exists:subjects,id',
            'credentials'      => 'nullable|array',
            'credentials.*'    => 'string',
        ]);

        $selectedSubjects = Subject::with(['schedules.day', 'schedules.timeSlot', 'schedules.room'])
            ->whereIn('id', $validated['subject_ids'] ?? [])
            ->get();

        $conflicts = $this->detectScheduleConflicts($selectedSubjects);

        if (! empty($conflicts)) {
            return back()
                ->withErrors(['subject_ids' => 'Subject schedule conflict: ' . implode(' ', $conflicts)])
                ->withInput();
        }

        $validated['department_head_name'] = DepartmentHead::where('course_code', $validated['course_code'] ?? null)
            ->where('is_active', true)
            ->value('name') ?? $validated['department_head_name'] ?? null;

        unset($validated['subject_ids']);

        $enrollment = DB::transaction(function () use ($validated, $selectedSubjects) {
            $enrollment = Enrollment::create($validated);

            foreach ($selectedSubjects as $subject) {
                $enrollment->subjects()->attach($subject->id, [
                    'lecture_units' => $subject->lecture_units,
                    'laboratory_units' => $subject->laboratory_units,
                    'total_units' => $subject->total_units,
                ]);
            }

            return $enrollment;
        });

        $pdfContent = $this->fillExistingPDF($enrollment);

        $filename = 'Enrollment_' . ($enrollment->student_number ?? 'Unknown') . '_' . now()->format('YmdHis') . '.pdf';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function preview(Request $request)
    {
        $data = $request->all();
        $data['department_head_name'] = DepartmentHead::where('course_code', $data['course_code'] ?? null)
            ->where('is_active', true)
            ->value('name') ?? $data['department_head_name'] ?? null;
        $pdfContent = $this->fillExistingPDF((object)$data);

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf');
    }

    private function fillExistingPDF($data)
    {
        $pdf = new Fpdi();

        $templatePath = public_path('templates/enrollment-template.pdf');

        if (!file_exists($templatePath)) {
            abort(500, 'PDF Template not found!');
        }

        $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);

        $pdf->AddPage('P', [381, 508]);
        $pdf->useTemplate($templateId);

        $pdf->SetFont('Helvetica', '', 15);
        $pdf->SetTextColor(0, 0, 0);

        // ==================== TEXT FIELDS ====================
        $pdf->SetXY(90, 37.5);   $pdf->Write(0, $data->student_number ?? '');
        $pdf->SetXY(205, 37.5);  $pdf->Write(0, $data->date_filed ?? '');
        $pdf->SetXY(313, 37.5);  $pdf->Write(0, $data->school_year ?? '');

        $pdf->SetXY(98, 45);   $pdf->Write(0, $data->last_name ?? '');
        $pdf->SetXY(185, 45);  $pdf->Write(0, $data->first_name ?? '');
        $pdf->SetXY(289, 45);  $pdf->Write(0, $data->middle_name ?? '');

        $pdf->SetXY(90, 59);   $pdf->Write(0, $data->cellphone ?? '');
        $pdf->SetXY(193, 59);  $pdf->Write(0, $data->email ?? '');
        $pdf->SetXY(105, 112);  $pdf->Write(0, $data->last_school ?? '');

        $address = $data->present_address ?? '';
        $maxWidth = 107;
        $initialFontSize = 15;
        $minFontSize = 6;
        $currentFontSize = $initialFontSize;
        $pdf->SetFont('Helvetica', '', $currentFontSize);
        while ($pdf->GetStringWidth($address) > $maxWidth && $currentFontSize > $minFontSize) {
            $currentFontSize -= 0.5;
            $pdf->SetFont('Helvetica', '', $currentFontSize);
        }
        $pdf->SetXY(84, 284);
        $pdf->Cell($maxWidth, 5, $address, 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', $initialFontSize);

        $pdf->SetXY(224, 284); $pdf->Write(0, $data->barangay ?? '');
        $pdf->SetXY(70, 291.5); $pdf->Write(0, $data->city ?? '');
        $pdf->SetXY(224, 291.5); $pdf->Write(0, $data->province ?? '');

        $pdf->SetXY(77, 299); $pdf->Write(0, $data->date_of_birth ?? '');
        $pdf->SetXY(200, 299); $pdf->Write(0, $data->age ?? '');
        $pdf->SetXY(261, 299); $pdf->Write(0, $data->civil_status ?? '');

        $place_of_birth = $data->place_of_birth ?? '';
        $maxWidth = 99; 
        $initialFontSize = 15; 
        $minFontSize = 6;      
        $currentFontSize = $initialFontSize;
        $pdf->SetFont('Helvetica', '', $currentFontSize);
        while ($pdf->GetStringWidth($place_of_birth) > $maxWidth && $currentFontSize > $minFontSize) {
            $currentFontSize -= 0.5;
            $pdf->SetFont('Helvetica', '', $currentFontSize);
        }
        $pdf->SetXY(77, 307);
        $pdf->Cell($maxWidth, 5, $place_of_birth, 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', $initialFontSize);




        $pdf->SetXY(203, 307); $pdf->Write(0, $data->gender ?? '');
        $pdf->SetXY(255, 307); $pdf->Write(0, $data->religion ?? '');

        $pdf->SetXY(80, 314); $pdf->Write(0, $data->father_name ?? '');
        $pdf->SetXY(203, 314); $pdf->Write(0, $data->father_address ?? '');
        $pdf->SetXY(300, 314); $pdf->Write(0, $data->father_cpNumber ?? '');

        $pdf->SetXY(102, 322); $pdf->Write(0, $data->mother_name ?? '');
        $pdf->SetXY(203, 322); $pdf->Write(0, $data->mother_address ?? '');
        $pdf->SetXY(300, 322); $pdf->Write(0, $data->mother_cpNumber ?? '');

        $this->checkCourseBox($pdf, $data->course_code ?? '');
        $this->checkYearLevelBox($pdf, $data->year_level ?? '');
        $this->checkSemesterBox($pdf, $data->semester ?? '');

        $this->checkCredentialBoxes($pdf, $data->credentials ?? []);

        if (! empty($data->department_head_name)) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(235, 334);
            $pdf->Write(0, 'Dept. Head: ' . $data->department_head_name);
        }

        return $pdf->Output('', 'S');
    }

    private function detectScheduleConflicts($subjects): array
    {
        $seen = [];
        $conflicts = [];

        foreach ($subjects as $subject) {
            foreach ($subject->schedules as $schedule) {
                $key = $schedule->day_id . ':' . $schedule->time_slot_id;

                if (isset($seen[$key])) {
                    $conflicts[] = $seen[$key]->code . ' conflicts with ' . $subject->code . ' on ' .
                        $schedule->day->name . ' at ' .
                        ($schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . '-' . $schedule->timeSlot->end_time)) . '.';
                    continue;
                }

                $seen[$key] = $subject;
            }
        }

        return $conflicts;
    }

    private function checkCourseBox($pdf, $courseCode)
    {
        $pdf->SetFont('dejavusans', 'B', 32);
        $pdf->SetTextColor(0, 100, 0);
        $check = html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8');

        switch (strtoupper(trim($courseCode))) {
            case 'BSIT':  $pdf->SetXY(77, 71);  $pdf->Write(0, $check); break;
            case 'BSCS':  $pdf->SetXY(158, 71); $pdf->Write(0, $check); break;
            case 'ACT':   $pdf->SetXY(225, 71); $pdf->Write(0, $check); break;
            case 'BSHM':  $pdf->SetXY(77, 86);  $pdf->Write(0, $check); break;
            case 'BSOM':  $pdf->SetXY(205, 86); $pdf->Write(0, $check); break;
            case 'BSA':   $pdf->SetXY(285, 86); $pdf->Write(0, $check); break;
        }
    }

    private function checkYearLevelBox($pdf, $yearLevel)
    {
        $pdf->SetFont('dejavusans', 'B', 32);
        $pdf->SetTextColor(0, 100, 0);
        $check = html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8');

        switch (trim($yearLevel)) {
            case '1': $pdf->SetXY(131, 99); $pdf->Write(0, $check); break;
            case '2': $pdf->SetXY(152, 99); $pdf->Write(0, $check); break; 
            case '3': $pdf->SetXY(173, 99); $pdf->Write(0, $check); break; 
            case '4': $pdf->SetXY(194, 99); $pdf->Write(0, $check); break; 
        }
    }

    private function checkSemesterBox($pdf, $semester)
    {
        $pdf->SetFont('dejavusans', 'B', 32);
        $pdf->SetTextColor(0, 100, 0);
        $check = html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8');

        switch (strtolower(trim($semester))) {
            case '1st':    $pdf->SetXY(262, 99); $pdf->Write(0, $check); break;
            case '2nd':    $pdf->SetXY(287.5, 99); $pdf->Write(0, $check); break;
            case 'summer': $pdf->SetXY(325, 99); $pdf->Write(0, $check); break;
        }
    }
    private function checkCredentialBoxes($pdf, $credentials)
    {
        $pdf->SetFont('dejavusans', 'B', 28);
        $pdf->SetTextColor(0, 100, 0);

        $check = html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8');

        if (!is_array($credentials)) {
            return;
        }

        $positions = [

            'form_138' => [54, 126.5],
            'birth_certificate' => [54, 138],
            'good_moral' => [54, 149],

            'certificate_grades' => [133.5, 126.5],
            'certificate_eligibility' => [133.5, 138],
            'transcript' => [133.5, 149],

            'long_folder' => [266.5, 126.5],
            'picture' => [266.5, 138],
        ];

        foreach ($credentials as $credential) {

            if (isset($positions[$credential])) {

                [$x, $y] = $positions[$credential];

                $pdf->SetXY($x, $y);
                $pdf->Write(0, $check);
            }
        }
    }
}
