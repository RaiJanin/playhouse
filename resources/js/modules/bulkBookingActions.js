import { API_ROUTES } from '../config/api.js';
import { getOrDelete, submitData } from '../services/requestApi.js';
import '../components/alertBlade.js';

let saving = false;

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function initCheckInAll() {
    const btn = document.getElementById('booking-check-in-all-btn');
    if (!btn) return;

    btn.addEventListener('click', () => {
        if (saving) return;

        const pending = JSON.parse(btn.dataset.pending || '[]');
        if (!pending.length) {
            App.component.showAlert('No children to check in.', 'error');
            return;
        }

        const list = document.getElementById('check-in-all-list');
        list.innerHTML = pending.map(item => `
            <li class="py-2 text-gray-700">${escapeHtml(item.name)}</li>
        `).join('');

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'check-in-all-modal' }));

        const doneBtn = document.getElementById('check-in-all-done-btn');
        const closeBtn = document.getElementById('check-in-all-close-btn');

        const cleanup = () => {
            doneBtn.removeEventListener('click', handler);
            closeBtn.removeEventListener('click', handler);
        };

        const handler = async () => {
            cleanup();
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'check-in-all-modal' }));

            saving = true;
            btn.disabled = true;

            try {
                const response = await submitData(
                    API_ROUTES.ordersURL,
                    null,
                    'PATCH',
                    `${btn.dataset.ordCode}/check-in-all`
                );

                if (response.success) {
                    App.component.showAlert(`Checked in ${response.checked_in_count} child(ren).`, 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    App.component.showAlert(response.message || 'Check-in failed.', 'error');
                }
            } catch (err) {
                App.component.showAlert('Failed to check in. Please try again.', 'error');
            } finally {
                saving = false;
                btn.disabled = false;
            }
        };

        doneBtn.addEventListener('click', handler);
        closeBtn.addEventListener('click', () => {
            cleanup();
        });
    });
}

function initCheckOutAll() {
    const btn = document.getElementById('booking-check-out-all-btn');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        if (saving) return;
        if (!confirm('Check out all children? This will apply any overtime charges.')) return;

        saving = true;
        btn.disabled = true;

        try {
            const response = await submitData(
                API_ROUTES.ordersURL,
                null,
                'PATCH',
                `${btn.dataset.ordCode}/check-out-all`
            );

            if (response.success) {
                App.component.showAlert(`Checked out ${response.checked_out_count} child(ren).`, 'success');
                setTimeout(() => window.location.reload(), 800);
            } else {
                App.component.showAlert(response.message || 'Check-out failed.', 'error');
            }
        } catch (err) {
            App.component.showAlert('Failed to check out. Please try again.', 'error');
        } finally {
            saving = false;
            btn.disabled = false;
        }
    });
}

