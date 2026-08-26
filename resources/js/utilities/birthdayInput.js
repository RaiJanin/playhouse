

// ============ Birthday Dropdown (Inline Selects) ============
// Creates three inline dropdowns for Month, Day, Year
// Usage: add attribute `data-birthday-dropdown` to a container element
// The container should have a `data-name` attribute for the hidden input name
// Example: <div id="parentBirthday" data-birthday-dropdown data-name="parentBirthday" required></div>

/**
 * Attaches a birthday dropdown (Month/Day/Year) to a given container.
 * Maintains a hidden input for form submission and validates the date.
 *
 * @param {HTMLElement} container - The container element to attach the dropdown to.
 */
export function attachBirthdayDropdown(container) {
    if (!container || container.dataset.birthdayDropdownAttached) return;

    const originalName = container.getAttribute('data-name') || 'birthday';
    const isRequired = container.hasAttribute('required');
    const yearOptional = container.hasAttribute('data-year-optional');
    const existingValue = container.dataset.birthdayValue || '';

    // Parse existing value (ISO format YYYY-MM-DD)
    let currentMM = '', currentDD = '', currentYYYY = '';
    if (existingValue && /^\d{4}-\d{2}-\d{2}$/.test(existingValue)) {
        currentYYYY = existingValue.slice(0, 4);
        currentMM = existingValue.slice(5, 7);
        currentDD = existingValue.slice(8, 10);
    }

    // Create hidden input for form submission
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = originalName;
    hiddenInput.value = existingValue;
    container.appendChild(hiddenInput);

    // Create wrapper for dropdowns
    const dropdownWrapper = document.createElement('div');
    dropdownWrapper.className = 'birthday-dropdown-wrapper flex gap-2';

    // Month select
    const monthSelect = document.createElement('select');
    monthSelect.className = 'birthday-month-select flex-1 backdrop-blur-md border border-teal-500 rounded-xl shadow-md px-2 py-2 text-sm font-semibold focus:outline-none focus:border-[#17c1d1] focus:ring-2 focus:ring-[#caf8fc] cursor-pointer';
    monthSelect.setAttribute('aria-label', 'Month');
    
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let monthOptions = '<option value="">Month</option>';
    monthNames.forEach((name, idx) => {
        const val = String(idx + 1).padStart(2, '0');
        const selected = (val === currentMM) ? ' selected' : '';
        monthOptions += `<option value="${val}"${selected}>${name}</option>`;
    });
    monthSelect.innerHTML = monthOptions;

    // Day select
    const daySelect = document.createElement('select');
    daySelect.className = 'birthday-day-select flex-1 backdrop-blur-md border border-teal-500 rounded-xl shadow-md px-2 py-2 text-sm font-semibold focus:outline-none focus:border-[#17c1d1] focus:ring-2 focus:ring-[#caf8fc] cursor-pointer';
    daySelect.setAttribute('aria-label', 'Day');
    
    let dayOptions = '<option value="">Day</option>';
    for (let d = 1; d <= 31; d++) {
        const val = String(d).padStart(2, '0');
        const selected = (val === currentDD) ? ' selected' : '';
        dayOptions += `<option value="${val}"${selected}>${d}</option>`;
    }
    daySelect.innerHTML = dayOptions;

    // Year select
    const yearSelect = document.createElement('select');
    yearSelect.className = 'birthday-year-select flex-1 backdrop-blur-md border border-teal-500 rounded-xl shadow-md px-2 py-2 text-sm font-semibold focus:outline-none focus:border-[#17c1d1] focus:ring-2 focus:ring-[#caf8fc] cursor-pointer';
    yearSelect.setAttribute('aria-label', 'Year');
    
    const currentYear = new Date().getFullYear();
    const startYear = currentYear - 100;
    let yearOptions = '<option value="">Year</option>';
    for (let y = currentYear; y >= startYear; y--) {
        const selected = (String(y) === currentYYYY) ? ' selected' : '';
        yearOptions += `<option value="${y}"${selected}>${y}</option>`;
    }
    yearSelect.innerHTML = yearOptions;

    dropdownWrapper.appendChild(monthSelect);
    dropdownWrapper.appendChild(daySelect);
    dropdownWrapper.appendChild(yearSelect);
    container.appendChild(dropdownWrapper);

    // Function to update hidden input and validation
    const updateValue = () => {
        const mm = monthSelect.value;
        const dd = daySelect.value;
        const yyyy = yearSelect.value;

        // When the year is optional, month + day alone are enough.
        const complete = yearOptional ? (mm && dd) : (mm && dd && yyyy);

        if (complete) {
            // If the year was left blank, fall back to the current year so the
            // stored value stays a valid full date (the column requires YYYY-MM-DD).
            const isoYear = yyyy || '1900';
            const isoDate = `${isoYear}-${mm}-${dd}`;
            hiddenInput.value = isoDate;
            container.dataset.birthdayValue = isoDate;

            // Validate date
            const daysInMonth = new Date(parseInt(isoYear), parseInt(mm), 0).getDate();
            if (parseInt(dd) > daysInMonth) {
                daySelect.setCustomValidity('Invalid day for selected month');
                container.classList.add('birthday-invalid');
                container.classList.remove('birthday-valid');
                container.removeAttribute('data-birthday-valid');
            } else {
                daySelect.setCustomValidity('');
                container.classList.remove('birthday-invalid');
                container.classList.add('birthday-valid');
                container.setAttribute('data-birthday-valid', 'true');
            }
        } else {
            hiddenInput.value = '';
            container.dataset.birthdayValue = '';

            if (isRequired && (mm || dd || yyyy)) {
                container.classList.add('birthday-invalid');
                container.classList.remove('birthday-valid');
                container.removeAttribute('data-birthday-valid');
            } else {
                container.classList.remove('birthday-invalid');
                container.classList.remove('birthday-valid');
                container.removeAttribute('data-birthday-valid');
            }
        }
    };

    // Add event listeners
    monthSelect.addEventListener('change', updateValue);
    daySelect.addEventListener('change', updateValue);
    yearSelect.addEventListener('change', updateValue);

    // Initial validation
    if (currentMM && currentDD && currentYYYY) {
        updateValue();
    }

    container.dataset.birthdayDropdownAttached = '1';
}

