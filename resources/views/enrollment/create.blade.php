@extends('layouts.app')

@section('title', 'COMTEQ | Enrollment Form 2026-2027')

@section('content')
<div class="bg-slate-50 min-h-screen">
    @include('enrollment.partials.header')

    <div class="max-w-5xl mx-auto px-6 py-10">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-semibold text-slate-800">Student Enrollment Form</h1>
            <p class="text-slate-600 mt-2">Please fill out all required fields accurately.</p>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border p-10">
            <form id="enrollmentForm" action="{{ route('enrollment.store') }}" method="POST">
                @csrf

                @include('enrollment.partials.basic-info')
                @include('enrollment.partials.personal-info')
                @include('enrollment.partials.academic-program')
                @include('enrollment.partials.required-credentials')

                <div class="mt-12 flex justify-center gap-4">
                    <button type="button" onclick="generatePreview()" 
                            class="px-8 py-5 bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded-3xl">
                        Preview
                    </button>
                    <button type="submit"
                            class="px-12 py-5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-lg rounded-3xl flex items-center gap-3">
                        <i class="fa-solid fa-file-pdf"></i>
                        Submit & Generate PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('enrollment.partials.preview-modal')
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>

@php
    $subjectCatalog = $subjects->map(function ($subject) {
        return [
            'id' => $subject->id,
            'code' => $subject->code,
            'name' => $subject->name,
            'course_code' => $subject->course_code,
            'year_level' => $subject->year_level,
            'semester' => $subject->semester,
            'type' => $subject->type,
            'lecture_units' => (float) $subject->lecture_units,
            'laboratory_units' => (float) $subject->laboratory_units,
            'total_units' => (float) $subject->total_units,
            'schedules' => $subject->schedules->map(function ($schedule) {
                return [
                    'day_id' => $schedule->day_id,
                    'time_slot_id' => $schedule->time_slot_id,
                    'day' => $schedule->day->name,
                    'time' => $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . ' - ' . $schedule->timeSlot->end_time),
                    'room' => $schedule->room->name,
                ];
            })->values(),
        ];
    })->values();

    $departmentHeadMap = $departmentHeads->map(function ($head) {
        return [
            'name' => $head->name,
            'title' => $head->title,
        ];
    });

@endphp

<script>
    window.previewUrl = "{{ route('enrollment.preview') }}";
    window.subjectCatalog = @json($subjectCatalog);
    window.departmentHeads = @json($departmentHeadMap);
</script>

@vite(['resources/js/app.js'])
@endpush
