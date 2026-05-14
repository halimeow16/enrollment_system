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
    const form = document.getElementById('enrollmentForm');
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
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => { throw new Error(text || response.statusText); });
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
    document.getElementById('previewModal').classList.add('hidden');
}

function closeModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

window.onload = populateCourses;

async function loadProvinces() {
    const provinceSelect = document.getElementById('province');
    provinceSelect.innerHTML = '<option value="">Select Province</option>';

    try {
        const res = await fetch('https://psgc.cloud/api/provinces');
        const json = await res.json();
        const provinces = json.data ?? json;

        provinces.forEach(p => {
            const option = document.createElement('option');
            option.value = p.name;           
            option.dataset.code = String(p.code); 
            option.textContent = p.name || 'Unknown Province';
            provinceSelect.appendChild(option);
        });

    } catch (error) {
        console.error("Failed to load provinces:", error);
    }
}

async function loadCities(provinceCode) {
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');

    citySelect.innerHTML = '<option value="">Select City/Town</option>';
    citySelect.disabled = true;
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;

    if (!provinceCode) return;

    try {
        const res = await fetch(
            `https://psgc.cloud/api/provinces/${provinceCode}/cities-municipalities`
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

        citySelect.disabled = false;

    } catch (error) {
        console.error("Failed to load cities:", error);
    }
}

async function loadBarangays(cityCode) {
    const barangaySelect = document.getElementById('barangay');
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;

    if (!cityCode) return;

    try {
        const res = await fetch(
            `https://psgc.cloud/api/cities-municipalities/${cityCode}/barangays`
        );

        const json = await res.json();
        const barangays = json.data ?? json;

        barangays.forEach(b => {
            const option = document.createElement('option');
            option.value = b.name;
            option.textContent = b.name;
            barangaySelect.appendChild(option);
        });

        barangaySelect.disabled = false;

    } catch (error) {
        console.error("Failed to load barangays:", error);
        barangaySelect.disabled = false;
    }
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

    document.getElementById('province').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        loadCities(selectedOption.dataset.code); 
    });

    document.getElementById('city').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        loadBarangays(selectedOption.dataset.code); 
    });
});


function calculateAge() {
    const dobInput = document.getElementById('date_of_birth');
    const ageInput = document.getElementById('age');

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
    const today = new Date();
    
    const maxDate = new Date(today.getFullYear() - 15, today.getMonth(), today.getDate());
    
    const formattedMaxDate = maxDate.toISOString().split('T')[0];
    dobInput.setAttribute('max', formattedMaxDate);
}

document.addEventListener('DOMContentLoaded', function() {
    setMaxDate();
    
    const dobInput = document.getElementById('date_of_birth');
    dobInput.addEventListener('change', calculateAge);
    
    if (dobInput.value) {
        calculateAge();
    }
});