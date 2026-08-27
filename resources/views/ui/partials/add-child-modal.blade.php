<x-breeze-modal name="add-child-modal" :show="false" maxWidth="4xl">
    <div class="flex flex-col">
        <div class="flex items-center justify-between py-3 px-6 bg-[var(--color-primary-full-dark)]">
            <h2 class="font-semibold text-lg text-gray-50">Add Child</h2>
            <button type="button" id="add-child-modal-close-btn"
                class="w-7 h-7 flex items-center justify-center rounded bg-red-600 text-white hover:bg-red-700 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6 bg-[var(--color-primary-transparent)]">
            <form id="add-child-form" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:order-1 self-start">
                        <label class="block text-base font-semibold text-gray-900 mb-2">Child Photo</label>
                        <div id="add-child-modal-photo" data-camera-input data-name="childPhoto" class="bg-cyan-50 rounded-lg p-2 overflow-visible"></div>
                        <div class="mt-3 p-2 rounded-lg border border-cyan-300 bg-cyan-50/60">
                            <button type="button" id="add-child-guardian-checkbox" class="cursor-pointer p-2 text-sm hover:text-gray-500">
                                <span class="flex items-center">
                                    <i id="add-child-guardian-icon" class="fa-regular fa-square text-gray-500 text-xl"></i>
                                    <p id="add-child-guardian-info" class="ml-2"></p>
                                </span>
                            </button>
                            <div id="add-child-guardian-form" class="grid grid-cols-1 gap-3 mt-3" hidden>
                                <div>
                                    <label class="block text-base font-semibold text-gray-900 mb-2">Guardian First Name <span class="text-red-600">*</span></label>
                                    <input type="text" id="add-child-guardian-name" name="guardianName" class="bg-white/70 w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"/>
                                </div>
                                <div>
                                    <label class="block text-base font-semibold text-gray-900 mb-2">Guardian Last Name</label>
                                    <input type="text" id="add-child-guardian-lastname" name="guardianLastName" class="bg-white/70 w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"/>
                                </div>
                                <div>
                                    <label class="block text-base font-semibold text-gray-900 mb-2">Guardian Phone Number</label>
                                    <input type="tel" id="add-child-guardian-phone" name="guardianPhone" class="bg-white/70 w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300" placeholder="09XXXXXXXXX" inputmode="tel"/>
                                </div>
                                <div>
                                    <label class="block text-base font-semibold text-gray-900 mb-2">Guardian Age</label>
                                    <input type="tel" id="add-child-guardian-age" name="guardianAge" class="bg-white/70 w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"/>
                                </div>
                                <div>
                                    <label class="block text-base font-semibold text-gray-900 mb-2">Add Socks (&#8369;100)</label>
                                    <div class="relative">
                                        <select name="guardianSocks" class="bg-white/70 w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300 cursor-pointer appearance-none">
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[var(--color-primary)]">
                                            <i class="fa-solid fa-chevron-down text-sm"></i>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="add-child-confirm-guardian-checkbox" class="cursor-pointer p-2 text-sm hover:text-gray-500">
                                    <span class="flex flex-row">
                                        <i id="add-child-confirm-guardian-icon" class="fa-regular fa-square text-gray-500 text-xl"></i>
                                        <p id="add-child-confirm-guardian-info" class="ml-2"></p>
                                    </span>
                                </button>
                                <p id="add-child-guardian-underage-warning" class="text-sm font-semibold text-red-600 hidden">
                                    Are you sure do you want to proceed this guardian below 18 yrs old?
                                </p>
                                <input type="hidden" name="guardianAuthorized" id="add-child-guardian-authorized" value="0" />
                            </div>
                        </div>
                    </div>

                    <div class="md:order-2 grid grid-cols-1 gap-4 self-start">
                        <div>
                            <label class="block text-base font-semibold text-gray-900 mb-2">Child Name <span class="text-red-600">*</span></label>
                            <input type="text" id="add-child-name" name="childName" class="bg-white/70 w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300" required/>
                        </div>

                        <div>
                            <label class="block text-base font-semibold text-gray-900 mb-2">Birthday <span class="text-red-600">*</span></label>
                            <div id="add-child-birthday" data-birthday-dropdown data-name="childBirthday" required class="bg-white/70 rounded-xl"></div>
                        </div>

                        <div>
                            <label class="block text-base font-semibold text-gray-900 mb-2">Playtime Duration <span class="text-red-600">*</span></label>
                            <div class="relative">
                                <select id="add-child-duration" name="playDuration" class="bg-white/70 w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300 cursor-pointer appearance-none" required>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[var(--color-primary)]">
                                    <i class="fa-solid fa-chevron-down text-sm"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-base font-semibold text-gray-900 mb-2">Add Socks (&#8369;100)</label>
                            <div class="relative">
                                <select name="addSocks" class="bg-white/70 w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300 cursor-pointer appearance-none">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-[var(--color-primary)]">
                                    <i class="fa-solid fa-chevron-down text-sm"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex justify-end gap-2 py-3 px-6 bg-[var(--color-primary-transparent)]">
            <button type="button" id="add-child-modal-cancel-btn"
                class="px-4 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 transition-all duration-300">
                Cancel
            </button>
            <button type="button" id="add-child-modal-submit-btn"
                class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-lg hover:opacity-80 transition-all duration-300 disabled:opacity-50">
                <i class="fa-solid fa-plus mr-1"></i> Add Child
            </button>
        </div>
    </div>
</x-breeze-modal>
