const courses = [
    {code: "BSIT", name: "BS Information Technology"},
    {code: "BSCS", name: "BS Computer Science"},
    {code: "ACT",  name: "Associate in Computer Technology"},
    {code: "BSBA", name: "BS Business Administration"},
    {code: "BSOM", name: "BS Operations Management"},
    {code: "BSA",  name: "BS Accountancy"}
];

let selectedCourseCode = "BSIT";
let selectedCourseName = "BS Information Technology";

function populateCourses() {
    const container = document.getElementById('courseGrid');
    if (!container) return;

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

    const courseCode = document.getElementById('course_code');
    const courseName = document.getElementById('course_name');

    if (courseCode) courseCode.value = code;
    if (courseName) courseName.value = name;

    updateDepartmentHead();
    renderSubjectList();
}

function generatePreview() {
    const form = document.getElementById('enrollmentForm');
    if (!form) return;

    const formData = new FormData(form);

    document.getElementById('formPreview').innerHTML = `
        <div class="text-center py-20">
            <div class="animate-spin h-8 w-8 mx-auto border-4 border-blue-600 border-t-transparent rounded-full"></div>
            <p class="mt-4 text-slate-600">Generating PDF Preview...</p>
        </div>
    `;
    document.getElementById('previewModal').classList.remove('hidden');

    fetch(window.previewUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json, application/pdf',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json()
                .catch(() => ({}))
                .then(data => {
                    const error = new Error(data.message || 'Enrollment form preview is unavailable.');
                    error.previewUnavailable = true;
                    throw error;
                });
        }
        return response.blob();
    })
    .then(blob => {
        const url = URL.createObjectURL(blob);
        const previewHTML = `
            <iframe src="${url}" width="100%" height="820px" 
                    style="border: none; border-radius: 12px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
            </iframe>
        `;
        document.getElementById('formPreview').innerHTML = previewHTML;
    })
    .catch(error => {
        console.error('Preview Error:', error);
        if (error.previewUnavailable) {
            document.getElementById('formPreview').innerHTML = `
                <div class="text-center py-16 px-6">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <i class="fa-solid fa-file-circle-xmark text-xl"></i>
                    </div>
                    <p class="mt-4 text-lg font-bold text-slate-800">Preview unavailable</p>
                    <p class="mt-2 text-sm text-slate-500">No current enrollment form layout is available.</p>
                </div>
            `;
            return;
        }

        document.getElementById('formPreview').innerHTML = `
            <div class="text-red-600 text-center py-10 px-6">
                <p class="font-medium">Failed to generate preview</p>
                <p class="text-sm mt-2 text-slate-600">${error.message}</p>
                <button onclick="generatePreview()" 
                        class="mt-6 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl">
                    Try Again
                </button>
            </div>
        `;
    });
}

function closeModal() {
    document.getElementById('previewModal')?.classList.add('hidden');
}

window.generatePreview = generatePreview;
window.closeModal = closeModal;

window.onload = populateCourses;

function openDuplicateEnrollmentModal(message) {
    const modal = document.getElementById('duplicateEnrollmentModal');
    const messageBox = document.getElementById('duplicateEnrollmentMessage');

    if (messageBox && message) {
        messageBox.textContent = message;
    }

    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
}

function closeDuplicateEnrollmentModal() {
    const modal = document.getElementById('duplicateEnrollmentModal');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}

async function checkExistingEnrollment(form) {
    const formData = new FormData(form);

    const response = await fetch(window.checkExistingEnrollmentUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });

    if (!response.ok) {
        return { exists: false };
    }

    return response.json();
}

