<x-breeze-modal name="new-customer-modal" :show="false" maxWidth="6xl">
    <div class="flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between py-3 px-6 bg-[var(--color-primary-full-dark)]">
            <h2 class="font-semibold text-lg text-gray-50">New Customer</h2>
            <button type="button" id="new-customer-modal-close-btn"
                class="w-7 h-7 flex items-center justify-center rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6 bg-[var(--color-primary-transparent)] overflow-y-auto">
            <form id="new-customer-form" class="space-y-6">
                <!-- Customer Info -->
                <div>
                    <h3 class="font-semibold text-gray-800 border-b border-gray-300 pb-1 mb-3">Customer Info</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="new-customer-first-name" class="block text-sm font-semibold text-gray-700 mb-1">First Name <span class="text-red-600">*</span></label>
                            <input type="text" id="new-customer-first-name" name="parentName" required
                                class="w-full rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-2 px-3 shadow-sm">
                        </div>
                        <div>
                            <label for="new-customer-last-name" class="block text-sm font-semibold text-gray-700 mb-1">Last Name <span class="text-red-600">*</span></label>
                            <input type="text" id="new-customer-last-name" name="parentLastName" required
                                class="w-full rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-2 px-3 shadow-sm">
                        </div>
                        <div>
                            <label for="new-customer-phone" class="block text-sm font-semibold text-gray-700 mb-1">Mobile # <span class="text-red-600">*</span></label>
                            <input type="tel" id="new-customer-phone" name="phone" required placeholder="09XXXXXXXXX" inputmode="tel"
                                class="w-full rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-2 px-3 shadow-sm">
                        </div>
                        <div>
                            <label for="new-customer-email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <input type="email" id="new-customer-email" name="parentEmail"
                                class="w-full rounded-md border-gray-300 bg-[var(--color-light-mode)] text-gray-900 py-2 px-3 shadow-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Birthday <span class="text-red-600">*</span></label>
                            <div id="new-customer-birthday" data-birthday-dropdown data-name="parentBirthday" data-year-optional required class="rounded-md"></div>
                            <p class="text-xs text-gray-500 mt-1">Year is optional.</p>
                        </div>
                    </div>
                </div>

                <!-- Children -->
                <div>
                    <div class="flex items-center justify-between border-b border-gray-300 pb-1 mb-3">
                        <h3 class="font-semibold text-gray-800">Children</h3>
                        <button type="button" id="new-customer-add-child-btn"
                            class="text-sm font-semibold text-[var(--color-primary)] bg-[var(--color-accent)] hover:opacity-80 px-3 py-1 rounded-lg transition-all duration-300">
                            <i class="fa-solid fa-plus mr-1"></i> Add another child
                        </button>
                    </div>
                    <div id="new-customer-children" class="space-y-4"></div>
                </div>
            </form>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-between gap-2 py-3 px-6 bg-[var(--color-primary-transparent)] border-t border-gray-300">
            <div class="text-sm text-gray-700">
                Estimated Total:
                <span id="new-customer-total" class="text-lg font-bold text-[var(--color-primary-full-dark)]">&#8369;0.00</span>
            </div>
            <div class="flex gap-2">
                <button type="button" id="new-customer-modal-cancel-btn"
                    class="px-4 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 transition-all duration-300">
                    Cancel
                </button>
                <button type="button" id="new-customer-modal-save-btn"
                    class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300 disabled:opacity-50">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Save Customer
                </button>
            </div>
        </div>
    </div>

    {{-- Child entry template — cloned per child by newCustomerModal.js. __I__ is replaced with the child index. --}}
    <template id="new-customer-child-template">
        <div class="nc-child-entry rounded-xl border border-gray-300 bg-[var(--color-light-mode)] p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="font-semibold text-gray-800">Child <span class="nc-child-number">1</span></span>
                <button type="button" class="nc-remove-child text-xs font-semibold text-white bg-red-600 hover:bg-red-500 px-3 py-1 rounded-lg transition-all duration-300 hidden">
                    <i class="fa-solid fa-trash mr-1"></i> Remove
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Child Photo</label>
                    <div data-camera-input data-name="child[__I__][photo]" class="bg-cyan-50 rounded-lg p-2 overflow-visible"></div>
                </div>
                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3 self-start">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Child Name <span class="text-red-600">*</span></label>
                        <input type="text" data-field="name" required
                            class="w-full rounded-md border-gray-300 bg-white text-gray-900 py-2 px-3 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Birthday <span class="text-red-600">*</span></label>
                        <div data-birthday-dropdown data-name="child[__I__][birthday]" required class="rounded-md"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Playtime Duration <span class="text-red-600">*</span></label>
                        <select data-field="playDuration" required
                            class="nc-duration w-full rounded-md border-gray-300 bg-white text-gray-900 py-2 px-3 shadow-sm"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Add Socks (&#8369;100)</label>
                        <select data-field="addSocks"
                            class="nc-socks w-full rounded-md border-gray-300 bg-white text-gray-900 py-2 px-3 shadow-sm">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-3 p-3 rounded-lg border border-gray-200 bg-white/60">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" class="nc-guardian-toggle">
                    Add a guardian for this child
                </label>
                <div class="nc-guardian-fields grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-3" hidden>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Guardian First Name <span class="text-red-600">*</span></label>
                        <input type="text" data-field="guardianName"
                            class="w-full rounded-md border-gray-300 bg-white text-gray-900 py-2 px-3 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Guardian Last Name</label>
                        <input type="text" data-field="guardianLastName"
                            class="w-full rounded-md border-gray-300 bg-white text-gray-900 py-2 px-3 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Guardian Phone</label>
                        <input type="tel" data-field="guardianPhone" placeholder="09XXXXXXXXX" inputmode="tel"
                            class="w-full rounded-md border-gray-300 bg-white text-gray-900 py-2 px-3 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Guardian Age</label>
                        <input type="number" min="0" data-field="guardianAge"
                            class="w-full rounded-md border-gray-300 bg-white text-gray-900 py-2 px-3 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Guardian Socks (&#8369;100)</label>
                        <select data-field="guardianSocks"
                            class="nc-socks w-full rounded-md border-gray-300 bg-white text-gray-900 py-2 px-3 shadow-sm">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" data-field="guardianAuthorized">
                            Allowed to pick up
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </template>
</x-breeze-modal>
