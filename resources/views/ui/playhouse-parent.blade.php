<div class="p-0 sm:p-4">
    <h2 class="font-bold text-2xl text-gray-800 mb-2 text-center">Parent Information</h2>
    <p class="text-center text-gray-600 mb-5 font-semibold">
        Please provide your details
    </p>
    <div id="edit-parent-checkbox-el" class="hidden">
        <button id="edit-parent-checkbox" type="button" class="cursor-pointer p-2 text-sm hover:text-gray-500">
            <span class="flex items-center">
                <i id="edit-parent-icon" class="fa-regular fa-square text-gray-500 text-xl"></i></i>
                <p id="edit-parent-info" class="ml-2"></p>
            </span>
        </button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 p-4 sm:p-6 border-2 border-gray-50 backdrop-blur bg-white/40 rounded-xl shadow-md">
        <div>
            <label for="parentName" class="block text-base font-semibold text-gray-900 mb-2">First Name <span class="text-red-600">*</span></label>
            <input type="text" id="parentName" name="parentName" class="bg-primary-200 w-full px-4 py-2 border border-[var(--color-primary)] shadow-md rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary)] focus:shadow-none transition-all duration-300" required />
        </div>
        <div>
            <label for="parentLastName" class="block text-base font-semibold text-gray-900 mb-2">Last Name <span class="text-red-600">*</span></label>
            <input type="text" id="parentLastName" name="parentLastName" class="bg-primary-200 w-full px-4 py-2 border border-[var(--color-primary)] shadow-md rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary)] focus:shadow-none transition-all duration-300" required />
        </div>
        <div>
            <label for="parentEmail" class="block text-base font-semibold text-gray-900 mb-2">Email Address <span class="text-red-600">*</span></label>
            <input type="email" id="parentEmail" name="parentEmail" class="bg-primary-200 w-full px-4 py-2 border border-[var(--color-primary)] shadow-md rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary)] focus:shadow-none transition-all duration-300" required />
        </div>
        <div>
            <label for="parentBirthday" class="block text-base font-semibold text-gray-900 mb-2">Birthday</label>
            <div id="parentBirthday" data-birthday-dropdown data-name="parentBirthday" data-year-optional required class="bg-primary-200 rounded-lg"></div>
            <p class="text-xs text-gray-500 mt-1">Year is optional.</p>
            <input type="hidden" id="parentBirthday-hidden" name="parentBirthday">
        </div>
    </div>
    <div class="mt-4 w-full" hidden>
        <div class="relative overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 via-white to-blue-50 shadow-md p-5">
            <div class="flex items-start gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 text-white text-lg shadow">
                    <i class="fa-brands fa-facebook-f"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">
                        Follow our Facebook Page
                    </p>
                    <p class="text-xs text-gray-600 mt-1">
                        Follow us on Facebook and receive an
                        <span class="font-bold text-green-600">exclusive discount</span>.
                    </p>
                    <div class="flex gap-2 mt-3">
                        <button
                        type="button"
                            onclick="openFb()"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition"
                        >
                            <i class="fa-brands fa-facebook"></i>
                            Follow Page
                    </button>
                        {{-- <button
                            type="button"
                            id="already-ff-btn"
                            class="px-4 py-2 rounded-lg border border-gray-300 text-xs font-semibold hover:bg-gray-100 transition"
                        >
                            I Already Followed
                        </button> --}}
                    </div>
                    <input type="hidden" id="isFollowedFlag" name='isfollowedFb' value="0">
                    <script>
                        function openFb() {
                            document.getElementById('isFollowedFlag').value = '1';

                            window.open(window.masterfile.extras.myFacebookPage, '_blank');
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
