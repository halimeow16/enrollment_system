<header class="bg-white border-b">
    <div class="max-w-5xl mx-auto px-6 py-5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="COMTEQ Logo" class="h-20">
        </div>
        <div class="text-right">
            <p class="text-sm font-semibold">Academic Year {{ $academicYear ?? '2026-2027' }}</p>
            <p class="text-xs text-emerald-600">Student Enrollment</p>
        </div>
    </div>
</header>
