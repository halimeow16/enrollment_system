<!-- Academic Program -->
<div class="mt-14">
    <h2 class="section-title border-b border-slate-200 pb-3 mb-6">ACADEMIC PROGRAM</h2>
    
    <!-- Course Selection Grid -->
    <div class="mb-8">
        <label class="block text-sm font-medium text-slate-600 mb-3">Choose Program</label>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="courseGrid"></div>
    </div>

    <input type="hidden" name="course_code" id="course_code" value="BSIT">
    <input type="hidden" name="course_name" id="course_name" value="BS Information Technology">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Year Level</label>
            <select name="year_level" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="" disabled selected hidden>Select Year Level</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Semester</label>
            <select name="semester" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="" disabled selected hidden>Select Semester</option>
                <option value="1st">1st Semester</option>
                <option value="2nd">2nd Semester</option>
                <option value="Summer">Summer</option>
            </select>
        </div>
    </div>
</div>