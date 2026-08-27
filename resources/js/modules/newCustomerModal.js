import '../config/global.js';
import '../components/alertBlade.js';
import { API_ROUTES } from '../config/api.js';
import { submitData } from '../services/requestApi.js';
import {
    attachBirthdayDropdown,
    requestBirthdayDropdownValidation,
} from '../utilities/birthdayInput.js';
import { attachCameraCapture } from '../utilities/cameraCapture.js';

/**
 * POS "New Customer" modal.
 *
 * Mirrors the public registration flow (customer info + one or more children,
 * each with an optional guardian) but with no OTP step, packed into the single
 * order-item-modal style panel on the bookings page. Submits to
 * POST /api/new-customer and reloads into the freshly created booking.
 */

const MODAL_NAME = 'new-customer-modal';

let saving = false;
let childCounter = 0;

let closeBtn, cancelBtn, saveBtn, form, addChildBtn, childrenContainer,
    template, parentBirthdayHost, totalEl,
    firstNameInput, lastNameInput, phoneInput, emailInput;

function onMount() {
    closeBtn = document.getElementById('new-customer-modal-close-btn');
    cancelBtn = document.getElementById('new-customer-modal-cancel-btn');
    saveBtn = document.getElementById('new-customer-modal-save-btn');
    form = document.getElementById('new-customer-form');
    addChildBtn = document.getElementById('new-customer-add-child-btn');
    childrenContainer = document.getElementById('new-customer-children');
    template = document.getElementById('new-customer-child-template');
    parentBirthdayHost = document.getElementById('new-customer-birthday');
    totalEl = document.getElementById('new-customer-total');
    firstNameInput = document.getElementById('new-customer-first-name');
    lastNameInput = document.getElementById('new-customer-last-name');
    phoneInput = document.getElementById('new-customer-phone');
    emailInput = document.getElementById('new-customer-email');
}

function durationOptionsHtml() {
    const map = (window.masterfile && window.masterfile.durationMap) || {};
    return Object.entries(map)
        .map(([key, label]) => `<option value="${key}">${label}</option>`)
        .join('');
}

function rebuildBirthdayHost(host) {
    if (!host) return;
    host.innerHTML = '';
    host.removeAttribute('data-birthday-dropdown-attached');
    host.dataset.birthdayValue = '';
    attachBirthdayDropdown(host);
}

function todayIso() {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
}