function setupDuplicateEnrollmentCheck() {
    const form = document.getElementById('enrollmentForm');
    const replaceInput = document.getElementById('replaceExistingEnrollment');
    const confirmButton = document.getElementById('confirmDuplicateEnrollment');
    const cancelButtons = [
        document.getElementById('cancelDuplicateEnrollment'),
        document.getElementById('cancelDuplicateEnrollmentTop'),
    ].filter(Boolean);

    if (!form || !replaceInput) return;

    let confirmedDuplicateSubmit = false;

    form.addEventListener('submit', async (event) => {
        if (confirmedDuplicateSubmit) {
            confirmedDuplicateSubmit = false;
            return;
        }

        event.preventDefault();
        replaceInput.value = '0';

        if (!form.reportValidity()) return;

        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton?.innerHTML;

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Checking...';
        }

        try {
            const result = await checkExistingEnrollment(form);

            if (result.exists) {
                const submittedText = result.submitted_at ? ` It was submitted on ${result.submitted_at}.` : '';
                openDuplicateEnrollmentModal(`You already have an enrollment for A.Y. ${result.school_year}.${submittedText} Continuing will replace your previous submission with the details on this form.`);
                return;
            }

            replaceInput.value = '0';
            form.submit();
        } catch (error) {
            console.error('Existing enrollment check failed:', error);
            form.submit();
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        }
    });

    confirmButton?.addEventListener('click', () => {
        replaceInput.value = '1';
        confirmedDuplicateSubmit = true;
        closeDuplicateEnrollmentModal();
        form.requestSubmit();
    });

    cancelButtons.forEach(button => {
        button.addEventListener('click', () => {
            replaceInput.value = '0';
            closeDuplicateEnrollmentModal();
        });
    });
}

function updateDepartmentHead() {
    const input = document.getElementById('department_head_name');
    if (!input) return;

    input.value = window.departmentHeads?.[selectedCourseCode]?.name ?? '';
}

function renderSubjectList() {
    const container = document.getElementById('subjectList');
    if (!container) return;

    const yearLevel = document.getElementById('year_level')?.value;
    const semester = document.getElementById('semester')?.value;
    const subjects = (window.subjectCatalog ?? []).filter(subject =>
        subject.course_code === selectedCourseCode &&
        subject.year_level === yearLevel &&
        subject.semester === semester
    );

    if (!yearLevel || !semester) {
        container.innerHTML = '<p class="text-sm text-slate-500">Choose a program, year level, and semester to load available subjects.</p>';
        updateSubjectTotals();
        return;
    }

    if (subjects.length === 0) {
        container.innerHTML = '<p class="text-sm text-slate-500">No subjects configured for this program, year level, and semester.</p>';
        updateSubjectTotals();
        return;
    }

    container.innerHTML = subjects.map(subject => `
        <label class="mb-3 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 last:mb-0">
            <input type="checkbox" name="subject_ids[]" value="${subject.id}" class="mt-1 subject-checkbox">
            <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold text-slate-800">${subject.code} - ${subject.name}</span>
                <span class="mt-1 block text-xs text-slate-500">${subject.type} / ${subject.total_units} units</span>
            </span>
        </label>
    `).join('');

    document.querySelectorAll('.subject-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSubjectTotals);
    });

    updateSubjectTotals();
}

function updateSubjectTotals() {
    const totalInput = document.getElementById('total_units');
    const selectedIds = Array.from(document.querySelectorAll('.subject-checkbox:checked')).map(input => Number(input.value));
    const subjects = (window.subjectCatalog ?? []).filter(subject => selectedIds.includes(subject.id));

    const totalUnits = subjects.reduce((total, subject) => total + Number(subject.total_units || 0), 0);
    if (totalInput) totalInput.value = totalUnits;
}

async function loadProvinces() {
    const provinceSelect = document.getElementById('province');
    if (!provinceSelect) return;

    provinceSelect.innerHTML = '<option value="">Select Province</option>';

    try {
        const res = await fetch(window.addressDataUrls?.provinces || '/address-data/provinces');
        const json = await res.json();
        const provinces = json.data ?? json;

        provinces.forEach(p => {
            const option = document.createElement('option');
            option.value = p.name;           
            option.dataset.code = String(p.code); 
            option.textContent = p.name || 'Unknown Province';
            provinceSelect.appendChild(option);
        });
        appendOtherOption(provinceSelect, 'Other / Not listed');

    } catch (error) {
        console.error("Failed to load local provinces:", error);
        appendOtherOption(provinceSelect, 'Other / Not listed');
    }
}

