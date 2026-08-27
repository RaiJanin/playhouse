import '../config/global.js';
import { API_ROUTES } from '../config/api.js';
import { submitData } from '../services/requestApi.js';
import { attachBirthdayDropdown, underageWarning } from '../utilities/birthdayInput.js';
import { attachCameraCapture } from '../utilities/cameraCapture.js';
import { CustomCheckbox } from '../components/customCheckbox.js';

let saving = false;

let closeBtn, cancelBtn, submitBtn, form, birthdayContainer, durationSelect,
    guardianCheckbox, guardianForm, guardianNameInput, guardianLastNameInput,
    guardianPhoneInput, guardianAgeInput, guardianAuthorizedInput,
    confirmGuardianCheckbox, confirmGuardianIcon, confirmGuardianInfo,
    guardianUnderageWarning;

let currentOrdCodePh = null;

function onMount() {
    closeBtn = document.getElementById('add-child-modal-close-btn');
    cancelBtn = document.getElementById('add-child-modal-cancel-btn');
    submitBtn = document.getElementById('add-child-modal-submit-btn');
    form = document.getElementById('add-child-form');
    birthdayContainer = document.getElementById('add-child-birthday');
    durationSelect = document.getElementById('add-child-duration');
    guardianCheckbox = document.getElementById('add-child-guardian-checkbox');
    guardianForm = document.getElementById('add-child-guardian-form');
    guardianNameInput = document.getElementById('add-child-guardian-name');
    guardianLastNameInput = document.getElementById('add-child-guardian-lastname');
    guardianPhoneInput = document.getElementById('add-child-guardian-phone');
    guardianAgeInput = document.getElementById('add-child-guardian-age');
    guardianAuthorizedInput = document.getElementById('add-child-guardian-authorized');
    confirmGuardianCheckbox = document.getElementById('add-child-confirm-guardian-checkbox');
    confirmGuardianIcon = document.getElementById('add-child-confirm-guardian-icon');
    confirmGuardianInfo = document.getElementById('add-child-confirm-guardian-info');
    guardianUnderageWarning = document.getElementById('add-child-guardian-underage-warning');
}

function showModal(ordCodePh) {
    currentOrdCodePh = ordCodePh;
    resetForm();
    populateDurations();
    
    const photoContainer = document.getElementById('add-child-modal-photo');
    if (photoContainer) {
        attachCameraCapture(photoContainer);
    }
    
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'add-child-modal' }));
}

function closeModal() {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'add-child-modal' }));
}

