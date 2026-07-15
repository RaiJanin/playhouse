import '../config/global.js';
import { API_ROUTES } from '../config/api.js';
import '../components/alertBlade.js';
import { getOrDelete, submitData } from '../services/requestApi.js';

let currentId = null;
let orderItem = {};
let durations = [];
let promoCodes = (window.masterfile && window.masterfile.promoCodes) || [];
let socksPrice = 0;
let countdownTimer = null;
let saving = false;

let closeBtn, qrChildEl, qrGuardianEl, countdownEl, countdownLabelEl, readyBadgeEl,
    loadingEl, bodyEl, childCodeEl, childNameEl, childAgeInput, durationSelect,
    startTimeEl, endTimeEl, outForBreakInput, inFromBreakInput, guardianNameInput,
    guardianMobileInput, guardianAgeInput, guardianAuthorizedInput, promoSelect,
    idNumberEl, bookingNumberEl, socksQtyInput, hoursEl, playtimeAmountEl,
    socksAmountEl, othersInput, subtotalEl, discountEl, lineTotalEl,
    saveBtn, checkoutBtn, checkoutLabelEl;

/**
 * Formats remaining play time as HH:MM:SS, clamped at 00:00:00.
 *
 * @param {string|null} ckin - ISO check-in timestamp, or null if not checked in.
 * @param {number|string} durationHours - Paid duration in hours ('unlimited' or 5 = unlimited).
 * @returns {string}
 */
function formatCountdown(ckin, durationHours) {
    if (durationHours === 'unlimited' || durationHours === 5) {
        return 'Unlimited';
    }

    if (!ckin) {
        return '--:--:--';
    }

    const paidMs = Number(durationHours || 0) * 60 * 60 * 1000;
    const elapsedMs = Date.now() - new Date(ckin).getTime();
    const remainingMs = Math.max(0, paidMs - elapsedMs);

    const totalSeconds = Math.floor(remainingMs / 1000);
    const hh = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const mm = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const ss = String(totalSeconds % 60).padStart(2, '0');

    return `${hh}:${mm}:${ss}`;
}

function onMount() {
    closeBtn = document.getElementById('order-modal-close-btn');
    qrChildEl = document.getElementById('order-modal-qr-child');
    qrGuardianEl = document.getElementById('order-modal-qr-guardian');
    countdownEl = document.getElementById('order-modal-countdown');
    countdownLabelEl = document.getElementById('order-modal-countdown-label');
    readyBadgeEl = document.getElementById('order-modal-ready-badge');
    loadingEl = document.getElementById('order-modal-loading');
    bodyEl = document.getElementById('order-modal-body');
    childCodeEl = document.getElementById('order-modal-child-code');
    childNameEl = document.getElementById('order-modal-child-name');
    childAgeInput = document.getElementById('order-modal-child-age');
    durationSelect = document.getElementById('order-modal-duration');
    startTimeEl = document.getElementById('order-modal-start-time');
    endTimeEl = document.getElementById('order-modal-end-time');
    outForBreakInput = document.getElementById('order-modal-out-for-break');
    inFromBreakInput = document.getElementById('order-modal-in-from-break');
    guardianNameInput = document.getElementById('order-modal-guardian-name');
    guardianMobileInput = document.getElementById('order-modal-guardian-mobile');
    guardianAgeInput = document.getElementById('order-modal-guardian-age');
    guardianAuthorizedInput = document.getElementById('order-modal-guardian-authorized');
    promoSelect = document.getElementById('order-modal-promo');
    idNumberEl = document.getElementById('order-modal-id-number');
    bookingNumberEl = document.getElementById('order-modal-booking-number');
    socksQtyInput = document.getElementById('order-modal-socks-qty');
    hoursEl = document.getElementById('order-modal-hours');
    playtimeAmountEl = document.getElementById('order-modal-playtime-amount');
    socksAmountEl = document.getElementById('order-modal-socks-amount');
    othersInput = document.getElementById('order-modal-others');
    subtotalEl = document.getElementById('order-modal-subtotal');
    discountEl = document.getElementById('order-modal-discount');
    lineTotalEl = document.getElementById('order-modal-line-total');
    saveBtn = document.getElementById('order-modal-save-btn');
    checkoutBtn = document.getElementById('order-modal-checkout-btn');
    checkoutLabelEl = document.getElementById('order-modal-checkout-label');
}

