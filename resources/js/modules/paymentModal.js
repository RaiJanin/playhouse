import '../config/global.js';
import { API_ROUTES } from '../config/api.js';
import '../components/alertBlade.js';
import { getOrDelete, submitData } from '../services/requestApi.js';

let currentId = null;
let amountDue = 0;
let saving = false;

let closeBtn, childNameEl, parentNameEl, bookingNumberEl, loadingEl, bodyEl,
    playtimeEl, socksEl, othersEl, discountEl, checkinTimeEl, subtotalEl,
    overtimeSection, overtimeAmountEl, overtimeMinutesEl, overtimeRateEl,
    overtimeBlocksEl, overtimeFormulaEl, amountDueEl,
    unpaidSection, cashInput, changeEl, paidSection, paidCashEl, paidChangeEl,
    paidAtEl, notReadyEl, actionsEl, payBtn, cancelBtn;

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
    unpaidSection = document.getElementById('payment-modal-unpaid-section');
    cashInput = document.getElementById('payment-modal-cash-input');
    changeEl = document.getElementById('payment-modal-change');
    paidSection = document.getElementById('payment-modal-paid-section');
    paidCashEl = document.getElementById('payment-modal-paid-cash');
    paidChangeEl = document.getElementById('payment-modal-paid-change');
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

function recomputeChange() {
    changeEl.textContent = money(Number(cashInput.value || 0) - amountDue);
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

function populate(data) {
    currentId = data.id;
    amountDue = Number(data.amount_due || 0);

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

    unpaidSection.classList.add('hidden');
    paidSection.classList.add('hidden');
    notReadyEl.classList.add('hidden');
    actionsEl.classList.remove('hidden');

    if (data.is_paid) {
        paidSection.classList.remove('hidden');
        paidCashEl.textContent = money(data.cash_tendered);
        paidChangeEl.textContent = money(data.change_amnt);
        paidAtEl.textContent = data.paid_at ? new Date(data.paid_at).toLocaleString() : '';
        actionsEl.classList.add('hidden');
    } else if (data.checked_out) {
        unpaidSection.classList.remove('hidden');
        cashInput.value = '';
        recomputeChange();
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
        const data = await getOrDelete('GET', API_ROUTES.orderItemURL, `${detail.id}/payment-details`);
        console.log('Payment modal data: '+data)
        populate(data);
        loadingEl.classList.add('hidden');
        bodyEl.classList.remove('hidden');
    } catch (err) {
        App.component.criticalAlert('Failed to load payment details.');
    }
}

async function pay() {
    if (saving || !currentId) return;

    const cashTendered = Number(cashInput.value || 0);
    if (cashTendered < amountDue) {
        App.component.showAlert('Cash tendered is less than the amount due.', 'error');
        return;
    }

    saving = true;
    payBtn.disabled = true;
    cancelBtn.disabled = true;

    try {
        await submitData(API_ROUTES.orderItemURL, { cash_tendered: cashTendered }, 'PATCH', `${currentId}/pay`);
        App.component.showAlert('Payment recorded.', 'success');
        setTimeout(() => window.location.reload(), 800);
    } catch (err) {
        App.component.showAlert(err?.data?.message || 'Failed to record payment.', 'error');
        saving = false;
        payBtn.disabled = false;
        cancelBtn.disabled = false;
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

    closeBtn.addEventListener('click', closeModal);
    cashInput.addEventListener('input', recomputeChange);
    payBtn.addEventListener('click', pay);
    cancelBtn.addEventListener('click', cancelCheckout);
}

document.addEventListener('DOMContentLoaded', init);