function initBirthdayDropdowns(scope = document) {
    const containers = scope.querySelectorAll('[data-birthday-dropdown]');
    containers.forEach(attachBirthdayDropdown);
}

/**
 * Triggers validation on all birthday dropdowns within a given scope.
 * This ensures that the hidden input and validity states are updated.
 *
 * @param {HTMLElement|Document} scope - The container to search for birthday dropdowns. Defaults to the entire document.
 */
export function requestBirthdayDropdownValidation(scope = document) {
    const containers = scope.querySelectorAll('[data-birthday-dropdown]');
    containers.forEach((container) => {
        const monthSelect = container.querySelector('.birthday-month-select');
        const daySelect = container.querySelector('.birthday-day-select');
        const yearSelect = container.querySelector('.birthday-year-select');
        
        if (monthSelect) monthSelect.dispatchEvent(new Event('change', { bubbles: true }));
        if (daySelect) daySelect.dispatchEvent(new Event('change', { bubbles: true }));
        if (yearSelect) yearSelect.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

/**
 * Validates all birthday dropdowns inside a given scope.
 * Ensures that month, day, and year are selected for required fields,
 * or if any value is entered in optional fields.
 *
 * @param {Document|HTMLElement} scope - The container to search for birthday dropdowns. Defaults to `document`.
 * @returns {boolean} - Returns true if all required fields are valid; false otherwise.
 */
export function validateDateInputs(scope = document) {
    const containers = scope.querySelectorAll('.birthday-dropdown-wrapper');
    // Start true so each birthday block can independently fail validation.
    let allValid = true;

    containers.forEach(field => {
        const monthEl = field.querySelector('.birthday-month-select');
        const dayEl = field.querySelector('.birthday-day-select');
        const yearEl = field.querySelector('.birthday-year-select');
        const hostContainer = field.closest('[data-birthday-dropdown]');
        const isRequired = hostContainer ? hostContainer.hasAttribute('required') : false;
        const yearOptional = hostContainer ? hostContainer.hasAttribute('data-year-optional') : false;
        const hasAnyValue = Boolean(
            (monthEl && monthEl.value) ||
            (dayEl && dayEl.value) ||
            (yearEl && yearEl.value)
        );

        // Skip optional birthday fields when untouched.
        if (!isRequired && !hasAnyValue) {
            return;
        }

        const month = checkInput(monthEl);
        const day = checkInput(dayEl);
        // The year is only required when the field isn't flagged year-optional.
        const year = yearOptional ? true : checkInput(yearEl);

        if (!(month && day && year)) {
            const missingMonth = monthEl.dataset.name || monthEl.getAttribute('aria-label');
            const missingYear = yearEl.dataset.name || yearEl.getAttribute('aria-label');
            const missingDay = dayEl.dataset.name || dayEl.getAttribute('aria-label');
            App.component.showAlert(`Fields ${missingMonth},  ${missingYear},  ${missingDay} must filled in`, 'caution');
            hostContainer.scrollIntoView({behavior: 'smooth', block: 'center'});
            allValid = false;
        }
    });

    return allValid;
}

/**
 * Validates a single input element and updates its border color based on validity.
 *
 * @param {HTMLInputElement|HTMLSelectElement} element - The input or select element to validate.
 * @returns {boolean} - Returns true if the element has a value; false if empty.
 */
function checkInput(element) {
    let valid = true;
    if(!element.value) {
        element.classList.remove('border-teal-500');
        element.classList.add('border-red-600');
        valid = false;
    } else {
        element.classList.remove('border-red-600');
        element.classList.add('border-teal-500');
    }

    return valid;
}

// Initialize birthday dropdowns on DOM ready
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        initBirthdayDropdowns();
    });
}