function showModal() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-item-modal' }));
}

function closeModal() {
    stopCountdown();
    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'order-item-modal' }));
}

function stopCountdown() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

function startCountdown() {
    stopCountdown();

    const isCheckedOut = !!orderItem.checked_out;
    countdownLabelEl.classList.toggle('hidden', isCheckedOut);
    readyBadgeEl.classList.toggle('hidden', !isCheckedOut);

    if (isCheckedOut) {
        countdownEl.textContent = 'Checked Out';
        return;
    }

    const tick = () => {
        const selected = durations.find(d => d.id === Number(durationSelect.value));
        countdownEl.textContent = formatCountdown(
            orderItem.ckin,
            selected ? selected.duration_hour : orderItem.durationhours
        );
    };
    tick();
    countdownTimer = setInterval(tick, 1000);
}

function populateDurationsSelect() {
    durationSelect.innerHTML = durations
        .map(d => `<option value="${d.id}">${d.label}</option>`)
        .join('');
    durationSelect.value = orderItem.durations_id;
}

function populatePromoSelect() {
    promoSelect.innerHTML = promoCodes
        .map(p => `<option value="${p.code}">${p.label}</option>`)
        .join('');
    promoSelect.value = orderItem.disc_code || '';
}

function recompute() {
    const selected = durations.find(d => d.id === Number(durationSelect.value));
    const hours = selected ? selected.duration_hour : orderItem.durationhours;
    const playtimeAmount = selected ? Number(selected.price) : Number(orderItem.durationsubtotal || 0);
    const socksAmount = Number(socksQtyInput.value || 0) * Number(socksPrice || 0);
    const othersAmnt = Number(othersInput.value || 0);
    const subtotal = playtimeAmount + socksAmount + othersAmnt;
    const promo = promoCodes.find(p => p.code === promoSelect.value);
    const discAmnt = promo ? Number(promo.discount) : 0;
    const lineTotal = Math.max(0, subtotal - discAmnt);

    hoursEl.textContent = hours === 'unlimited' || hours === 5 ? 'Unlimited' : hours;
    playtimeAmountEl.textContent = playtimeAmount.toFixed(2);
    socksAmountEl.textContent = socksAmount.toFixed(2);
    subtotalEl.textContent = subtotal.toFixed(2);
    discountEl.textContent = discAmnt.toFixed(2);
    lineTotalEl.textContent = lineTotal.toFixed(2);
}

function applyBreakAvailability() {
    const isCheckedOut = !!orderItem.checked_out;
    const isFrozen = !!(orderItem.bkin && !orderItem.bkout);

    outForBreakInput.checked = false;
    inFromBreakInput.checked = false;
    outForBreakInput.disabled = isCheckedOut || !orderItem.ckin || isFrozen;
    inFromBreakInput.disabled = isCheckedOut || !isFrozen;
}

function applyCheckedOutState() {
    const isCheckedOut = !!orderItem.checked_out;
    checkoutBtn.disabled = isCheckedOut;
    checkoutLabelEl.textContent = isCheckedOut ? 'Already Checked Out' : 'Check Out';
}

function populateForm(data) {
    orderItem = data.orderItem;
    durations = data.durations || [];
    promoCodes = data.promoCodes || promoCodes;
    socksPrice = Number(data.socksPrice || 0);

    const child = data.child || {};
    const guardian = data.guardian || {};

    childCodeEl.textContent = child.d_code_c || '';
    childNameEl.textContent = `${child.firstname || ''} ${child.lastname || ''}`.trim();
    childAgeInput.value = child.age ?? '';

    populateDurationsSelect();
    populatePromoSelect();

    startTimeEl.textContent = orderItem.ckin ? new Date(orderItem.ckin).toLocaleTimeString() : 'Not checked in';
    endTimeEl.textContent = orderItem.ckout ? new Date(orderItem.ckout).toLocaleTimeString() : '—';

    guardianNameInput.value = guardian.d_name || '';
    guardianMobileInput.value = guardian.mobileno || '';
    guardianAgeInput.value = guardian.age ?? '';
    guardianAuthorizedInput.checked = !!guardian.guardianauthorized;

    idNumberEl.textContent = orderItem.id;
    bookingNumberEl.textContent = orderItem.ord_code_ph;

    socksQtyInput.value = orderItem.socksqty || 0;
    othersInput.value = orderItem.others_amnt || 0;

    applyBreakAvailability();
    applyCheckedOutState();
    recompute();
    startCountdown();
}

