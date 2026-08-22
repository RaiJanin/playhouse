<x-breeze-modal name="check-in-all-modal" :show="false" maxWidth="lg">
    <div class="flex flex-col">
        <div class="flex items-center justify-between py-3 px-6 bg-[var(--color-primary-full-dark)]">
            <h2 class="font-semibold text-lg text-gray-50">Check In Children</h2>
            <button type="button" id="check-in-all-close-btn"
                class="w-7 h-7 flex items-center justify-center rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6 bg-[var(--color-primary-transparent)]">
            <p class="text-sm text-gray-600 mb-3">Confirm each child individually before checking them in.</p>
            <ul id="check-in-all-list" class="divide-y divide-gray-200"></ul>
            <p id="check-in-all-empty" class="hidden text-center text-gray-500 py-6">All children are checked in.</p>
        </div>

        <div class="flex justify-end gap-2 py-3 px-6 bg-[var(--color-primary-transparent)]">
            <button type="button" id="check-in-all-done-btn"
                class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300">
                Done
            </button>
        </div>
    </div>
</x-breeze-modal>
