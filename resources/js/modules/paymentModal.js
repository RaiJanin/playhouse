import '../config/global.js';
import { API_ROUTES } from '../config/api.js';
import '../components/alertBlade.js';
import { getOrDelete, submitData } from '../services/requestApi.js';

const CASH_CODE = 'CSH';
const CHARGE_CODE = 'charge';

let currentId = null;
let amountDue = 0;
let remainingDue = 0;
let saving = false;
let chargeAccountsLoaded = false;
let paymentModesLoaded = false;
let paymentModesMap = {};

let closeBtn, childNameEl, parentNameEl, bookingNumberEl, loadingEl, bodyEl,
    playtimeEl, socksEl, othersEl, discountEl, checkinTimeEl, subtotalEl,
    overtimeSection, overtimeAmountEl, overtimeMinutesEl, overtimeRateEl,
    overtimeBlocksEl, overtimeFormulaEl, amountDueEl,
    paymentsWrap, paymentsListEl, remainingEl,
    unpaidSection, methodSelect, cashFields, cashInput, changeEl,
    amountFields, amountInput, chargeFields, chargeAccountInput, chargeAccountsDatalist,
    referenceInput, remarksInput,
    paidSection, paidAtEl, notReadyEl, actionsEl, payBtn, cancelBtn;

function onMount() {
    closeBtn = document.getElementById('payment-modal-close-btn');
    childNameEl = document.getElementById('payment-modal-child-name');
    parentNameEl = document.getElementById('payment-modal-parent-name');
    bookingNumberEl = document.getElementById('payment-modal-booking-number');
    loadingEl = document.getElementById('payment-modal-loading');
    bodyEl = document.getElementById('payment-modal-body');
    playtimeEl = document.getElementById('payment-modal-playtime');
    socksEl = document.getElementById('payment-modal-socks');
    othersEl = document.getElementById('payment-modal-others');
    discountEl = document.getElementById('payment-modal-discount');
    checkinTimeEl = document.getElementById('payment-modal-checkin-time');
    subtotalEl = document.getElementById('payment-modal-subtotal');
    overtimeSection = document.getElementById('payment-modal-overtime-section');
    overtimeAmountEl = document.getElementById('payment-modal-overtime-amount');
    overtimeMinutesEl = document.getElementById('payment-modal-overtime-minutes');
    overtimeRateEl = document.getElementById('payment-modal-overtime-rate');
    overtimeBlocksEl = document.getElementById('payment-modal-overtime-blocks');
    overtimeFormulaEl = document.getElementById('payment-modal-overtime-formula');
    amountDueEl = document.getElementById('payment-modal-amount-due');
    paymentsWrap = document.getElementById('payment-modal-payments-wrap');
    paymentsListEl = document.getElementById('payment-modal-payments-list');
    remainingEl = document.getElementById('payment-modal-remaining');
    unpaidSection = document.getElementById('payment-modal-unpaid-section');
    methodSelect = document.getElementById('payment-modal-method-select');
    cashFields = document.getElementById('payment-modal-cash-fields');
    cashInput = document.getElementById('payment-modal-cash-input');
    changeEl = document.getElementById('payment-modal-change');
    amountFields = document.getElementById('payment-modal-amount-fields');
    amountInput = document.getElementById('payment-modal-amount-input');
    chargeFields = document.getElementById('payment-modal-charge-fields');
    chargeAccountInput = document.getElementById('payment-modal-charge-account-input');
    chargeAccountsDatalist = document.getElementById('payment-modal-charge-accounts');
    referenceInput = document.getElementById('payment-modal-reference-input');
    remarksInput = document.getElementById('payment-modal-remarks-input');
    paidSection = document.getElementById('payment-modal-paid-section');
    paidAtEl = document.getElementById('payment-modal-paid-at');
    notReadyEl = document.getElementById('payment-modal-not-ready');
    actionsEl = document.getElementById('payment-modal-actions');
    payBtn = document.getElementById('payment-modal-pay-btn');
    cancelBtn = document.getElementById('payment-modal-cancel-btn');
}

function showModal() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'payment-modal' }));
}

function closeModal() {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'payment-modal' }));
}

