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

    document.getElementById('course_code').value = code;
    document.getElementById('course_name').value = name;
}

function generatePreview() {
    // You can expand this later with full data
    const data = {
        studentNumber: document.querySelector('[name="student_number"]').value || "2026-XXXXX",
        firstName: document.querySelector('[name="first_name"]').value,
        middleName: document.querySelector('[name="middle_name"]').value,
        lastName: document.querySelector('[name="last_name"]').value,
        courseCode: selectedCourseCode,
        courseName: selectedCourseName,
    };

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