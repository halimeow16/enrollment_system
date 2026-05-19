<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Student Number</label>
        <input type="text" name="student_number" value="{{ old('student_number') }}"
               placeholder="e.g. 2026-XXXXX" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Date Filled <span class="text-red-500">*</span></label>
        <input type="date" name="date_filed" value="{{ old('date_filed', now()->format('Y-m-d')) }}"
               class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl" readonly>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">School Year <span class="text-red-500">*</span></label>
        <input type="text" name="school_year" value="{{ old('school_year', $academicYear ?? '2026-2027') }}" required readonly
               class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
    </div>
</div>