function money(value) {
    return `₱${Number(value || 0).toFixed(2)}`;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function applyMethodFieldVisibility() {
    const method = methodSelect.value;
    const isCash = method === CASH_CODE;
    const isCharge = method === CHARGE_CODE;

    cashFields.classList.toggle('hidden', !isCash);
    amountFields.classList.toggle('hidden', isCash);
    chargeFields.classList.toggle('hidden', !isCharge);

    if (!isCash) {
        amountInput.value = remainingDue.toFixed(2);
    }

    if (isCharge) {
        loadChargeAccounts();
    }
}

async function loadPaymentModes() {
    if (paymentModesLoaded) return;
    paymentModesLoaded = true;

    try {
        const modes = await getOrDelete('GET', API_ROUTES.paymentModesURL);
        paymentModesMap = {};
        methodSelect.innerHTML = modes.map(m => {
            paymentModesMap[m.code] = m.label;
            return `<option value="${escapeHtml(m.code)}">${escapeHtml(m.label)}</option>`;
        }).join('');
    } catch (err) {
        paymentModesLoaded = false;
    }
}

async function loadChargeAccounts() {
    if (chargeAccountsLoaded) return;
    chargeAccountsLoaded = true;

    try {
        const names = await getOrDelete('GET', API_ROUTES.chargeAccountsURL);
        chargeAccountsDatalist.innerHTML = names.map(name => `<option value="${escapeHtml(name)}"></option>`).join('');
    } catch (err) {
        chargeAccountsLoaded = false;
    }
}

function recomputeChange() {
    changeEl.textContent = money(Math.max(0, Number(cashInput.value || 0) - remainingDue));
}

function renderOvertime(breakdown) {
    if (!breakdown) {
        overtimeSection.classList.add('hidden');
        return;
    }

    overtimeSection.classList.remove('hidden');
    overtimeAmountEl.textContent = money(breakdown.charge_units * breakdown.rate);
    overtimeMinutesEl.textContent = `Overtime: ${breakdown.extra_minutes} minute(s) (actual ${breakdown.actual_minutes} - paid ${breakdown.paid_minutes})`;
    overtimeRateEl.textContent = `Rate: ₱${breakdown.rate} per ${breakdown.minutes_per_charge} minutes`;
    overtimeBlocksEl.textContent = `Number of ${breakdown.minutes_per_charge}-minute blocks: ${breakdown.charge_units}`;
    overtimeFormulaEl.textContent = `Extra Charge = ${breakdown.charge_units} × ₱${breakdown.rate} = ${money(breakdown.charge_units * breakdown.rate)}`;
}

function renderPayments(payments, allowRemove) {
    if (!payments || !payments.length) {
        paymentsWrap.classList.add('hidden');
        paymentsListEl.innerHTML = '';
        return;
    }

    paymentsWrap.classList.remove('hidden');
    paymentsListEl.innerHTML = payments.map(p => {
        let detail = '';
        if (p.payment_method === CASH_CODE && p.cash_tendered !== null) {
            detail = `tendered ${money(p.cash_tendered)}, change ${money(p.change_amnt)}`;
        } else if (p.payment_method === CHARGE_CODE && p.charge_account) {
            detail = `to ${escapeHtml(p.charge_account.name)}`;
        }
        if (p.reference) {
            detail += `${detail ? ', ' : ''}ref# ${escapeHtml(p.reference)}`;
        }
        if (p.remarks) {
            detail += `${detail ? ', ' : ''}"${escapeHtml(p.remarks)}"`;
        }

        const removeBtn = allowRemove
            ? `<button type="button" class="payment-remove-btn text-red-600 hover:text-red-800 ml-2" data-payment-id="${p.id}" title="Remove this payment">
                 <i class="fa-solid fa-xmark"></i>
               </button>`
            : '';

        const label = paymentModesMap[p.payment_method] || p.payment_method;

        return `<li class="flex items-center justify-between py-1.5">
            <span>${escapeHtml(label)}${detail ? ` <span class="text-sm text-gray-500">(${detail})</span>` : ''}</span>
            <span class="flex items-center font-medium">${money(p.amount)}${removeBtn}</span>
        </li>`;
    }).join('');

    paymentsListEl.querySelectorAll('.payment-remove-btn').forEach(btn => {
        btn.addEventListener('click', () => removePayment(btn.dataset.paymentId));
    });
}

function populate(data) {
    currentId = data.id;
    amountDue = Number(data.amount_due || 0);
    remainingDue = Number(data.remaining_due ?? data.amount_due ?? 0);

    childNameEl.textContent = data.child ? `${data.child.firstname} ${data.child.lastname}` : 'N/A';
    parentNameEl.textContent = data.guardian || data.parent_name || 'N/A';
    bookingNumberEl.textContent = data.ord_code_ph;

    const durationLabel = data.durationhours === 5 ? 'Unlimited' : `${data.durationhours} hr(s)`;
    playtimeEl.textContent = `${durationLabel} — ${money(data.durationsubtotal)}`;
    socksEl.textContent = `${data.socksqty || 0} pair(s) — ${money(data.socksprice)}`;
    othersEl.textContent = money(data.others_amnt);
    discountEl.textContent = `-${money(data.disc_amnt)}`;
    checkinTimeEl.textContent = data.ckin ? new Date(data.ckin).toLocaleTimeString() : 'N/A';
    subtotalEl.textContent = money(Number(data.durationsubtotal) + Number(data.socksprice) + Number(data.others_amnt) - Number(data.disc_amnt));

    renderOvertime(data.overtime_breakdown);

    amountDueEl.textContent = money(data.amount_due);
    remainingEl.textContent = money(remainingDue);

    unpaidSection.classList.add('hidden');
    paidSection.classList.add('hidden');
    notReadyEl.classList.add('hidden');
    actionsEl.classList.remove('hidden');
    cancelBtn.disabled = false;

    renderPayments(data.payments, !data.is_paid);

    if (data.is_paid) {
        paidSection.classList.remove('hidden');
        paidAtEl.textContent = data.paid_at ? new Date(data.paid_at).toLocaleString() : '';
        actionsEl.classList.add('hidden');
    } else if (data.checked_out) {
        unpaidSection.classList.remove('hidden');
        methodSelect.value = CASH_CODE;
        cashInput.value = '';
        amountInput.value = remainingDue.toFixed(2);
        chargeAccountInput.value = '';
        referenceInput.value = '';
        remarksInput.value = '';
        applyMethodFieldVisibility();
        recomputeChange();
        cancelBtn.disabled = (data.payments || []).length > 0;
    } else {
        notReadyEl.classList.remove('hidden');
        actionsEl.classList.add('hidden');
    }
}

async function open(detail) {
    loadingEl.classList.remove('hidden');
    bodyEl.classList.add('hidden');
    showModal();

    try {
        const [data] = await Promise.all([
            getOrDelete('GET', API_ROUTES.orderItemURL, `${detail.id}/payment-details`),
            loadPaymentModes(),
        ]);
        populate(data);
        loadingEl.classList.add('hidden');
        bodyEl.classList.remove('hidden');
    } catch (err) {
        App.component.criticalAlert('Failed to load payment details.');
    }
}

async function refresh() {
    const data = await getOrDelete('GET', API_ROUTES.orderItemURL, `${currentId}/payment-details`);
    populate(data);
    return data;
}

async function addPayment() {
    if (saving || !currentId) return;

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
        if (amount > remainingDue + 0.01) {
            App.component.showAlert('Amount exceeds the remaining balance.', 'error');
            return;
        }
        payload.amount = amount;

        if (method === CHARGE_CODE) {
            const chargeAccountName = chargeAccountInput.value.trim();
            if (!chargeAccountName) {
                App.component.showAlert('Enter or select an account to charge.', 'error');
                return;
            }
            payload.charge_account_name = chargeAccountName;
        }
    }

    saving = true;
    payBtn.disabled = true;
    cancelBtn.disabled = true;

    try {
        await submitData(API_ROUTES.orderItemURL, payload, 'PATCH', `${currentId}/pay`);
        App.component.showAlert('Payment recorded.', 'success');
        if (method === CHARGE_CODE) {
            chargeAccountsLoaded = false;
        }
        const data = await refresh();
        if (data.is_paid) {
            setTimeout(() => window.location.reload(), 800);
        }
    } catch (err) {
        App.component.showAlert(err?.data?.message || 'Failed to record payment.', 'error');
    } finally {
        saving = false;
        payBtn.disabled = false;
    }
}