/**
 * Generates HTML for a birthday dropdown with month, day, and year selectors.
 * Optionally pre-selects a value if an existing date string (YYYY-MM-DD) is provided.
 *
 * @param {string} name - The base name to use for the select elements' data-name attributes.
 * @param {string} [existingValue=''] - An optional existing date string in "YYYY-MM-DD" format to pre-select.
 * @returns {string} - HTML string for the birthday dropdown component.
 */
export function createBirthdayDropdownHtml(name, existingValue = '') {
    let currentMM = '', currentDD = '', currentYYYY = '';
    if (existingValue && /^\d{4}-\d{2}-\d{2}$/.test(existingValue)) {
        currentYYYY = existingValue.slice(0, 4);
        currentMM = existingValue.slice(5, 7);
        currentDD = existingValue.slice(8, 10);
    }

    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    let monthOptions = '<option value="">Month</option>';
    monthNames.forEach((name, idx) => {
        const val = String(idx + 1).padStart(2, '0');
        const selected = (val === currentMM) ? ' selected' : '';
        monthOptions += `<option value="${val}"${selected}>${name}</option>`;
    });

    let dayOptions = '<option value="">Day</option>';
    for (let d = 1; d <= 31; d++) {
        const val = String(d).padStart(2, '0');
        const selected = (val === currentDD) ? ' selected' : '';
        dayOptions += `<option value="${val}"${selected}>${d}</option>`;
    }

    const currentYear = new Date().getFullYear();
    let yearOptions = '<option value="">Year</option>';
    for (let y = currentYear; y >= currentYear - 100; y--) {
        const selected = (String(y) === currentYYYY) ? ' selected' : '';
        yearOptions += `<option value="${y}"${selected}>${y}</option>`;
    }

    return `
        <div class="birthday-dropdown-wrapper flex gap-2">
            <select class="birthday-month-select flex-1 bg-white/50 border-2 backdrop-blur-2xl border-gray-200 rounded-lg px-2 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200" data-name="${name}[month]">
                ${monthOptions}
            </select>
            <select class="birthday-day-select flex-1 bg-white/50 backdrop-blur-2xl border-2 border-gray-200 rounded-lg px-2 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200" data-name="${name}[day]">
                ${dayOptions}
            </select>
            <select class="birthday-year-select flex-1 bg-white/50 backdrop-blur-2xl border-2 border-gray-200 rounded-lg px-2 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200" data-name="${name}[year]">
                ${yearOptions}
            </select>
        </div>
    `;
}

/**
 * Toggles an underage warning message based on the age input value.
 * Shows the warning scope when the age is under 18 (if toggled is true),
 * or hides it otherwise.
 *
 * @param {HTMLInputElement} ageInput - The input element containing the age value.
 * @param {HTMLElement} warnMessageScope - The container element for the warning message to show/hide.
 * @param {boolean} toggled - Whether the warning toggle is enabled. When true, checks age; when false, always hides.
 * @returns {void}
 */
export function underageWarning(ageInput, warnMessageScope, toggled) {
    if(toggled) {
        const flag = ageInput.value < 18;
        warnMessageScope.classList.toggle('hidden', !flag);
    } else {
        warnMessageScope.classList.add('hidden');
    }
}