@extends('layouts.app')

@section('title', 'COMTEQ | Enrollment Form')

@section('content')
<div class="bg-slate-50 min-h-screen">
    @include('enrollment.partials.header')

    @error('layout')
        <div id="layoutErrorModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-6">
            <div class="relative w-full max-w-[380px] rounded-2xl bg-white px-7 pb-7 pt-6 text-center shadow-xl shadow-slate-950/15">
                <button type="button"
                        onclick="document.getElementById('layoutErrorModal')?.classList.add('hidden')"
                        class="absolute right-4 top-4 text-sm text-slate-900 transition hover:text-slate-500">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="mx-auto mt-1 flex h-11 w-11 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <i class="fa-solid fa-triangle-exclamation text-base"></i>
                </div>
                <h2 class="mt-4 text-base font-extrabold text-slate-950">Layout Unavailable</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ $message }}</p>
                <button type="button"
                        onclick="document.getElementById('layoutErrorModal')?.classList.add('hidden')"
                        class="mt-6 w-full rounded-lg bg-black px-5 py-3.5 text-sm font-bold text-white transition hover:bg-slate-800">
                    Got it
                </button>
            </div>
        </div>
    @enderror

    <div class="max-w-5xl mx-auto px-6 py-10">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-semibold text-slate-800">Student Enrollment Form</h1>
            <p class="text-slate-600 mt-2">Please fill out all required fields accurately.</p>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border p-10">
            @php
                $inlineErrors = collect($errors->getMessages())->except('layout');
            @endphp

            @if($inlineErrors->isNotEmpty())
                <div class="mb-8 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    <p class="font-bold">Submission failed</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($inlineErrors as $fieldErrors)
                            @foreach($fieldErrors as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="enrollmentForm" action="{{ route('enrollment.store') }}" method="POST">
                @csrf
                <input type="hidden" name="replace_existing" id="replaceExistingEnrollment" value="0">

                @include('enrollment.partials.basic-info')
                @include('enrollment.partials.personal-info')

                @if($customEnrollmentFields->isNotEmpty())
                    <div class="mt-14">
                        <h2 class="section-title border-b border-slate-200 pb-3 mb-6">ADDITIONAL INFORMATION</h2>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            @foreach($customEnrollmentFields as $field)
                                <div>
                                    <label class="block text-sm font-medium text-slate-600 mb-1">
                                        {{ $field->label }}
                                        @if($field->is_required)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <input type="{{ $field->input_type === 'number' ? 'number' : ($field->input_type === 'date' ? 'date' : 'text') }}"
                                           name="custom_fields[{{ $field->key }}]"
                                           value="{{ old('custom_fields.' . $field->key) }}"
                                           @required($field->is_required)
                                           class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @include('enrollment.partials.academic-program')
                @include('enrollment.partials.required-credentials')

                <div class="mt-10 flex flex-col justify-center gap-3 sm:mt-12 sm:flex-row sm:gap-4">
                    <button type="button" onclick="generatePreview()" 
                            class="rounded-2xl bg-slate-600 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700 sm:rounded-3xl sm:px-8 sm:py-5 sm:text-base">
                        Preview
                    </button>
                    <button type="submit"
                            class="flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 sm:gap-3 sm:rounded-3xl sm:px-12 sm:py-5 sm:text-lg">
                        <i class="fa-solid fa-file-pdf"></i>
                        Submit & Generate PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="duplicateEnrollmentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-6">
    <div class="relative w-full max-w-[430px] rounded-[24px] bg-white px-7 pb-7 pt-6 text-center shadow-2xl shadow-slate-950/20">
        <button type="button"
                id="cancelDuplicateEnrollmentTop"
                class="absolute right-4 top-4 grid h-8 w-8 place-items-center rounded-full text-sm text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="mx-auto mt-1 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600">
            <i class="fa-solid fa-rotate text-base"></i>
        </div>
        <h2 class="mt-4 text-lg font-extrabold text-slate-950">Enrollment Already Submitted</h2>
        <p id="duplicateEnrollmentMessage" class="mt-2 text-sm leading-6 text-slate-500">
            You already submitted an enrollment for this school year. Continuing will replace the previous submission with the details on this form.
        </p>

        <div class="mt-7 grid grid-cols-2 gap-3">
            <button type="button"
                    id="cancelDuplicateEnrollment"
                    class="rounded-xl border border-slate-200 bg-white px-5 py-3.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                Cancel
            </button>
            <button type="button"
                    id="confirmDuplicateEnrollment"
                    class="rounded-xl bg-black px-5 py-3.5 text-sm font-bold text-white transition hover:bg-slate-800">
                Submit
            </button>
        </div>
    </div>
</div>

@include('enrollment.partials.preview-modal')
@endsection

@push('scripts')
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
            'lecture_units' => (int) $subject->lecture_units,
            'laboratory_units' => (int) $subject->laboratory_units,
            'total_units' => (int) $subject->total_units,
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
    window.checkExistingEnrollmentUrl = "{{ route('enrollment.check-existing') }}";
    window.pendingDuplicateEnrollment = @json(session('duplicate_enrollment'));
    window.addressDataUrls = {
        provinces: "{{ route('address-data.provinces') }}",
        cities: "{{ url('/address-data/provinces') }}",
        barangays: "{{ url('/address-data/cities') }}",
    };
    window.subjectCatalog = @json($subjectCatalog);
    window.departmentHeads = @json($departmentHeadMap);
</script>

@vite(['resources/js/app.js'])
@endpush