async function removePayment(paymentId) {
    if (saving || !currentId) return;
    if (!confirm('Remove this payment entry?')) return;

    saving = true;

    try {
        await getOrDelete('DELETE', API_ROUTES.orderItemURL, `${currentId}/payments/${paymentId}`);
        App.component.showAlert('Payment removed.', 'success');
        await refresh();
    } catch (err) {
        App.component.showAlert(err?.data?.message || 'Failed to remove payment.', 'error');
    } finally {
        saving = false;
    }
}

async function cancelCheckout() {
    if (saving || !currentId) return;
    if (!confirm('Cancel this checkout? The child will go back to an active session.')) return;

    saving = true;
    payBtn.disabled = true;
    cancelBtn.disabled = true;

    try {
        await submitData(API_ROUTES.orderItemURL, null, 'PATCH', `${currentId}/cancel-checkout`);
        App.component.showAlert('Checkout cancelled.', 'success');
        setTimeout(() => window.location.reload(), 800);
    } catch (err) {
        App.component.showAlert(err?.data?.message || 'Failed to cancel checkout.', 'error');
        saving = false;
        payBtn.disabled = false;
        cancelBtn.disabled = false;
    }
}

function init() {
    onMount();
    if (!closeBtn) return; // modal partial not on this page

    document.querySelectorAll('.open-payment-btn').forEach(btn => {
        btn.addEventListener('click', () => open({ id: btn.dataset.id }));
    });

    document.querySelectorAll('.order-row').forEach(row => {
        row.addEventListener('click', () => open({ id: row.dataset.id }))
    })

    window.addEventListener('open-payment-modal', (e) => open(e.detail));

    closeBtn.addEventListener('click', closeModal);
    methodSelect.addEventListener('change', applyMethodFieldVisibility);
    cashInput.addEventListener('input', recomputeChange);
    payBtn.addEventListener('click', addPayment);
    cancelBtn.addEventListener('click', cancelCheckout);
}

document.addEventListener('DOMContentLoaded', init);
