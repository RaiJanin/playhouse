<x-breeze-modal name="payment-modal" :show="false" maxWidth="3xl">
    <div class="flex flex-col">
        <div class="flex items-center justify-between py-3 px-6 bg-[var(--color-primary-full-dark)]">
            <h2 class="font-semibold text-lg text-gray-50">Collect Payment</h2>
            <button type="button" id="payment-modal-close-btn"
                class="w-7 h-7 flex items-center justify-center rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6 bg-[var(--color-primary-transparent)]">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 id="payment-modal-child-name" class="text-2xl font-bold text-gray-900"></h3>
                    <p class="text-base text-gray-800"><span class="font-bold">Guardian:</span> <span id="payment-modal-parent-name"></span></p>
                </div>
                <span class="text-base text-gray-500">Booking# <span id="payment-modal-booking-number" class="font-bold text-gray-800"></span></span>
            </div>

            <div id="payment-modal-loading" class="py-10 text-center text-gray-500 text-lg">Loading payment details...</div>

            <div id="payment-modal-body" class="hidden">
                <div class="text-base text-gray-800 mb-2">
                    <div class="flex justify-between py-1"><span>Play duration</span><span id="payment-modal-playtime" class="font-medium"></span></div>
                    <div class="flex justify-between py-1"><span>Socks</span><span id="payment-modal-socks" class="font-medium"></span></div>
                    <div class="flex justify-between py-1"><span>Others</span><span id="payment-modal-others" class="font-medium"></span></div>
                    <div class="flex justify-between py-1"><span>Discount</span><span id="payment-modal-discount" class="font-medium"></span></div>
                    <div class="flex justify-between py-1"><span>Check-in Time</span><span id="payment-modal-checkin-time" class="font-medium"></span></div>
                </div>

                <div class="flex justify-between py-2 border-t-2 border-gray-300 text-lg font-bold text-gray-900">
                    <span>Subtotal</span><span id="payment-modal-subtotal"></span>
                </div>

                <div id="payment-modal-overtime-section" class="hidden mt-2 pt-2 border-t border-gray-200">
                    <div class="flex justify-between text-base font-bold text-red-600">
                        <span>Extra Charge (Preview)</span><span id="payment-modal-overtime-amount"></span>
                    </div>
                    <div class="text-sm text-gray-500 mt-1 space-y-0.5">
                        <p id="payment-modal-overtime-minutes"></p>
                        <p id="payment-modal-overtime-rate"></p>
                        <p id="payment-modal-overtime-blocks"></p>
                        <p id="payment-modal-overtime-formula"></p>
                    </div>
                </div>

                <div class="flex justify-between py-3 mt-2 border-t-2 border-gray-300 text-2xl font-bold text-gray-900">
                    <span>Total</span><span id="payment-modal-amount-due"></span>
                </div>

                <!-- Recorded payments (supports split payments across methods) -->
                <div id="payment-modal-payments-wrap" class="hidden mt-3 pt-2 border-t border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-1">Payments Recorded</h4>
                    <ul id="payment-modal-payments-list" class="text-base text-gray-800 divide-y divide-gray-100"></ul>
                </div>

                <div class="flex justify-between py-2 mt-2 border-t-2 border-gray-300 text-xl font-bold text-gray-900">
                    <span>Balance Due</span><span id="payment-modal-remaining"></span>
                </div>

                <div id="payment-modal-unpaid-section" class="space-y-2 mt-4">
                    <label class="block text-base font-bold text-gray-800">Payment Method</label>
                    <select id="payment-modal-method-select"
                        class="bg-white w-full rounded-xl border-gray-300 py-3 px-3 shadow-sm text-lg font-semibold"></select>

                    <div id="payment-modal-cash-fields" class="space-y-2">
                        <label class="block text-base font-bold text-gray-800">Cash Tendered (₱)</label>
                        <input type="number" min="0" step="0.01" id="payment-modal-cash-input"
                            class="bg-white w-full rounded-xl border-gray-300 py-3 px-3 shadow-sm text-2xl font-semibold">

                        <div class="flex justify-between text-lg font-bold text-gray-800">
                            <span>Change</span>
                            <span id="payment-modal-change">0.00</span>
                        </div>
                    </div>

                    <div id="payment-modal-amount-fields" class="hidden space-y-2">
                        <label class="block text-base font-bold text-gray-800">Amount to Apply (₱)</label>
                        <input type="number" min="0" step="0.01" id="payment-modal-amount-input"
                            class="bg-white w-full rounded-xl border-gray-300 py-3 px-3 shadow-sm text-2xl font-semibold">
                    </div>

                    <div id="payment-modal-charge-fields" class="hidden space-y-2">
                        <label class="block text-base font-bold text-gray-800">Charge To (Account)</label>
                        <input type="text" list="payment-modal-charge-accounts" id="payment-modal-charge-account-input"
                            placeholder="Type or select an account"
                            class="bg-white w-full rounded-xl border-gray-300 py-3 px-3 shadow-sm text-lg">
                        <datalist id="payment-modal-charge-accounts"></datalist>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Reference</label>
                            <input type="text" id="payment-modal-reference-input" placeholder="OR / transaction #"
                                class="bg-white w-full rounded-xl border-gray-300 py-2 px-3 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Remarks</label>
                            <input type="text" id="payment-modal-remarks-input" placeholder="Optional note"
                                class="bg-white w-full rounded-xl border-gray-300 py-2 px-3 shadow-sm">
                        </div>
                    </div>
                </div>

                <div id="payment-modal-paid-section" class="hidden text-base text-gray-800 space-y-1 border-t pt-2 mt-2">
                    <div class="flex justify-between font-bold text-green-700"><span>Fully Paid</span><span id="payment-modal-paid-at" class="font-medium"></span></div>
                </div>

                <p id="payment-modal-not-ready" class="hidden text-base text-gray-500 italic mt-4">
                    This child has not been checked out yet — nothing to pay.
                </p>
            </div>
        </div>

        <!-- Buttons -->
        <div id="payment-modal-actions" class="flex justify-end gap-2 py-3 px-6 bg-[var(--color-primary-transparent)]">
            <button hidden type="button" id="payment-modal-cancel-btn"
                class="px-4 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300 disabled:opacity-50">
                <i class="fa-solid fa-rotate-left mr-1"></i> Cancel Checkout
            </button>
            <button type="button" id="payment-modal-pay-btn"
                class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300 disabled:opacity-50">
                <i class="fa-solid fa-money-bill-wave mr-1"></i> Add Payment
            </button>
        </div>
    </div>
</x-breeze-modal>
