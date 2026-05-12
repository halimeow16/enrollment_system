<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;

class EnrollmentController extends Controller
{
    public function create()
    {
        return view('enrollment.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_number'   => 'required',
            'first_name'       => 'required',
            'middle_name'      => 'nullable',
            'last_name'        => 'required',
            'present_address'  => 'required',
            'barangay'         => 'nullable',
            'city'             => 'nullable',
            'province'         => 'nullable',
            'date_of_birth'    => 'nullable',
            'age'              => 'nullable',
            'gender'           => 'nullable',
            'course_code'      => 'nullable',
            'year_level'       => 'nullable',
            'semester'         => 'nullable',
            // Add other fields you need
        ]);

        $templatePath = storage_path('app/public/templates/enrollment-template.pdf');
        $outputDir = storage_path('app/public/filled-enrollments/');

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = 'ENROLLMENT_' . $data['student_number'] . '_' . time() . '.pdf';
        $outputPath = $outputDir . $filename;

        $this->fillPdfTemplate($templatePath, $outputPath, $data);

        return response()->download($outputPath, $filename)
                         ->deleteFileAfterSend(true);
    }

    private function fillPdfTemplate($templatePath, $outputPath, $data)
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($templatePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $pdf->AddPage();
            $tplId = $pdf->importPage($i);
            $pdf->useTemplate($tplId);

            $pdf->SetFont('Helvetica', '', 12);
            $pdf->SetTextColor(0, 0, 0);

            // ==================== ADJUST THESE COORDINATES ====================
            // Format: SetXY(X, Y)  - X = horizontal, Y = vertical (from top)

            $pdf->SetXY(253, 112);  $pdf->Write(8, $data['student_number'] ?? '');
            $pdf->SetXY(45, 85);  $pdf->Write(8, $data['first_name'] . ' ' . $data['middle_name'] . ' ' . $data['last_name']);
            $pdf->SetXY(45, 105); $pdf->Write(8, $data['present_address'] ?? '');
            $pdf->SetXY(45, 125); $pdf->Write(8, ($data['barangay'] ?? '') . ', ' . ($data['city'] ?? ''));
            $pdf->SetXY(45, 145); $pdf->Write(8, $data['province'] ?? '');
            
            $pdf->SetXY(45, 165); $pdf->Write(8, $data['date_of_birth'] ?? '');
            $pdf->SetXY(45, 180); $pdf->Write(8, $data['age'] ?? '');
            $pdf->SetXY(45, 195); $pdf->Write(8, $data['gender'] ?? '');

            $pdf->SetXY(45, 250); $pdf->Write(8, ($data['course_code'] ?? '') . ' - ' . ($data['year_level'] ?? '') . ' Year');
            // Add more fields as needed...
        }

        $pdf->Output($outputPath, 'F');
    }
}