async function loadCities(provinceCode) {
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');
    if (!citySelect || !barangaySelect) return;

    citySelect.innerHTML = '<option value="">Select City/Town</option>';
    citySelect.disabled = true;
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;

    if (!provinceCode) return;

    try {
        const res = await fetch(
            `${window.addressDataUrls?.cities || '/address-data/provinces'}/${provinceCode}/cities`
        );
        const json = await res.json();
        const cities = json.data ?? json;

        cities.forEach(city => {
            const option = document.createElement('option');
            option.value = city.name;          
            option.dataset.code = String(city.code); 
            option.textContent = city.name || 'Unknown City';
            citySelect.appendChild(option);
        });

        appendOtherOption(citySelect, 'Other / Not listed');
        citySelect.disabled = false;

    } catch (error) {
        console.error("Failed to load local cities:", error);
        appendOtherOption(citySelect, 'Other / Not listed');
        citySelect.disabled = false;
    }
}

async function loadBarangays(cityCode) {
    const barangaySelect = document.getElementById('barangay');
    if (!barangaySelect) return;

    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;

    if (!cityCode) return;

    try {
        const res = await fetch(
            `${window.addressDataUrls?.barangays || '/address-data/cities'}/${cityCode}/barangays`
        );

        const json = await res.json();
        const barangays = json.data ?? json;

        barangays.forEach(b => {
            const option = document.createElement('option');
            option.value = b.name;
            option.textContent = b.name;
            barangaySelect.appendChild(option);
        });

        appendOtherOption(barangaySelect, 'Other / Not listed');
        barangaySelect.disabled = false;

    } catch (error) {
        console.error("Failed to load local barangays:", error);
        appendOtherOption(barangaySelect, 'Other / Not listed');
        barangaySelect.disabled = false;
    }
}

function appendOtherOption(select, label) {
    if (!select || Array.from(select.options).some(option => option.value === 'Other / Not listed')) {
        return;
    }

    const option = document.createElement('option');
    option.value = 'Other / Not listed';
    option.textContent = label;
    select.appendChild(option);
}

function getSelectedAddress() {
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');

    return {
        province: provinceSelect.value ?? '',
        city:     citySelect.value ?? '',
        barangay: barangaySelect.value,
    };
}


document.addEventListener('DOMContentLoaded', function () {
    loadProvinces();

    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');

    if (!provinceSelect || !citySelect) return;

    provinceSelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        loadCities(selectedOption.dataset.code); 
    });

    citySelect.addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        loadBarangays(selectedOption.dataset.code); 
    });
});


function calculateAge() {
    const dobInput = document.getElementById('date_of_birth');
    const ageInput = document.getElementById('age');
    if (!dobInput || !ageInput) return;

    if (!dobInput.value) {
        ageInput.value = '';
        return;
    }

    const birthDate = new Date(dobInput.value);
    const today = new Date();

    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }

    ageInput.value = age > 0 ? age : '';
}

function setMaxDate() {
    const dobInput = document.getElementById('date_of_birth');
    if (!dobInput) return;

    const today = new Date();
    
    // Minimum age: 15 years ago from today (Sets the 'max' allowable date attribute)
    const maxDate = new Date(today.getFullYear() - 15, today.getMonth(), today.getDate());
    const formattedMaxDate = maxDate.toISOString().split('T')[0];
    dobInput.setAttribute('max', formattedMaxDate);

    // Maximum age: 100 years ago from today (Sets the 'min' allowable date attribute)
    const minDate = new Date(today.getFullYear() - 100, today.getMonth(), today.getDate());
    const formattedMinDate = minDate.toISOString().split('T')[0];
    dobInput.setAttribute('min', formattedMinDate);
}

document.addEventListener('DOMContentLoaded', function() {
    setMaxDate();
    updateDepartmentHead();
    renderSubjectList();
    setupDuplicateEnrollmentCheck();

    document.getElementById('year_level')?.addEventListener('change', renderSubjectList);
    document.getElementById('semester')?.addEventListener('change', renderSubjectList);
    
    const dobInput = document.getElementById('date_of_birth');
    if (!dobInput) return;

    dobInput.addEventListener('change', calculateAge);
    
    if (dobInput.value) {
        calculateAge();
    }
});