function money(amount) {
    return '₱' + Number(amount || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function addChildEntry() {
    const index = childCounter++;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.replace(/__I__/g, String(index));

    const entry = wrapper.firstElementChild;
    entry.dataset.childIndex = String(index);

    const durationSelect = entry.querySelector('[data-field="playDuration"]');
    if (durationSelect) durationSelect.innerHTML = durationOptionsHtml();

    childrenContainer.appendChild(entry);

    entry.querySelectorAll('[data-birthday-dropdown]').forEach(attachBirthdayDropdown);
    entry.querySelectorAll('[data-camera-input]').forEach(attachCameraCapture);

    renumberChildren();
    recalcTotal();
}

function renumberChildren() {
    const entries = childrenContainer.querySelectorAll('.nc-child-entry');
    entries.forEach((entry, i) => {
        const numEl = entry.querySelector('.nc-child-number');
        if (numEl) numEl.textContent = String(i + 1);
        const removeBtn = entry.querySelector('.nc-remove-child');
        if (removeBtn) removeBtn.classList.toggle('hidden', entries.length === 1);
    });
}

function recalcTotal() {
    const durationPriceMap = (window.masterfile && window.masterfile.durationPriceMap) || {};
    const socksPrice = (window.masterfile && window.masterfile.socksPrice) || 0;
    let total = 0;

    childrenContainer.querySelectorAll('.nc-child-entry').forEach((entry) => {
        const duration = entry.querySelector('[data-field="playDuration"]')?.value;
        total += Number(durationPriceMap[duration] || 0);

        if (entry.querySelector('[data-field="addSocks"]')?.value === '1') {
            total += socksPrice;
        }

        const guardianOn = entry.querySelector('.nc-guardian-toggle')?.checked;
        if (guardianOn && entry.querySelector('[data-field="guardianSocks"]')?.value === '1') {
            total += socksPrice;
        }
    });

    if (totalEl) totalEl.textContent = money(total);
}

function onContainerChange(e) {
    const target = e.target;

    if (target.classList.contains('nc-guardian-toggle')) {
        const entry = target.closest('.nc-child-entry');
        const fields = entry.querySelector('.nc-guardian-fields');
        const nameInput = entry.querySelector('[data-field="guardianName"]');
        fields.hidden = !target.checked;
        if (nameInput) nameInput.required = target.checked;
        if (!target.checked) {
            fields.querySelectorAll('input, select').forEach((el) => {
                if (el.type === 'checkbox') el.checked = false;
                else if (el.tagName === 'SELECT') el.selectedIndex = 0;
                else el.value = '';
            });
        }
    }

    recalcTotal();
}

function onContainerClick(e) {
    const removeBtn = e.target.closest('.nc-remove-child');
    if (!removeBtn) return;
    const entry = removeBtn.closest('.nc-child-entry');
    if (!entry) return;
    if (childrenContainer.querySelectorAll('.nc-child-entry').length === 1) return;
    entry.remove();
    renumberChildren();
    recalcTotal();
}

function resetCameraContainer(container) {
    if (!container) return;
    const hidden = container.querySelector('input[type="hidden"]');
    if (hidden) hidden.value = '';

    const preview = container.querySelector('.camera-preview');
    const placeholder = container.querySelector('.camera-placeholder');
    const startBtn = container.querySelector('.start-camera-btn');
    const uploadBtn = container.querySelector('.upload-btn');
    const retakeBtn = container.querySelector('.retake-btn');
    const removeBtn = container.querySelector('.remove-btn');

    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
        preview.classList.remove('object-cover');
        preview.style.width = '';
        preview.style.height = '';
    }
    if (placeholder) placeholder.style.display = 'flex';
    if (startBtn) startBtn.classList.remove('hidden');
    if (uploadBtn) uploadBtn.classList.remove('hidden');
    if (retakeBtn) retakeBtn.classList.add('hidden');
    if (removeBtn) removeBtn.classList.add('hidden');
}

function clearEntry(entry) {
    entry.querySelectorAll('input[data-field], select[data-field]').forEach((el) => {
        if (el.type === 'checkbox') el.checked = false;
        else if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';
    });

    const toggle = entry.querySelector('.nc-guardian-toggle');
    if (toggle) toggle.checked = false;
    const guardianFields = entry.querySelector('.nc-guardian-fields');
    if (guardianFields) guardianFields.hidden = true;

    entry.querySelectorAll('[data-birthday-dropdown]').forEach(rebuildBirthdayHost);
    resetCameraContainer(entry.querySelector('[data-camera-input]'));
}

function resetForm() {
    if (form) form.reset();

    // Reset one child entry in place and drop the rest. Rebuilding from scratch
    // would re-run attachCameraCapture, which leaks a capture modal into <body>.
    const entries = Array.from(childrenContainer.querySelectorAll('.nc-child-entry'));
    entries.forEach((entry, i) => { if (i > 0) entry.remove(); });

    rebuildBirthdayHost(parentBirthdayHost);

    if (entries.length) {
        childCounter = 1;
        clearEntry(entries[0]);
        renumberChildren();
        recalcTotal();
    } else {
        childCounter = 0;
        addChildEntry();
    }
}

function showModal() {
    saving = false;
    if (saveBtn) saveBtn.disabled = false;
    resetForm();
    window.dispatchEvent(new CustomEvent('open-modal', { detail: MODAL_NAME }));
}

function closeModal() {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: MODAL_NAME }));
}

