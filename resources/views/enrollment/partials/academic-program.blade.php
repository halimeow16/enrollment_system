<div class="mt-14">
    <h2 class="section-title border-b border-slate-200 pb-3 mb-6">ACADEMIC PROGRAM</h2>
    
    <div class="mb-8">
        <label class="block text-sm font-medium text-slate-600 mb-3">Choose Program <span class="text-red-500">*</span></label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="courseGrid"></div>
    </div>

    <input type="hidden" name="course_code" id="course_code" value="BSIT">
    <input type="hidden" name="course_name" id="course_name" value="BS Information Technology">

    <div class="mb-8">
        <label class="block text-sm font-medium text-slate-600 mb-3">Student Type <span class="text-red-500">*</span></label>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <label class="student-type-card flex cursor-pointer items-start gap-3 rounded-3xl border-2 border-blue-600 bg-blue-50 p-5 transition" data-student-type-card="new">
                <input type="radio" name="student_type" value="new" checked required class="mt-1 student-type-radio">
                <span>
                    <span class="block text-sm font-bold text-slate-900">New</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Load subjects from the selected program, year level, and semester.</span>
                </span>
            </label>
            <label class="student-type-card flex cursor-pointer items-start gap-3 rounded-3xl border-2 border-transparent bg-slate-50 p-5 transition hover:border-blue-200" data-student-type-card="old">
                <input type="radio" name="student_type" value="old" required class="mt-1 student-type-radio">
                <span>
                    <span class="block text-sm font-bold text-slate-900">Old</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Load subjects from the selected program, year level, and semester.</span>
                </span>
            </label>
            <label class="student-type-card flex cursor-pointer items-start gap-3 rounded-3xl border-2 border-transparent bg-slate-50 p-5 transition hover:border-blue-200" data-student-type-card="transferee">
                <input type="radio" name="student_type" value="transferee" required class="mt-1 student-type-radio">
                <span>
                    <span class="block text-sm font-bold text-slate-900">Transferee</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Open all available subjects and use search to choose manually.</span>
                </span>
            </label>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Year Level <span class="text-red-500">*</span></label>
            <select name="year_level" id="year_level" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="" disabled selected hidden>Select Year Level</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Semester <span class="text-red-500">*</span></label>
            <select name="semester" id="semester" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="" disabled selected hidden>Select Semester</option>
                <option value="1st">1st Semester</option>
                <option value="2nd">2nd Semester</option>
                <option value="Summer">Summer</option>
            </select>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Department Head</label>
            <input type="text" name="department_head_name" id="department_head_name" readonly
                   class="form-input w-full px-5 py-3.5 bg-slate-100 border border-slate-200 rounded-2xl text-slate-600"
                   placeholder="Auto-filled after choosing a program">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Total Units</label>
            <input type="text" id="total_units" readonly value="0"
                   class="form-input w-full px-5 py-3.5 bg-slate-100 border border-slate-200 rounded-2xl text-slate-600">
        </div>
    </div>

    <div class="mt-8">
        <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-slate-600">Subjects</label>
        </div>
        <div id="openSubjectSearchWrap" class="mb-3 hidden">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input type="search"
                       id="subjectSearch"
                       placeholder="Search subject code, name, course, year, semester..."
                       class="form-input w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-11 pr-4 text-sm">
            </div>
        </div>
        <div id="subjectList" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Choose a program, year level, and semester to load available subjects.</p>
        </div>
    </div>
</div>
