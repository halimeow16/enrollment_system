<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Models\Enrollment;   // Make sure this model exists

class EnrollmentController extends Controller
{
    /**
     * Show the enrollment form
     */
    public function create()
    {
        return view('enrollment.create');
    }

    /**
     * Store enrollment and generate filled PDF
     */
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
        ]);

        $enrollment = Enrollment::create($validated);

        $pdfContent = $this->fillExistingPDF($enrollment);

        $filename = 'Enrollment_' . ($enrollment->student_number ?? 'Unknown') . '_' . now()->format('YmdHis') . '.pdf';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Fill your existing PDF template using FPDI
     */
    private function fillExistingPDF($enrollment)
    {
        $pdf = new Fpdi();

        $templatePath = public_path('templates/enrollment-template.pdf');

        if (!file_exists($templatePath)) {
            abort(500, 'PDF Template not found!');
        }

        $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);

        // Use exact size from your PDF
        $pdf->AddPage('P', [381, 508]);     // Width 381mm, Height 508mm, Portrait
        $pdf->useTemplate($templateId);

        // Font Configuration
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(0, 0, 0);

        // ====================== FILL FIELDS ======================

        // Top Header
        $pdf->SetXY(88, 38);  $pdf->Write(0, $enrollment->student_number ?? '');
        $pdf->SetXY(380, 48);  $pdf->Write(0, $enrollment->date_filed ? $enrollment->date_filed->format('m/d/Y') : '');
        $pdf->SetXY(580, 48);  $pdf->Write(0, $enrollment->school_year ?? '2026-2027');

        // Name Fields
        $pdf->SetXY(135, 78);  $pdf->Write(0, $enrollment->last_name ?? '');
        $pdf->SetXY(340, 78);  $pdf->Write(0, $enrollment->first_name ?? '');
        $pdf->SetXY(520, 78);  $pdf->Write(0, $enrollment->middle_name ?? '');

        // Contact
        $pdf->SetXY(135, 105); $pdf->Write(0, $enrollment->cellphone ?? '');
        $pdf->SetXY(380, 105); $pdf->Write(0, $enrollment->email ?? '');

        // Course & Academic
        $pdf->SetXY(135, 145); $pdf->Write(0, $enrollment->course_code ?? '');
        $pdf->SetXY(380, 175); $pdf->Write(0, $enrollment->year_level ?? '');
        $pdf->SetXY(520, 175); $pdf->Write(0, $enrollment->semester ?? '');

        // Personal Information
        $pdf->SetXY(135, 280); $pdf->Write(0, $enrollment->present_address ?? '');
        $pdf->SetXY(480, 280); $pdf->Write(0, $enrollment->barangay ?? '');

        $pdf->SetXY(135, 305); $pdf->Write(0, $enrollment->city ?? '');
        $pdf->SetXY(480, 305); $pdf->Write(0, $enrollment->province ?? '');

        $pdf->SetXY(135, 330); $pdf->Write(0, $enrollment->date_of_birth ? $enrollment->date_of_birth->format('m/d/Y') : '');
        $pdf->SetXY(380, 330); $pdf->Write(0, $enrollment->age ?? '');
        $pdf->SetXY(520, 330); $pdf->Write(0, $enrollment->gender ?? '');

        $pdf->SetXY(135, 355); $pdf->Write(0, $enrollment->place_of_birth ?? '');
        $pdf->SetXY(520, 355); $pdf->Write(0, $enrollment->religion ?? '');

        // Parents Information
        $pdf->SetXY(135, 390); $pdf->Write(0, $enrollment->father_name ?? '');
        $pdf->SetXY(480, 390); $pdf->Write(0, $enrollment->father_cpNumber ?? '');

        $pdf->SetXY(135, 415); $pdf->Write(0, $enrollment->mother_name ?? '');
        $pdf->SetXY(480, 415); $pdf->Write(0, $enrollment->mother_cpNumber ?? '');

        return $pdf->Output('', 'S');
    }
}