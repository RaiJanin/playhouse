<x-breeze-modal name="order-item-modal" :show="false" maxWidth="6xl">
    <div class="flex flex-col">
        <div class="flex items-center justify-between py-3 px-6 bg-[var(--color-primary-full-dark)]">
            <h2 class="font-semibold text-lg text-gray-50">Enter Play Hours</h2>
            <button type="button" id="order-modal-close-btn"
                class="w-7 h-7 flex items-center justify-center rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6 bg-[var(--color-primary-transparent)]">
            <!-- QR Row -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Child QR Code</label>
                    <div id="order-modal-qr-child" class="rounded-md py-2 px-3 bg-yellow-100 border border-yellow-300 text-gray-800 font-medium">N/A</div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Guardian QR Code</label>
                    <div id="order-modal-qr-guardian" class="rounded-md py-2 px-3 bg-orange-200 border border-orange-400 text-gray-800 font-medium">N/A</div>
                </div>
            </div>

            <!-- Countdown -->
            <div class="text-center py-3 mb-4 border-y border-gray-300">
                <span id="order-modal-countdown" class="text-3xl sm:text-4xl font-bold text-gray-800">--:--:--</span>
                <span id="order-modal-countdown-label" class="text-xl sm:text-2xl font-semibold text-gray-600"> Remaining Hours</span>
                <span id="order-modal-ready-badge" class="ml-2 px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800 hidden">Ready to Pay</span>
            </div>

            <div id="order-modal-loading" class="py-10 text-center text-gray-500">Loading play session...</div>

            <div id="order-modal-body" class="grid grid-cols-1 lg:grid-cols-2 gap-6 hidden">
                <!-- Left column: Playtime Info -->
                <div class="space-y-3">
                    <h3 class="font-semibold text-gray-800 border-b border-gray-300 pb-1">Playtime Info</h3>

                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 w-28">Child</span>
                        <span id="order-modal-child-code" class="text-xs text-gray-400"></span>
                        <span id="order-modal-child-name" class="flex-1 font-semibold text-gray-800"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 w-28">Child Age</label>
                        <input type="number" min="0" id="order-modal-child-age"
                            class="flex-1 rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-1.5 px-2 shadow-sm">
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 w-28">Playtime Duration</label>
                        <select id="order-modal-duration" class="flex-1 rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-1.5 px-2 shadow-sm"></select>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 w-28">Start Time</label>
                        <div id="order-modal-start-time" class="flex-1 rounded-md border-gray-300 py-1.5 px-2 bg-gray-100 text-gray-700">Not checked in</div>
                        <label class="flex items-center gap-1 text-xs font-medium text-gray-700">
                            <input type="checkbox" id="order-modal-out-for-break">
                            Out for Break
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 w-28">End Time</label>
                        <div id="order-modal-end-time" class="flex-1 rounded-md border-gray-300 py-1.5 px-2 bg-gray-100 text-gray-700">—</div>
                        <label class="flex items-center gap-1 text-xs font-medium text-gray-700">
                            <input type="checkbox" id="order-modal-in-from-break">
                            In From Break
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 w-28">Guardian Name</label>
                        <input type="text" id="order-modal-guardian-name"
                            class="flex-1 rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-1.5 px-2 shadow-sm">
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 w-28">Guardian Mobile#</label>
                        <input type="text" id="order-modal-guardian-mobile"
                            class="flex-1 rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-1.5 px-2 shadow-sm">
                        <label class="text-sm font-semibold text-gray-700">Age</label>
                        <input type="number" min="0" id="order-modal-guardian-age"
                            class="w-16 rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-1.5 px-2 shadow-sm">
                    </div>

                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" id="order-modal-guardian-authorized">
                        This guardian is allowed to pickup
                    </label>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 w-28">Promo Code</label>
                        <select id="order-modal-promo" class="flex-1 rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-1.5 px-2 shadow-sm"></select>
                    </div>

                    <div class="flex items-center justify-between pt-2 mt-2 border-t border-gray-300 text-sm text-gray-500">
                        <span>ID Number <span id="order-modal-id-number" class="font-semibold text-gray-700"></span></span>
                        <span>Booking# <span id="order-modal-booking-number" class="font-semibold text-gray-700"></span></span>
                    </div>
                </div>

                <!-- Right column: Amount Info -->
                <div class="space-y-3">
                    <h3 class="font-semibold text-gray-800 border-b border-gray-300 pb-1">Amount Info</h3>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 flex-1">Socks Qty</label>
                        <input type="number" min="0" id="order-modal-socks-qty"
                            class="w-28 rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-1.5 px-2 shadow-sm text-right">
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 flex-1">Hours</span>
                        <span id="order-modal-hours" class="w-28 text-right font-medium text-gray-800"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 flex-1">Playtime (₱)</span>
                        <span id="order-modal-playtime-amount" class="w-28 text-right font-medium text-gray-800"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 flex-1">Socks (₱)</span>
                        <span id="order-modal-socks-amount" class="w-28 text-right font-medium text-gray-800"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-gray-700 flex-1">Others (₱)</label>
                        <input type="number" min="0" step="0.01" id="order-modal-others"
                            class="w-28 rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-1.5 px-2 shadow-sm text-right">
                    </div>

                    <div class="border-t border-dashed border-gray-300 my-2"></div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 flex-1">Subtotal (₱)</span>
                        <span id="order-modal-subtotal" class="w-28 text-right font-medium text-gray-800"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-gray-700 flex-1">Discount (₱)</span>
                        <span id="order-modal-discount" class="w-28 text-right font-medium text-gray-800"></span>
                    </div>

                    <div class="rounded-lg p-4 mt-3 bg-[var(--color-third-full-dark)] text-center">
                        <div class="text-sm text-gray-100 uppercase tracking-wider">Line Total (₱)</div>
                        <div id="order-modal-line-total" class="text-3xl font-bold text-white"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end gap-2 py-3 px-6 bg-[var(--color-primary-transparent)]">
            <button type="button" id="order-modal-save-btn"
                class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300 disabled:opacity-50">
                <i class="fa-solid fa-floppy-disk mr-1"></i> Save
            </button>
            <button type="button" id="order-modal-checkout-btn"
                class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300 disabled:opacity-50">
                <i class="fa-solid fa-right-from-bracket mr-1"></i>
                <span id="order-modal-checkout-label">Check Out</span>
            </button>
        </div>
    </div>
</x-breeze-modal>