function resetForm() {
    if (!form) return;
    form.reset();
    
    if (guardianForm) guardianForm.hidden = true;
    if (guardianAuthorizedInput) guardianAuthorizedInput.value = '0';
    if (guardianUnderageWarning) guardianUnderageWarning.classList.add('hidden');
    if (confirmGuardianIcon) {
        confirmGuardianIcon.classList.remove('fa-solid', 'fa-square-check', 'text-green-500');
        confirmGuardianIcon.classList.add('fa-regular', 'fa-square', 'text-gray-500');
    }
    if (confirmGuardianInfo) confirmGuardianInfo.textContent = 'This guardian is allowed to pick this child';
    
    const birthdayHost = document.getElementById('add-child-birthday');
    if (birthdayHost) {
        birthdayHost.innerHTML = '';
        birthdayHost.removeAttribute('data-birthday-dropdown-attached');
        birthdayHost.dataset.birthdayValue = '';
        attachBirthdayDropdown(birthdayHost);
    }

    const photoHiddenInput = document.querySelector('#add-child-modal-photo input[type="hidden"]');
    if (photoHiddenInput) photoHiddenInput.value = '';

    const photoContainer = document.getElementById('add-child-modal-photo');
    if (photoContainer) {
        const preview = photoContainer.querySelector('.camera-preview');
        const placeholder = photoContainer.querySelector('.camera-placeholder');
        const startBtn = photoContainer.querySelector('.start-camera-btn');
        const uploadBtn = photoContainer.querySelector('.upload-btn');
        const retakeBtn = photoContainer.querySelector('.retake-btn');
        const removeBtn = photoContainer.querySelector('.remove-btn');

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
}

function populateDurations() {
    if (!durationSelect || !window.masterfile || !window.masterfile.durationMap) return;
    durationSelect.innerHTML = Object.entries(window.masterfile.durationMap)
        .map(([key, duration]) => `<option value="${key}">${duration}</option>`)
        .join('');
}

function buildPayload() {
    const formData = new FormData(form);
    return {
        childName: formData.get('childName')?.trim() || '',
        childBirthday: document.getElementById('add-child-birthday')?.dataset.birthdayValue || '',
        playDuration: formData.get('playDuration') || '',
        addSocks: formData.get('addSocks') || '0',
        guardianName: formData.get('guardianName')?.trim() || null,
        guardianLastName: formData.get('guardianLastName')?.trim() || null,
        guardianPhone: formData.get('guardianPhone')?.trim() || null,
        guardianAge: formData.get('guardianAge') ? Number(formData.get('guardianAge')) : null,
        guardianSocks: formData.get('guardianSocks') || '0',
        guardianAuthorized: document.getElementById('add-child-guardian-authorized')?.value === '1',
        childPhoto: document.querySelector('#add-child-modal-photo input[type="hidden"]')?.value || null,
    };
}

async function submit() {
    if (saving || !currentOrdCodePh) return;
    saving = true;
    submitBtn.disabled = true;

    const payload = buildPayload();
    
    if (!payload.childName || !payload.childBirthday || !payload.playDuration) {
        App.component.showAlert('Please fill in all required fields.', 'error');
        saving = false;
        submitBtn.disabled = false;
        return;
    }

    try {
        const response = await submitData(
            `${API_ROUTES.ordersURL}/${currentOrdCodePh}/add-child`,
            payload,
            'POST'
        );

        if (response.success) {
            App.component.showAlert('Child added successfully.', 'success');
            closeModal();
            setTimeout(() => window.location.reload(), 800);
        } else {
            throw new Error(response.error || 'Failed to add child.');
        }
    } catch (err) {
        console.error(err);
        App.component.showAlert(err?.data?.error || err?.statusText || 'Failed to add child. Please try again.', 'error');
        App.component.criticalAlert(err?.data?.error || err?.statusText || 'Unknown error');
    } finally {
        saving = false;
        submitBtn.disabled = false;
    }
}

function init() {
    onMount();
    if (!closeBtn) return;

    const guardianCheckboxInstance = new CustomCheckbox(
        'add-child-guardian-checkbox',
        'add-child-guardian-icon',
        'add-child-guardian-info'
    );
    guardianCheckboxInstance.setLabel('Add Guardian');

    const confirmGuardianCheckboxInstance = new CustomCheckbox(
        'add-child-confirm-guardian-checkbox',
        'add-child-confirm-guardian-icon',
        'add-child-confirm-guardian-info'
    );
    confirmGuardianCheckboxInstance.setLabel('This guardian is allowed to pick up my child');

    guardianCheckboxInstance.onChange((checked) => {
        guardianForm.hidden = !checked;
        guardianNameInput.required = checked;

        const optionalFields = [guardianLastNameInput, guardianAgeInput, guardianPhoneInput];
        if (!guardianCheckboxInstance.isChecked()) {
            [guardianNameInput, guardianLastNameInput, guardianAgeInput, guardianPhoneInput].forEach(field => {
                if (!field) return;
                field.value = '';
                field.required = false;
            });
        }
    });

    confirmGuardianCheckboxInstance.onChange((checked) => {
        if (confirmGuardianCheckboxInstance.isChecked()) {
            guardianAuthorizedInput.value = '1';
        } else {
            guardianAuthorizedInput.value = '0';
        }

        const optionalFields = [guardianLastNameInput, guardianAgeInput, guardianPhoneInput];
        optionalFields.forEach(field => {
            if (!field) return;
            field.required = checked;
            if (!confirmGuardianCheckboxInstance.isChecked()) {
                field.classList.remove('border-red-600');
                field.classList.add('border-[var(--color-primary)]');
            }
        });

        underageWarning(guardianAgeInput, guardianUnderageWarning, checked);
    });

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    submitBtn.addEventListener('click', submit);

    window.addEventListener('open-add-child-modal', (e) => {
        showModal(e.detail.ordCodePh);
    });
}

document.addEventListener('DOMContentLoaded', init);
