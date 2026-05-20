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
use Illuminate\Support\Facades\DB;

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
            ['start_time' => '16:00', 'end_time' => '17:30', 'label' => '4:00 PM - 5:30 PM'],
            ['start_time' => '17:30', 'end_time' => '19:00', 'label' => '5:30 PM - 7:00 PM'],
        ] as $slot) {
            TimeSlot::updateOrCreate(
                ['start_time' => $slot['start_time'], 'end_time' => $slot['end_time']],
                $slot + ['is_active' => true]
            );
        }

        foreach (['Room 101', 'Room 102', 'Room 103', 'Room 104', 'Computer Lab 1', 'Computer Lab 2', 'Computer Lab 3'] as $room) {
            Room::updateOrCreate(['name' => $room], ['is_active' => true]);
        }

        $departmentHeads = [
            ['course_code' => 'BSIT', 'name' => 'Noel R. Marcelino', 'title' => 'BSIT Department Head'],
            ['course_code' => 'BSCS', 'name' => 'Noel R. Marcelino', 'title' => 'BSCS Department Head'],
            ['course_code' => 'ACT', 'name' => 'Noel R. Marcelino', 'title' => 'ACT Program Head'],
            ['course_code' => 'BSBA', 'name' => 'Sample Dept. Name', 'title' => 'BSBA Department Head'],
            ['course_code' => 'BSOM', 'name' => 'Sample Dept. Name', 'title' => 'BSOM Department Head'],
            ['course_code' => 'BSA', 'name' => 'Sample Dept. Name', 'title' => 'BSA Department Head'],
        ];

        foreach ($departmentHeads as $head) {
            DepartmentHead::updateOrCreate(
                ['course_code' => $head['course_code'], 'is_active' => true],
                $head + ['is_active' => true]
            );
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('schedule_conflicts')->truncate();
        DB::table('enrollment_subjects')->truncate();
        DB::table('subject_schedules')->truncate();
        DB::table('subjects')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $subjects = $this->curriculumSubjects();

        foreach ($subjects as $subject) {
            Subject::create($subject + [
                'total_units' => $subject['lecture_units'] + $subject['laboratory_units'],
                'is_active' => true,
            ]);
        }

        $this->seedSubjectSchedules();

        $enrollments = [
            ['student_number' => '2026-00001', 'first_name' => 'Miguel', 'middle_name' => 'A.', 'last_name' => 'Dela Cruz', 'course_code' => 'BSIT', 'course_name' => 'BS Information Technology', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'enrolled', 'subjects' => ['GE101', 'CC101', 'CC102']],
            ['student_number' => '2026-00002', 'first_name' => 'Sofia', 'middle_name' => 'B.', 'last_name' => 'Reyes', 'course_code' => 'BSIT', 'course_name' => 'BS Information Technology', 'year_level' => '1', 'semester' => '2nd', 'enrollment_status' => 'pending', 'subjects' => ['GE103', 'CC104', 'HCI101']],
            ['student_number' => '2026-00003', 'first_name' => 'Carlo', 'middle_name' => 'C.', 'last_name' => 'Mendoza', 'course_code' => 'BSCS', 'course_name' => 'BS Computer Science', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'enrolled', 'subjects' => ['GE101', 'CC101', 'CC102']],
            ['student_number' => '2026-00004', 'first_name' => 'Bianca', 'middle_name' => 'D.', 'last_name' => 'Garcia', 'course_code' => 'ACT', 'course_name' => 'Associate in Computer Technology', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'pending', 'subjects' => ['GE101', 'SPI101', 'CC101']],
            ['student_number' => '2026-00005', 'first_name' => 'Jerome', 'middle_name' => 'E.', 'last_name' => 'Aquino', 'course_code' => 'BSBA', 'course_name' => 'BS Business Administration', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'enrolled', 'subjects' => ['HM101']],
            ['student_number' => '2026-00006', 'first_name' => 'Angelica', 'middle_name' => 'F.', 'last_name' => 'Torres', 'course_code' => 'BSOM', 'course_name' => 'BS Operations Management', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'cancelled', 'subjects' => ['OM101']],
            ['student_number' => '2026-00007', 'first_name' => 'Nathan', 'middle_name' => 'G.', 'last_name' => 'Lim', 'course_code' => 'BSA', 'course_name' => 'BS Accountancy', 'year_level' => '1', 'semester' => '1st', 'enrollment_status' => 'cancelled', 'subjects' => ['ACC101']],
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

            $syncData = Subject::where('course_code', $row['course_code'])
                ->whereIn('code', $subjectCodes)
                ->get()
                ->mapWithKeys(function (Subject $subject) {
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

        $this->call(PrebuiltTemplateSeeder::class);
    }

    private function curriculumSubjects(): array
    {
        $rows = [];

        $add = function (string $course, string $year, string $semester, string $code, string $name, float $lec, float $lab, float $units) use (&$rows): void {
            $rows[] = [
                'course_code' => $course,
                'year_level' => $year,
                'semester' => $semester,
                'code' => $code,
                'name' => $name,
                'type' => $lec > 0 && $lab > 0 ? 'BOTH' : ($lab > 0 ? 'LAB' : 'LEC'),
                'lecture_units' => $lec,
                'laboratory_units' => $lab,
                'total_units' => $units,
            ];
        };

        foreach (['BSIT', 'BSCS'] as $course) {
            $add($course, '1', '1st', 'GE101', 'Understanding the Self', 3, 0, 3);
            $add($course, '1', '1st', 'GE102', 'Readings in Philippine History with Peoples Studies/Education', 3, 0, 3);
            $add($course, '1', '1st', 'CC101', 'Introduction to Computing', 3, 1, 3);
            $add($course, '1', '1st', 'CC102', 'Computer Programming 1 (Fundamentals of Programming)', 3, 1, 3);
            $add($course, '1', '1st', 'CC103', 'Data Structures and Algorithms', 3, 1, 3);
            $add($course, '1', '1st', 'PATHFIT 1', 'Movement Competency Training', 2, 0, 2);

            $add($course, '1', '2nd', 'GE103', 'The Contemporary World with Peace Studies/Education', 3, 0, 3);
            $add($course, '1', '2nd', 'GE104', 'Mathematics in the Modern World', 3, 0, 3);
            $add($course, '1', '2nd', 'GE105', 'Purposive Communication', 3, 0, 3);
            $add($course, '1', '2nd', 'GE106', 'Art Appreciation', 3, 0, 3);
            $add($course, '1', '2nd', 'CC104', 'Computer Programming 2 (Intermediate Programming)', 2, 1, 3);
            $add($course, '1', '2nd', 'HCI101', 'Human Computer Interaction', 2, 1, 3);
            $add($course, '1', '2nd', 'PATHFIT 2', 'Exercise-based Fitness Activities', 2, 0, 2);

            $add($course, '2', '1st', 'GE107', 'Science, Technology and Society', 3, 0, 3);
            $add($course, '2', '1st', 'GE108', 'Ethics', 3, 0, 3);
            $add($course, '2', '1st', 'GE109', 'Rizal Life and Works', 3, 0, 3);
            $add($course, '2', '1st', 'FIL1', 'Wika at Kultura', 3, 0, 3);
            $add($course, '2', '1st', 'CC105', 'Fundamentals of Information Management and Database Systems', 2, 1, 3);
            $add($course, '2', '1st', 'NET101', 'Networking Fundamentals', 2, 1, 3);
            $add($course, '2', '1st', 'PATHFIT 3', 'Dance, Outdoor and Adventures Activities', 2, 0, 2);
            $add($course, '2', '1st', 'NSTP1', 'National Service Training Program 1', 3, 0, 3);

            $add($course, '2', '2nd', 'FIL2', 'Malikhaing Pagsulat', 3, 0, 3);
            $add($course, '2', '2nd', 'PHIL101', 'Philippine Literature', 3, 0, 3);
            $add($course, '2', '2nd', 'ITM101', 'Probability and Statistics', 3, 0, 3);
            $add($course, '2', '2nd', 'CG101', 'Calculus', 3, 0, 3);
            $add($course, '2', '2nd', 'CC106', 'Applications Development and Emerging Technologies', 2, 1, 3);
            $add($course, '2', '2nd', 'NET102', 'Network Management and Design', 2, 1, 3);
            $add($course, '2', '2nd', 'PATHFIT 4', 'Sports', 2, 0, 2);
            $add($course, '2', '2nd', 'NSTP2', 'National Service Training Program 2', 3, 0, 3);
        }

        foreach ([
            ['PT101', 'Network Servers, Virtualization and Cloud Computing', 2, 1, 3],
            ['IM101', 'Advance Database Systems', 2, 1, 3],
            ['PF101', 'Object Oriented and Event Driven Programming', 2, 1, 3],
            ['WMS101', 'Web and Mobile System Technologies', 2, 1, 3],
            ['ITM103', 'Discrete Mathematics', 3, 0, 3],
            ['IAS101', 'Information Assurance and Security 1', 3, 0, 3],
            ['SIA101', 'Fundamentals of System Integration and Architecture', 2, 1, 2],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('BSIT', '3', '1st', $code, $name, $lec, $lab, $units);
        }

        foreach ([
            ['IAS102', 'Information Assurance and Security 2', 3, 0, 3],
            ['IPT101', 'Integrative Programming and Technologies 1', 2, 1, 3],
            ['WMS102', 'Advance Web and Mobile System Technologies', 2, 1, 3],
            ['SIA102', 'Advance System Integration and Architecture', 2, 1, 3],
            ['SDM101', 'Social Issues and Professional Practice', 3, 0, 3],
            ['ITE101', 'IT Elective 1', 2, 1, 3],
            ['ITE102', 'IT Elective 2', 2, 1, 3],
            ['CAPS101', 'Capstone Project 1', 3, 0, 3],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('BSIT', '3', '2nd', $code, $name, $lec, $lab, $units);
        }

        foreach ([
            ['SDM102', 'System Testing and Quality Assurance', 2, 1, 3],
            ['SAM101', 'Systems Administration and Maintenance', 2, 1, 3],
            ['ITE103', 'IT Elective 3', 2, 1, 3],
            ['ITE104', 'IT Elective 4', 2, 1, 3],
            ['SPI101', 'Project Planning and Management', 3, 0, 3],
            ['CAPS102', 'Capstone Project 2', 3, 0, 3],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('BSIT', '4', '1st', $code, $name, $lec, $lab, $units);
        }
        $add('BSIT', '4', '2nd', 'PRAACT101', 'Practicum', 6, 0, 6);

        foreach ([
            ['CSELEC1', 'CS Elective 1', 2, 1, 3],
            ['IM101', 'Advance Database Systems', 2, 1, 3],
            ['PF101', 'Object Oriented and Event Driven Programming', 2, 1, 3],
            ['DAA101', 'Design and Analysis of Algorithm', 3, 0, 3],
            ['ITM103', 'Discrete Mathematics', 3, 0, 3],
            ['IAS101', 'Information Assurance and Security 1', 3, 0, 3],
            ['SE101', 'Software Engineering 1', 2, 1, 3],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('BSCS', '3', '1st', $code, $name, $lec, $lab, $units);
        }

        foreach ([
            ['CSELEC2', 'CS Elective 2', 2, 1, 3],
            ['WP101', 'Web Programming 1', 2, 1, 3],
            ['PL101', 'Programming Language', 2, 1, 3],
            ['SE102', 'Software Engineering 2', 2, 1, 3],
            ['CA101', 'Computer Architecture and Organization', 2, 1, 3],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('BSCS', '3', '2nd', $code, $name, $lec, $lab, $units);
        }

        foreach ([
            ['CT101', 'Automata Theory and Formal Language', 2, 1, 3],
            ['CSELEC3', 'CS Elective 3', 2, 1, 3],
            ['THS101', 'Thesis Writing 1', 3, 0, 3],
            ['OS101', 'Operating System', 2, 1, 3],
            ['SP101', 'Social Issues and Professional Practice', 3, 0, 3],
            ['PRAC160', 'Practicum', 3, 0, 3],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('BSCS', '4', '1st', $code, $name, $lec, $lab, $units);
        }
        $add('BSCS', '4', '2nd', 'THS102', 'Thesis Writing 2', 3, 0, 3);
        $add('BSCS', '4', '2nd', 'SEM101', 'Seminar on Advanced Topics', 3, 0, 3);

        foreach ([
            ['GE101', 'Understanding the Self', 3, 0, 3],
            ['SPI101', 'Social Issues and Professional Practice', 3, 0, 3],
            ['CC101', 'Introduction to Computing', 3, 1, 3],
            ['CC102', 'Computer Programming 1 (Fundamentals of Programming)', 3, 1, 3],
            ['CC103', 'Data Structures and Algorithms', 3, 1, 3],
            ['PATHFIT 1', 'Movement Competency Training or MCT', 2, 0, 2],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('ACT', '1', '1st', $code, $name, $lec, $lab, $units);
        }

        foreach ([
            ['GE103', 'The Contemporary World with Peace Studies/Education', 3, 0, 3],
            ['GE104', 'Mathematics in the Modern World', 3, 0, 3],
            ['GE105', 'Purposive Communication', 3, 0, 3],
            ['WDD101', 'Web and Design Development', 2, 1, 3],
            ['ITE101', 'Elective 1', 3, 1, 3],
            ['CC104', 'Computer Programming 2 (Intermediate Programming)', 2, 1, 3],
            ['HCI101', 'Human Computer Interaction', 2, 1, 3],
            ['PE2', 'Rhythmic Activities', 2, 0, 2],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('ACT', '1', '2nd', $code, $name, $lec, $lab, $units);
        }

        foreach ([
            ['ITE102', 'Elective 2', 2, 1, 3],
            ['ITE103', 'Elective 3', 2, 1, 3],
            ['GE109', 'Rizal Life and Works', 3, 0, 3],
            ['WMST101', 'Web and Mobile Systems Technologies', 2, 1, 3],
            ['CC105', 'Fundamentals of Information Management and Database Systems', 2, 1, 3],
            ['PATHFIT 3', 'Dance, Outdoor and Adventures Activities', 2, 0, 2],
            ['SDM101', 'Project Planning and Management', 3, 0, 3],
            ['NSTP1', 'National Service Training Program 1', 3, 0, 3],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('ACT', '2', '1st', $code, $name, $lec, $lab, $units);
        }

        foreach ([
            ['WMST102', 'Advance Mobile and System Technologies', 2, 1, 3],
            ['SAD', 'System Analysis and Design', 3, 0, 3],
            ['PATHFIT 4', 'Team Sports', 2, 0, 2],
            ['PRACT101', 'Practicum', 6, 0, 6],
            ['NSTP2', 'National Service Training Program 2', 3, 0, 3],
        ] as [$code, $name, $lec, $lab, $units]) {
            $add('ACT', '2', '2nd', $code, $name, $lec, $lab, $units);
        }

        return $rows;
    }

    private function seedSubjectSchedules(): void
    {
        $days = Day::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()->values();
        $timeSlots = TimeSlot::where('is_active', true)->orderBy('start_time')->get()->values();
        $lectureRooms = Room::where('is_active', true)
            ->where('name', 'not like', '%Lab%')
            ->orderBy('name')
            ->get()
            ->values();
        $labRooms = Room::where('is_active', true)
            ->where('name', 'like', '%Lab%')
            ->orderBy('name')
            ->get()
            ->values();

        $lectureIndex = 0;
        $labIndex = 0;

        Subject::orderBy('course_code')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('code')
            ->get()
            ->each(function (Subject $subject) use ($days, $timeSlots, $lectureRooms, $labRooms, &$lectureIndex, &$labIndex): void {
                $usesLab = in_array($subject->type, ['LAB', 'BOTH'], true);
                $rooms = $usesLab && $labRooms->isNotEmpty() ? $labRooms : $lectureRooms;
                $index = $usesLab ? $labIndex++ : $lectureIndex++;

                $room = $rooms[$index % $rooms->count()];
                $slotIndex = intdiv($index, $rooms->count());
                $timeSlot = $timeSlots[$slotIndex % $timeSlots->count()];
                $day = $days[intdiv($slotIndex, $timeSlots->count()) % $days->count()];

                SubjectSchedule::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'day_id' => $day->id,
                        'time_slot_id' => $timeSlot->id,
                    ],
                    ['room_id' => $room->id]
                );
            });
    }
}
