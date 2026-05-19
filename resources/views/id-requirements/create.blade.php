@extends('layouts.app')

@section('title', 'COMTEQ | Submit ID Requirements')

@section('content')
<div class="min-h-screen bg-slate-50">
    @include('enrollment.partials.header')

    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="mb-10 text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-blue-700">Student ID Requirements</p>
            <h1 class="mt-3 text-4xl font-semibold text-slate-800">Submit ID Requirements</h1>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                Enter the same details used during enrollment. Your upload will only be accepted when the information matches one enrolled student record.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-8 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-3xl border bg-white p-8 shadow-sm md:p-10">
            @if($errors->any())
                <div class="mb-8 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    <p class="font-bold">Submission failed</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('id-requirements.store') }}" method="POST" enctype="multipart/form-data" class="space-y-10">
                @csrf

                <section>
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                            <i class="fa-solid fa-user-check"></i>
                        </span>
                        <div>
                            <h2 class="section-title">Verify Enrollment</h2>
                            <p class="text-sm text-slate-500">Use your enrollment name, birthday, and email or cellphone.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input name="first_name" value="{{ old('first_name') }}" required class="form-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3.5">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input name="last_name" value="{{ old('last_name') }}" required class="form-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3.5">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Birthdate <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required class="form-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3.5">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Email or Cellphone <span class="text-red-500">*</span>
                            </label>
                            <input name="contact" value="{{ old('contact') }}" required placeholder="Email or cellphone used during enrollment" class="form-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3.5">
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                            <i class="fa-solid fa-phone-volume"></i>
                        </span>
                        <div>
                            <h2 class="section-title">Emergency Contact</h2>
                            <p class="text-sm text-slate-500">Provide someone the school can contact if needed.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" required placeholder="Full name" class="form-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3.5">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Relationship <span class="text-red-500">*</span>
                            </label>
                            <input name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}" required placeholder="Parent, guardian, sibling..." class="form-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3.5">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Contact Number <span class="text-red-500">*</span>
                            </label>
                            <input name="emergency_contact_number" value="{{ old('emergency_contact_number') }}" required placeholder="Emergency contact number" class="form-input w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3.5">
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                            <i class="fa-solid fa-id-card"></i>
                        </span>
                        <div>
                            <h2 class="section-title">Optional Uploads</h2>
                            <p class="text-sm text-slate-500">You may skip these if the admin will capture your photo at school.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-5">
                            <span class="block text-sm font-semibold text-slate-700">
                                Student Photo
                            </span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Optional. Use a clear front-facing photo if available.</span>
                            <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="mt-4 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-blue-600 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                        </label>

                        <label class="block rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-5">
                            <span class="block text-sm font-semibold text-slate-700">Signature Image</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Optional. Upload only if you already have a digital signature.</span>
                            <input type="file" name="signature" accept="image/png,image/jpeg,image/webp" class="mt-4 block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-700 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                        </label>
                    </div>
                </section>

                <div class="flex justify-center">
                    <button type="submit" class="inline-flex items-center gap-3 rounded-3xl bg-blue-600 px-10 py-4 text-base font-semibold text-white transition hover:bg-blue-700">
                        <i class="fa-solid fa-upload"></i>
                        Submit ID Requirements
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
@endsection
