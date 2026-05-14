<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Models\Enrollment;

class EnrollmentController extends Controller
{
    public function create()
    {
        return view('enrollment.create');
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
            'credentials'      => 'nullable|array',
            'credentials.*'    => 'string',
        ]);

        $enrollment = Enrollment::create($validated);

        $pdfContent = $this->fillExistingPDF($enrollment);

        $filename = 'Enrollment_' . ($enrollment->student_number ?? 'Unknown') . '_' . now()->format('YmdHis') . '.pdf';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function preview(Request $request)
    {
        $data = $request->all();
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

        // Checkboxes
        $this->checkCourseBox($pdf, $data->course_code ?? '');
        $this->checkYearLevelBox($pdf, $data->year_level ?? '');
        $this->checkSemesterBox($pdf, $data->semester ?? '');

        $this->checkCredentialBoxes($pdf, $data->credentials ?? []);

        return $pdf->Output('', 'S');
    }

    // ==================== CHECKBOX FUNCTIONS ====================

    private function checkCourseBox($pdf, $courseCode)
    {
        $pdf->SetFont('dejavusans', 'B', 32);
        $pdf->SetTextColor(0, 100, 0);
        $check = '✓';

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
        $check = '✓';

        switch (trim($yearLevel)) {
            case '1': $pdf->SetXY(131, 99); $pdf->Write(0, $check); break; // 1st
            case '2': $pdf->SetXY(152, 99); $pdf->Write(0, $check); break; // 2nd
            case '3': $pdf->SetXY(173, 99); $pdf->Write(0, $check); break; // 3rd
            case '4': $pdf->SetXY(194, 99); $pdf->Write(0, $check); break; // 4th
        }
    }

    private function checkSemesterBox($pdf, $semester)
    {
        $pdf->SetFont('dejavusans', 'B', 32);
        $pdf->SetTextColor(0, 100, 0);
        $check = '✓';

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

        $check = '✓';

        if (!is_array($credentials)) {
            return;
        }

        $positions = [

            // COLUMN 1
            'form_138' => [54, 126.5],
            'birth_certificate' => [54, 138],
            'good_moral' => [54, 149],

            // COLUMN 2
            'certificate_grades' => [133.5, 126.5],
            'certificate_eligibility' => [133.5, 138],
            'transcript' => [133.5, 149],

            // COLUMN 3
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