<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Day;
use App\Models\DepartmentHead;
use App\Models\Enrollment;
use App\Models\Room;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\TimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@comteq.test',
                'user_type' => 'admin',
            ],
            [
                'name' => 'Registrar Staff',
                'email' => 'registrar@comteq.test',
                'user_type' => 'registrar',
            ],
            [
                'name' => 'Department Head',
                'email' => 'department.head@comteq.test',
                'user_type' => 'department_head',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user + ['password' => 'password']
            );
        }

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $index => $day) {
            Day::updateOrCreate(['name' => $day], ['sort_order' => $index + 1, 'is_active' => true]);
        }

        foreach ([
            ['start_time' => '07:30', 'end_time' => '09:00', 'label' => '7:30 AM - 9:00 AM'],
            ['start_time' => '09:00', 'end_time' => '10:30', 'label' => '9:00 AM - 10:30 AM'],
            ['start_time' => '10:30', 'end_time' => '12:00', 'label' => '10:30 AM - 12:00 PM'],
            ['start_time' => '13:00', 'end_time' => '14:30', 'label' => '1:00 PM - 2:30 PM'],
            ['start_time' => '14:30', 'end_time' => '16:00', 'label' => '2:30 PM - 4:00 PM'],
        ] as $slot) {
            TimeSlot::updateOrCreate(
                ['start_time' => $slot['start_time'], 'end_time' => $slot['end_time']],
                $slot + ['is_active' => true]
            );
        }

        foreach (['Room 101', 'Room 102', 'Computer Lab 1', 'Computer Lab 2'] as $room) {
            Room::updateOrCreate(['name' => $room], ['is_active' => true]);
        }

        $departmentHeads = [
            ['course_code' => 'BSIT', 'name' => 'Engr. Maria Santos', 'title' => 'BSIT Department Head'],
            ['course_code' => 'BSCS', 'name' => 'Dr. Renato Cruz', 'title' => 'BSCS Department Head'],
            ['course_code' => 'ACT', 'name' => 'Ms. Liza Mercado', 'title' => 'ACT Program Head'],
            ['course_code' => 'BSHM', 'name' => 'Ms. Patricia Gomez', 'title' => 'BSHM Department Head'],
            ['course_code' => 'BSOM', 'name' => 'Mr. Daniel Reyes', 'title' => 'BSOM Department Head'],
            ['course_code' => 'BSA', 'name' => 'CPA Andrea Villanueva', 'title' => 'BSA Department Head'],
        ];

        foreach ($departmentHeads as $head) {
            DepartmentHead::updateOrCreate(
                ['course_code' => $head['course_code'], 'is_active' => true],
                $head + ['is_active' => true]
            );
        }

        $subjects = [
            ['code' => 'IT101', 'name' => 'Introduction to Computing', 'course_code' => 'BSIT', 'year_level' => '1', 'semester' => '1st', 'type' => 'LEC', 'lecture_units' => 3, 'laboratory_units' => 0],
            ['code' => 'IT102', 'name' => 'Computer Programming 1', 'course_code' => 'BSIT', 'year_level' => '1', 'semester' => '1st', 'type' => 'BOTH', 'lecture_units' => 2, 'laboratory_units' => 1],
            ['code' => 'IT103', 'name' => 'Web Systems and Technologies', 'course_code' => 'BSIT', 'year_level' => '1', 'semester' => '2nd', 'type' => 'BOTH', 'lecture_units' => 2, 'laboratory_units' => 1],
            ['code' => 'IT201', 'name' => 'Data Structures and Algorithms', 'course_code' => 'BSIT', 'year_level' => '2', 'semester' => '1st', 'type' => 'BOTH', 'lecture_units' => 2, 'laboratory_units' => 1],
            ['code' => 'CS101', 'name' => 'Discrete Structures', 'course_code' => 'BSCS', 'year_level' => '1', 'semester' => '1st', 'type' => 'LEC', 'lecture_units' => 3, 'laboratory_units' => 0],
            ['code' => 'CS102', 'name' => 'Computer Programming Fundamentals', 'course_code' => 'BSCS', 'year_level' => '1', 'semester' => '1st', 'type' => 'BOTH', 'lecture_units' => 2, 'laboratory_units' => 1],
            ['code' => 'ACT101', 'name' => 'Productivity Tools', 'course_code' => 'ACT', 'year_level' => '1', 'semester' => '1st', 'type' => 'LAB', 'lecture_units' => 0, 'laboratory_units' => 3],
            ['code' => 'HM101', 'name' => 'Introduction to Hospitality Management', 'course_code' => 'BSHM', 'year_level' => '1', 'semester' => '1st', 'type' => 'LEC', 'lecture_units' => 3, 'laboratory_units' => 0],
            ['code' => 'OM101', 'name' => 'Office Procedures and Administration', 'course_code' => 'BSOM', 'year_level' => '1', 'semester' => '1st', 'type' => 'LEC', 'lecture_units' => 3, 'laboratory_units' => 0],
            ['code' => 'ACC101', 'name' => 'Fundamentals of Accounting', 'course_code' => 'BSA', 'year_level' => '1', 'semester' => '1st', 'type' => 'LEC', 'lecture_units' => 3, 'laboratory_units' => 0],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['code' => $subject['code']],
                $subject + [
                    'description' => null,
                    'total_units' => $subject['lecture_units'] + $subject['laboratory_units'],
                    'is_active' => true,
                ]
            );
        }

        $scheduleRows = [
            ['subject' => 'IT101', 'day' => 'Monday', 'time' => '07:30', 'room' => 'Room 101'],
            ['subject' => 'IT102', 'day' => 'Monday', 'time' => '09:00', 'room' => 'Computer Lab 1'],
            ['subject' => 'IT103', 'day' => 'Tuesday', 'time' => '10:30', 'room' => 'Computer Lab 1'],
            ['subject' => 'IT201', 'day' => 'Wednesday', 'time' => '13:00', 'room' => 'Computer Lab 2'],
            ['subject' => 'CS101', 'day' => 'Tuesday', 'time' => '07:30', 'room' => 'Room 102'],
            ['subject' => 'CS102', 'day' => 'Tuesday', 'time' => '09:00', 'room' => 'Computer Lab 2'],
            ['subject' => 'ACT101', 'day' => 'Wednesday', 'time' => '09:00', 'room' => 'Computer Lab 1'],
            ['subject' => 'HM101', 'day' => 'Thursday', 'time' => '07:30', 'room' => 'Room 101'],
            ['subject' => 'OM101', 'day' => 'Thursday', 'time' => '09:00', 'room' => 'Room 102'],
            ['subject' => 'ACC101', 'day' => 'Friday', 'time' => '07:30', 'room' => 'Room 101'],
        ];

        foreach ($scheduleRows as $row) {
            $subject = Subject::where('code', $row['subject'])->first();
            $day = Day::where('name', $row['day'])->first();
            $timeSlot = TimeSlot::where('start_time', $row['time'])->first();
            $room = Room::where('name', $row['room'])->first();

            if (! $subject || ! $day || ! $timeSlot || ! $room) {
                continue;
            }

            SubjectSchedule::updateOrCreate(
                [
                    'subject_id' => $subject->id,
                    'day_id' => $day->id,
                    'time_slot_id' => $timeSlot->id,
                ],
                ['room_id' => $room->id]
            );
        }

        $enrollments = [
            ['student_number' => '2026-00001', 'first_name' => 'Miguel', 'middle_name' => 'A.', 'last_name' => 'Dela Cruz', 'course_code' => 'BSIT', 'course_name' => 'BS Information Technology', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'enrolled', 'subjects' => ['IT101', 'IT102']],
            ['student_number' => '2026-00002', 'first_name' => 'Sofia', 'middle_name' => 'B.', 'last_name' => 'Reyes', 'course_code' => 'BSIT', 'course_name' => 'BS Information Technology', 'year_level' => '1', 'semester' => '2nd', 'enrollment_status' => 'pending', 'subjects' => ['IT103']],
            ['student_number' => '2026-00003', 'first_name' => 'Carlo', 'middle_name' => 'C.', 'last_name' => 'Mendoza', 'course_code' => 'BSCS', 'course_name' => 'BS Computer Science', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'enrolled', 'subjects' => ['CS101', 'CS102']],
            ['student_number' => '2026-00004', 'first_name' => 'Bianca', 'middle_name' => 'D.', 'last_name' => 'Garcia', 'course_code' => 'ACT', 'course_name' => 'Associate in Computer Technology', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'pending', 'subjects' => ['ACT101']],
            ['student_number' => '2026-00005', 'first_name' => 'Jerome', 'middle_name' => 'E.', 'last_name' => 'Aquino', 'course_code' => 'BSHM', 'course_name' => 'BS Hospitality Management', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'enrolled', 'subjects' => ['HM101']],
            ['student_number' => '2026-00006', 'first_name' => 'Angelica', 'middle_name' => 'F.', 'last_name' => 'Torres', 'course_code' => 'BSOM', 'course_name' => 'BS Office Management', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'cancelled', 'subjects' => ['OM101']],
            ['student_number' => '2026-00007', 'first_name' => 'Nathan', 'middle_name' => 'G.', 'last_name' => 'Lim', 'course_code' => 'BSA', 'course_name' => 'BS Accountancy', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'dropped', 'subjects' => ['ACC101']],
            ['student_number' => '2026-00008', 'first_name' => 'Isabella', 'middle_name' => 'H.', 'last_name' => 'Navarro', 'course_code' => 'BSIT', 'course_name' => 'BS Information Technology', 'year_level' => '2', 'semester' => '1st', 'enrollment_status' => 'enrolled', 'subjects' => ['IT201']],
        ];

        foreach ($enrollments as $index => $row) {
            $head = DepartmentHead::where('course_code', $row['course_code'])->where('is_active', true)->first();
            $subjectCodes = $row['subjects'];
            unset($row['subjects']);

            $enrollment = Enrollment::updateOrCreate(
                ['student_number' => $row['student_number']],
                $row + [
                    'date_filed' => now()->subDays(8 - $index)->toDateString(),
                    'school_year' => '2026-2027',
                    'department_head_name' => $head?->name,
                    'cellphone' => '09' . str_pad((string) (170000000 + $index), 9, '0', STR_PAD_LEFT),
                    'email' => strtolower($row['first_name'] . '.' . $row['last_name'] . '@student.comteq.test'),
                    'last_school' => 'COMTEQ Senior High School',
                    'present_address' => 'Sample Street, Subic',
                    'barangay' => 'Wawandue',
                    'city' => 'Subic',
                    'province' => 'Zambales',
                    'date_of_birth' => now()->subYears(18 + ($index % 4))->toDateString(),
                    'age' => 18 + ($index % 4),
                    'place_of_birth' => 'Subic, Zambales',
                    'civil_status' => 'Single',
                    'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                    'religion' => 'Roman Catholic',
                    'credentials' => ['form_138', 'birth_certificate', 'good_moral'],
                ]
            );

            $syncData = Subject::whereIn('code', $subjectCodes)->get()->mapWithKeys(function (Subject $subject) {
                return [
                    $subject->id => [
                        'lecture_units' => $subject->lecture_units,
                        'laboratory_units' => $subject->laboratory_units,
                        'total_units' => $subject->total_units,
                    ],
                ];
            })->all();

            $enrollment->subjects()->sync($syncData);
        }
    }
}