function buildPayload() {
    const children = [];

    childrenContainer.querySelectorAll('.nc-child-entry').forEach((entry) => {
        const guardianOn = entry.querySelector('.nc-guardian-toggle')?.checked;
        const birthdayHost = entry.querySelector('[data-birthday-dropdown]');

        children.push({
            name: entry.querySelector('[data-field="name"]')?.value.trim() || '',
            birthday: birthdayHost?.dataset.birthdayValue || '',
            playDuration: entry.querySelector('[data-field="playDuration"]')?.value || '',
            addSocks: entry.querySelector('[data-field="addSocks"]')?.value || '0',
            photo: entry.querySelector('[data-camera-input] input[type="hidden"]')?.value || null,
            guardianName: guardianOn ? (entry.querySelector('[data-field="guardianName"]')?.value.trim() || null) : null,
            guardianLastName: guardianOn ? (entry.querySelector('[data-field="guardianLastName"]')?.value.trim() || null) : null,
            guardianPhone: guardianOn ? (entry.querySelector('[data-field="guardianPhone"]')?.value.trim() || null) : null,
            guardianAge: guardianOn && entry.querySelector('[data-field="guardianAge"]')?.value
                ? Number(entry.querySelector('[data-field="guardianAge"]').value)
                : null,
            guardianSocks: guardianOn ? (entry.querySelector('[data-field="guardianSocks"]')?.value || '0') : '0',
            guardianAuthorized: guardianOn
                ? !!entry.querySelector('[data-field="guardianAuthorized"]')?.checked
                : false,
        });
    });

    return {
        phone: phoneInput?.value.trim() || '',
        parentName: firstNameInput?.value.trim() || '',
        parentLastName: lastNameInput?.value.trim() || '',
        parentEmail: emailInput?.value.trim() || null,
        parentBirthday: parentBirthdayHost?.dataset.birthdayValue || '',
        visitDate: todayIso(),
        child: children,
    };
}

function validate(payload) {
    if (!payload.parentName || !payload.parentLastName || !payload.phone) {
        App.component.showAlert('Please fill in the customer name and mobile number.', 'caution');
        return false;
    }
    if (!payload.parentBirthday) {
        App.component.showAlert('Please select the customer birthday (month and day).', 'caution');
        return false;
    }
    if (!payload.child.length) {
        App.component.showAlert('Add at least one child.', 'caution');
        return false;
    }
    for (let i = 0; i < payload.child.length; i++) {
        const c = payload.child[i];
        if (!c.name || !c.birthday || !c.playDuration) {
            App.component.showAlert(`Child ${i + 1}: name, birthday and duration are required.`, 'caution');
            return false;
        }
    }
    return true;
}

async function submit() {
    if (saving) return;

    requestBirthdayDropdownValidation(form);
    const payload = buildPayload();

    if (!validate(payload)) return;

    // Guardian toggled on but no first name given.
    const badGuardian = Array.from(childrenContainer.querySelectorAll('.nc-child-entry')).some((entry) => {
        const on = entry.querySelector('.nc-guardian-toggle')?.checked;
        const name = entry.querySelector('[data-field="guardianName"]')?.value.trim();
        return on && !name;
    });
    if (badGuardian) {
        App.component.showAlert('Guardian first name is required when adding a guardian.', 'caution');
        return;
    }

    saving = true;
    saveBtn.disabled = true;

    try {
        const response = await submitData(API_ROUTES.newCustomerURL, payload, 'POST');

        if (response && response.success) {
            App.component.showAlert('Customer created.', 'success');
            const code = response.ordCodePh || response.orderNum;
            const url = new URL(window.location.href);
            url.searchParams.set('search', code);
            setTimeout(() => { window.location.href = url.toString(); }, 600);
        } else {
            throw new Error((response && response.error) || 'Failed to create customer.');
        }
    } catch (err) {
        console.error(err);
        const message = err?.data?.error || err?.data?.message || err?.message || err?.statusText || 'Failed to create customer.';
        App.component.showAlert(message, 'error');
        if (App.component.criticalAlert) App.component.criticalAlert(message);
        saving = false;
        saveBtn.disabled = false;
    }
}

function init() {
    onMount();
    if (!form) return;

    addChildBtn.addEventListener('click', addChildEntry);
    childrenContainer.addEventListener('change', onContainerChange);
    childrenContainer.addEventListener('input', recalcTotal);
    childrenContainer.addEventListener('click', onContainerClick);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    saveBtn.addEventListener('click', submit);

    window.addEventListener('open-new-customer-modal', showModal);
}

document.addEventListener('DOMContentLoaded', init);
