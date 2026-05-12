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