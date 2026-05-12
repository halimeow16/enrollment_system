@extends('layouts.app')

@section('title', 'COMTEQ | Enrollment Form 2026-2027')

@section('content')
<div class="bg-slate-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="COMTEQ Logo" class="h-20">
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold">Academic Year 2026-2027</p>
                <p class="text-xs text-emerald-600">Student Enrollment</p>
            </div>
        </div>
    </header>

    <div class="max-w-5xl mx-auto px-6 py-10">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-semibold text-slate-800">Student Enrollment Form</h1>
            <p class="text-slate-600 mt-2">Please fill out all required fields accurately.</p>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border p-10">
            <form id="enrollmentForm" action="{{ route('enrollment.store') }}" method="POST">
                @csrf

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Student Number <span class="text-red-500">*</span></label>
                        <input type="text" name="student_number" value="{{ old('student_number') }}" 
                               placeholder="e.g. 2026-XXXXX" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">Date Filled</label>
                        <input type="date" name="date_filed" value="{{ old('date_filed', now()->format('Y-m-d')) }}" 
                               class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-1">School Year</label>
                        <input type="text" name="school_year" value="2026-2027" 
                               class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="mt-12">
                    <h2 class="section-title border-b border-slate-200 pb-3 mb-6">PERSONAL INFORMATION</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">First Name <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Middle Name</label>
                            <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Cellphone Number <span class="text-red-500">*</span></label>
                            <input type="tel" name="cellphone" value="{{ old('cellphone') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Last School Attended</label>
                            <input type="text" name="last_school" value="{{ old('last_school') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                    </div>

                    <!-- More fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mt-8">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Present Address</label>
                            <input type="text" name="present_address" value="{{ old('present_address') }}" 
                                   placeholder="House No., Street, Subdivision" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Barangay</label>
                            <input type="text" name="barangay" value="{{ old('barangay') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">City / Town</label>
                            <input type="text" name="city" value="{{ old('city') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Province</label>
                            <input type="text" name="province" value="{{ old('province') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Age</label>
                            <input type="number" name="age" value="{{ old('age') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Place of Birth</label>
                            <input type="text" name="place_of_birth" value="{{ old('place_of_birth') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Civil Status</label>
                            <select name="civil_status" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                                <option value="">Select</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Gender</label>
                            <select name="gender" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Religion</label>
                            <input type="text" name="religion" value="{{ old('religion') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>

                        <div class="md:col-span-2 border-t border-slate-100 my-2"></div>

                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Father's Section -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Father's Name</label>
                            <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Address</label>
                            <input type="text" name="father_address" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Cellphone Number</label>
                            <input type="text" name="father_cpNumber" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>

                        <!-- Spacer for clarity or a horizontal rule could go here -->
                        <div class="md:col-span-2 border-t border-slate-100 my-2"></div>

                        <!-- Mother's Section -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-600 mb-1">Mother's Maiden Name</label>
                            <input type="text" name="mother_name" value="{{ old('mother_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Address</label>
                            <input type="text" name="mother_address" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Cellphone Number</label>
                            <input type="text" name="mother_cpNumber" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                    </div>
                    </div>
                </div>

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
                            <select name="year_level" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                                <option value="1">1st Year</option>
                                <option value="2" selected>2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Semester</label>
                            <select name="semester" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                                <option value="1st">1st Semester</option>
                                <option value="2nd" selected>2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                    </div>
                </div>

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

<!-- Preview Modal -->
<div id="previewModal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
    <div class="bg-white max-w-4xl w-full mx-4 rounded-3xl max-h-[92vh] overflow-auto">
        <div class="p-6 border-b flex justify-between sticky top-0 bg-white">
            <h3 class="font-semibold text-xl">Enrollment Form Preview</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>
        </div>
        <div id="formPreview" class="p-10"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
<script>
    const courses = [
        {code: "BSIT", name: "BS Information Technology"},
        {code: "BSCS", name: "BS Computer Science"},
        {code: "ACT",  name: "Associate in Computer Technology"},
        {code: "BSHM", name: "BS Hospitality Management"},
        {code: "BSOM", name: "BS Office Management"},
        {code: "BSA",  name: "BS Accountancy"}
    ];

    let selectedCourseCode = "BSIT";
    let selectedCourseName = "BS Information Technology";

    function populateCourses() {
        const container = document.getElementById('courseGrid');
        container.innerHTML = '';

        courses.forEach(course => {
            const div = document.createElement('div');
            div.className = `p-5 border-2 border-transparent hover:border-blue-200 rounded-3xl cursor-pointer transition-all course-card text-center ${course.code === selectedCourseCode ? 'border-blue-600 bg-blue-50' : ''}`;
            div.innerHTML = `
                <div class="font-bold text-xl">${course.code}</div>
                <div class="text-xs text-slate-500 mt-1">${course.name}</div>
            `;
            div.onclick = () => selectCourse(div, course.code, course.name);
            container.appendChild(div);
        });
    }

    function selectCourse(el, code, name) {
        document.querySelectorAll('.course-card').forEach(card => {
            card.classList.remove('border-blue-600', 'bg-blue-50');
        });
        el.classList.add('border-blue-600', 'bg-blue-50');

        selectedCourseCode = code;
        selectedCourseName = name;

        // Update hidden inputs
        document.getElementById('course_code').value = code;
        document.getElementById('course_name').value = name;
    }

    // Preview function (you can expand this)
    function generatePreview() {
        const data = {
            studentNumber: document.querySelector('[name="student_number"]').value || "2026-XXXXX",
            firstName: document.querySelector('[name="first_name"]').value,
            middleName: document.querySelector('[name="middle_name"]').value,
            lastName: document.querySelector('[name="last_name"]').value,
            courseCode: selectedCourseCode,
            courseName: selectedCourseName,
            // ... add more fields as needed
        };

        // Simple preview (customize as you like)
        const previewHTML = `
            <div class="max-w-3xl mx-auto border-2 border-slate-300 p-8 rounded-xl">
                <h2 class="text-2xl font-bold text-center text-[#1e40af] mb-6">COMTEQ Computer & Business College</h2>
                <p class="text-center mb-8">Academic Year 2026-2027</p>
                
                <h3 class="font-semibold mb-4">Selected Program</h3>
                <p class="text-lg"><strong>${data.courseCode} — ${data.courseName}</strong></p>
                
                <div class="mt-8 text-center text-sm text-slate-500">
                    Full preview coming soon...
                </div>
            </div>
        `;

        document.getElementById('formPreview').innerHTML = previewHTML;
        document.getElementById('previewModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('previewModal').classList.add('hidden');
    }

    // Initialize
    window.onload = populateCourses;
</script>
@endpush