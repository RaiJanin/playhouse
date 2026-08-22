<x-breeze-modal name="pay-all-modal" :show="false" maxWidth="lg">
    <div class="flex flex-col">
        <div class="flex items-center justify-between py-3 px-6 bg-[var(--color-primary-full-dark)]">
            <h2 class="font-semibold text-lg text-gray-50">Pay All</h2>
            <button type="button" id="pay-all-close-btn"
                class="w-7 h-7 flex items-center justify-center rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6 bg-[var(--color-primary-transparent)]">
            <div class="flex justify-between items-center mb-4">
                <span class="text-base text-gray-700">Booking# <span id="pay-all-booking-number" class="font-bold text-gray-900"></span></span>
                <span class="text-base text-gray-700"><span id="pay-all-items-count" class="font-bold text-gray-900"></span> unpaid child(ren)</span>
            </div>

            <div class="flex justify-between py-2 border-t-2 border-b-2 border-gray-300 text-2xl font-bold text-gray-900 mb-4">
                <span>Total Outstanding</span><span id="pay-all-total-due"></span>
            </div>

            <div class="space-y-2">
                <label class="block text-base font-bold text-gray-800">Payment Method</label>
                <select id="pay-all-method-select"
                    class="bg-white w-full rounded-xl border-gray-300 py-3 px-3 shadow-sm text-lg font-semibold"></select>

                <div id="pay-all-cash-fields" class="space-y-2">
                    <label class="block text-base font-bold text-gray-800">Cash Tendered (₱)</label>
                    <input type="number" min="0" step="0.01" id="pay-all-cash-input"
                        class="bg-white w-full rounded-xl border-gray-300 py-3 px-3 shadow-sm text-2xl font-semibold">

                    <div class="flex justify-between text-lg font-bold text-gray-800">
                        <span>Change</span>
                        <span id="pay-all-change">0.00</span>
                    </div>
                </div>

                <div id="pay-all-amount-fields" class="hidden space-y-2">
                    <label class="block text-base font-bold text-gray-800">Amount to Apply (₱)</label>
                    <input type="number" min="0" step="0.01" id="pay-all-amount-input"
                        class="bg-white w-full rounded-xl border-gray-300 py-3 px-3 shadow-sm text-2xl font-semibold">
                </div>

                <div id="pay-all-charge-fields" class="hidden space-y-2">
                    <label class="block text-base font-bold text-gray-800">Charge To (Account)</label>
                    <input type="text" list="pay-all-charge-accounts" id="pay-all-charge-account-input"
                        placeholder="Type or select an account"
                        class="bg-white w-full rounded-xl border-gray-300 py-3 px-3 shadow-sm text-lg">
                    <datalist id="pay-all-charge-accounts"></datalist>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Reference</label>
                        <input type="text" id="pay-all-reference-input" placeholder="OR / transaction #"
                            class="bg-white w-full rounded-xl border-gray-300 py-2 px-3 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Remarks</label>
                        <input type="text" id="pay-all-remarks-input" placeholder="Optional note"
                            class="bg-white w-full rounded-xl border-gray-300 py-2 px-3 shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 py-3 px-6 bg-[var(--color-primary-transparent)]">
            <button type="button" id="pay-all-cancel-btn"
                class="px-4 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300">
                Cancel
            </button>
            <button type="button" id="pay-all-submit-btn"
                class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300 disabled:opacity-50">
                <i class="fa-solid fa-money-bill-wave mr-1"></i> Pay All
            </button>
        </div>
    </div>
</x-breeze-modal>
