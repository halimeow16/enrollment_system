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

        <!-- Father's & Mother's Information -->
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