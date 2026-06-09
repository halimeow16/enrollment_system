<div class="mt-12">
    <h2 class="section-title border-b border-slate-200 pb-3 mb-6">PERSONAL INFORMATION</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">First Name <span class="text-red-500">*</span></label>
            <input type="text" name="first_name" required value="{{ old('first_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Middle Name</label>
            <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Last Name <span class="text-red-500">*</span></label>
            <input type="text" name="last_name" required value="{{ old('last_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Cellphone Number <span class="text-red-500">*</span></label>
            <input type="tel"
                   name="cellphone"
                   value="{{ old('cellphone') }}"
                   inputmode="numeric"
                   maxlength="11"
                   pattern="09[0-9]{9}"
                   placeholder="09XXXXXXXXX"
                   title="Use an 11-digit Philippine mobile number, e.g. 09171234567"
                   oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                   class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-600 mb-1">Last School Attended <span class="text-red-500">*</span></label>
            <input type="text" name="last_school" required value="{{ old('last_school') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mt-8">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-600 mb-1">Present Address <span class="text-red-500">*</span></label>
            <input type="text" name="present_address" required value="{{ old('present_address') }}"
                   placeholder="House No., Street, Subdivision" maxlength="40" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Province <span class="text-red-500">*</span></label>
            <select id="province" name="province" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="">Select Province</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">City / Town <span class="text-red-500">*</span></label>
            <select id="city" name="city" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="">Select City/Town</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Barangay <span class="text-red-500">*</span></label>
            <select id="barangay" name="barangay" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="">Select Barangay</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Date of Birth <span class="text-red-500">*</span></label>
            <input type="date" id="date_of_birth" required name="date_of_birth" 
                value="{{ old('date_of_birth') }}" 
                class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Age <span class="text-red-500">*</span></label>
            <input type="number" id="age" required name="age" 
                value="{{ old('age') }}" 
                class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl" 
                readonly>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Place of Birth <span class="text-red-500">*</span></label>
            <input type="text" name="place_of_birth" placeholder="City, Province (e.g., Subic, Zambales)" required maxlength="30" value="{{ old('place_of_birth') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Civil Status <span class="text-red-500">*</span></label>
            <select name="civil_status" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="">Select</option>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Widowed">Separated</option>
                <option value="Widowed">Annulled</option>
                <option value="Widowed">Widowed</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Gender <span class="text-red-500">*</span></label>
            <select name="gender" required class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Religion <span class="text-red-500">*</span></label>
            <input type="text" name="religion" required value="{{ old('religion') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
        </div>

        <div class="md:col-span-2 border-t border-slate-100 my-2"></div>

        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-600 mb-1">Father's Name</label>
                <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Address</label>
                <input type="text" name="father_address" placeholder="City, Province (e.g., Subic, Zambales)" maxlength="25" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Cellphone Number</label>
                <input type="tel"
                       name="father_cpNumber"
                       value="{{ old('father_cpNumber') }}"
                       inputmode="numeric"
                       maxlength="11"
                       pattern="09[0-9]{9}"
                       placeholder="09XXXXXXXXX"
                       title="Use an 11-digit Philippine mobile number, e.g. 09171234567"
                       oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                       class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
            </div>

            <div class="md:col-span-2 border-t border-slate-100 my-2"></div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-600 mb-1">Mother's Maiden Name</label>
                <input type="text" name="mother_name" value="{{ old('mother_name') }}" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Address</label>
                <input type="text" name="mother_address" placeholder="City, Province (e.g., Subic, Zambales)" maxlength="25" class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Cellphone Number</label>
                <input type="tel"
                       name="mother_cpNumber"
                       value="{{ old('mother_cpNumber') }}"
                       inputmode="numeric"
                       maxlength="11"
                       pattern="09[0-9]{9}"
                       placeholder="09XXXXXXXXX"
                       title="Use an 11-digit Philippine mobile number, e.g. 09171234567"
                       oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                       class="form-input w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
            </div>
        </div>
    </div>
</div>