function buildPayload() {
    return {
        durations_id: Number(durationSelect.value),
        socksqty: Number(socksQtyInput.value || 0),
        others_amnt: Number(othersInput.value || 0),
        disc_code: promoSelect.value || null,
        out_for_break: outForBreakInput.checked,
        in_from_break: inFromBreakInput.checked,
        child_age: childAgeInput.value !== '' ? Number(childAgeInput.value) : null,
        guardian_name: guardianNameInput.value || null,
        guardian_mobileno: guardianMobileInput.value || null,
        guardian_age: guardianAgeInput.value !== '' ? Number(guardianAgeInput.value) : null,
        guardian_authorized: guardianAuthorizedInput.checked,
    };
}

async function open(detail) {
    currentId = detail.id;

    qrChildEl.textContent = detail.qrChild || 'N/A';
    qrGuardianEl.textContent = detail.qrGuardian || 'N/A';
    bookingNumberEl.textContent = detail.bookId || '';

    loadingEl.classList.remove('hidden');
    bodyEl.classList.add('hidden');
    showModal();

    try {
        const data = await getOrDelete('GET', API_ROUTES.orderItemURL, currentId);
        populateForm(data);
        loadingEl.classList.add('hidden');
        bodyEl.classList.remove('hidden');
    } catch (err) {
        App.component.criticalAlert('Failed to load play session details.');
    }
}

async function save() {
    if (saving || !currentId) return;
    saving = true;
    saveBtn.disabled = true;

    try {
        await submitData(API_ROUTES.orderItemURL, buildPayload(), 'PATCH', currentId);
        App.component.showAlert('Play session updated.', 'success');
        setTimeout(() => window.location.reload(), 800);
    } catch (err) {
        console.error(err)
        App.component.showAlert('Failed to save. Please try again.', 'error');
        App.component.criticalAlert(err?.data?.error || err?.statusText || 'Unknown error');
    } finally {
        saving = false;
        saveBtn.disabled = false;
    }
}

async function checkOut() {
    if (saving || !currentId || orderItem.checked_out) return;
    saving = true;
    saveBtn.disabled = true;
    checkoutBtn.disabled = true;

    try {
        await submitData(API_ROUTES.orderItemURL, buildPayload(), 'PATCH', currentId);
        const response = await submitData(API_ROUTES.checkOutURL, null, 'PATCH', currentId);

        if (!response.checked_out) {
            App.component.showAlert(response.message || 'Checkout failed.', 'error');
            return;
        }

        App.component.showAlert('Checked out successfully.', 'success');
        setTimeout(() => window.location.reload(), 800);
    } catch (err) {
        App.component.showAlert('Failed to check out. Please try again.', 'error');
        App.component.criticalAlert(err?.data?.error || err?.statusText || 'Unknown error');
    } finally {
        saving = false;
        saveBtn.disabled = false;
        checkoutBtn.disabled = !!orderItem.checked_out;
    }
}

function init() {
    onMount();
    if (!closeBtn) return; // modal partial not on this page

    window.addEventListener('open-order-modal', (e) => open(e.detail));
    closeBtn.addEventListener('click', closeModal);

    [durationSelect, socksQtyInput, othersInput, promoSelect].forEach(el => {
        el.addEventListener('input', recompute);
        el.addEventListener('change', recompute);
    });

    saveBtn.addEventListener('click', save);
    checkoutBtn.addEventListener('click', checkOut);
}

document.addEventListener('DOMContentLoaded', init);
