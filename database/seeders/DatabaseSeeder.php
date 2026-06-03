<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AppSetting;
use App\Models\Day;
use App\Models\DepartmentHead;
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

        AppSetting::setValue('academic_year', '2026-2027');

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $index => $day) {
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

        $this->call(PrebuiltTemplateSeeder::class);
    }
}