function initPayAll() {
    const btn = document.getElementById('booking-pay-all-btn');
    if (!btn) return;

    const ordCode = btn.dataset.ordCode;
    const totalDue = btn.dataset.totalDue;
    const itemsCount = btn.dataset.itemsCount;

    btn.addEventListener('click', () => {
        document.getElementById('pay-all-booking-number').textContent = ordCode;
        document.getElementById('pay-all-items-count').textContent = itemsCount;
        document.getElementById('pay-all-total-due').textContent = '₱' + Number(totalDue).toFixed(2);

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pay-all-modal' }));
        loadPaymentModes();
    });

    const closeBtn = document.getElementById('pay-all-close-btn');
    const cancelBtn = document.getElementById('pay-all-cancel-btn');
    const submitBtn = document.getElementById('pay-all-submit-btn');
    const methodSelect = document.getElementById('pay-all-method-select');
    const cashFields = document.getElementById('pay-all-cash-fields');
    const amountFields = document.getElementById('pay-all-amount-fields');
    const chargeFields = document.getElementById('pay-all-charge-fields');
    const cashInput = document.getElementById('pay-all-cash-input');
    const amountInput = document.getElementById('pay-all-amount-input');
    const chargeAccountInput = document.getElementById('pay-all-charge-account-input');
    const referenceInput = document.getElementById('pay-all-reference-input');
    const remarksInput = document.getElementById('pay-all-remarks-input');
    const changeEl = document.getElementById('pay-all-change');

    const CASH_CODE = 'CSH';
    const CHARGE_CODE = 'charge';

    function applyMethodFields() {
        const method = methodSelect.value;
        const isCash = method === CASH_CODE;
        const isCharge = method === CHARGE_CODE;

        cashFields.classList.toggle('hidden', !isCash);
        amountFields.classList.toggle('hidden', isCash);
        chargeFields.classList.toggle('hidden', !isCharge);

        if (!isCash) {
            amountInput.value = Number(totalDue).toFixed(2);
        }

        if (isCharge) {
            loadChargeAccounts();
        }
    }

    async function loadPaymentModes() {
        try {
            const modes = await getOrDelete('GET', API_ROUTES.paymentModesURL);
            methodSelect.innerHTML = modes.map(m =>
                `<option value="${escapeHtml(m.code)}">${escapeHtml(m.label)}</option>`
            ).join('');
        } catch (err) {
            // ignore
        }
    }

    async function loadChargeAccounts() {
        try {
            const names = await getOrDelete('GET', API_ROUTES.chargeAccountsURL);
            const datalist = document.getElementById('pay-all-charge-accounts');
            datalist.innerHTML = names.map(name => `<option value="${escapeHtml(name)}"></option>`).join('');
        } catch (err) {
            // ignore
        }
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'pay-all-modal' }));
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'pay-all-modal' }));
        });
    }

    if (methodSelect) {
        methodSelect.addEventListener('change', applyMethodFields);
    }

    if (cashInput) {
        cashInput.addEventListener('input', () => {
            const tendered = Number(cashInput.value || 0);
            const due = Number(totalDue);
            changeEl.textContent = '₱' + Math.max(0, tendered - due).toFixed(2);
        });
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', async () => {
            if (saving) return;

            const method = methodSelect.value;
            const payload = {
                payment_method: method,
                reference: referenceInput.value.trim() || null,
                remarks: remarksInput.value.trim() || null,
            };

            if (method === CASH_CODE) {
                const cashTendered = Number(cashInput.value || 0);
                if (cashTendered <= 0) {
                    App.component.showAlert('Enter the cash tendered.', 'error');
                    return;
                }
                payload.cash_tendered = cashTendered;
            } else {
                const amount = Number(amountInput.value || 0);
                if (amount <= 0) {
                    App.component.showAlert('Enter the amount to apply.', 'error');
                    return;
                }
                if (amount > Number(totalDue) + 0.01) {
                    App.component.showAlert('Amount exceeds the total outstanding balance.', 'error');
                    return;
                }
                payload.amount = amount;

                if (method === CHARGE_CODE) {
                    const account = chargeAccountInput.value.trim();
                    if (!account) {
                        App.component.showAlert('Enter or select an account to charge.', 'error');
                        return;
                    }
                    payload.charge_account_name = account;
                }
            }

            saving = true;
            submitBtn.disabled = true;

            try {
                const response = await submitData(
                    API_ROUTES.ordersURL,
                    payload,
                    'PATCH',
                    `${ordCode}/pay-all`
                );

                if (response.success) {
                    App.component.showAlert('Payment recorded.', 'success');
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'pay-all-modal' }));
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    App.component.showAlert(response.message || 'Payment failed.', 'error');
                }
            } catch (err) {
                App.component.showAlert(err?.data?.message || 'Failed to record payment.', 'error');
            } finally {
                saving = false;
                submitBtn.disabled = false;
            }
        });
    }
}

function init() {
    initCheckInAll();
    initCheckOutAll();
    initPayAll();
}

document.addEventListener('DOMContentLoaded', init);
