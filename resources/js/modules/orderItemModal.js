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

let closeBtn, qrChildEl, qrGuardianEl, qrChildPreviewEl, qrGuardianPreviewEl,
    countdownEl, countdownLabelEl, readyBadgeEl,
    loadingEl, bodyEl, childCodeEl, childNameEl, childAgeInput, durationSelect,
    startTimeEl, endTimeEl, outForBreakInput, inFromBreakInput, guardianNameInput,
    guardianMobileInput, guardianAgeInput, guardianAuthorizedInput, promoSelect,
    idNumberEl, bookingNumberEl, socksQtyInput, socksMinusBtn, socksPlusBtn,
    hoursEl, playtimeAmountEl, socksAmountEl, othersInput, subtotalEl, discountEl, lineTotalEl,
    saveBtn, checkoutBtn, checkinBtn, checkoutLabelEl, checkinLabelEl, payBtn, printBtn;

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

    let breakMs = 0;
    if (orderItem.bkin) {
        if (orderItem.bkout) {
            breakMs += new Date(orderItem.bkout).getTime() - new Date(orderItem.bkin).getTime();
        } else {
            breakMs += Date.now() - new Date(orderItem.bkin).getTime();
        }
    }

    const adjustedElapsedMs = Math.max(0, elapsedMs - breakMs);
    const remainingMs = Math.max(0, paidMs - adjustedElapsedMs);

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
    qrChildPreviewEl = document.getElementById('order-modal-qr-child-preview');
    qrGuardianPreviewEl = document.getElementById('order-modal-qr-guardian-preview');
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
    checkinBtn = document.getElementById('order-modal-checkin-btn');
    checkoutLabelEl = document.getElementById('order-modal-checkout-label');
    checkinLabelEl = document.getElementById('order-modal-checkin-label');
    payBtn = document.getElementById('order-modal-pay-btn');
    printBtn = document.getElementById('order-modal-print-btn');
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

/**
 * Renders (or clears) a QR code image inside the given container.
 *
 * @param {HTMLElement} container
 * @param {string} text
 * @returns {void}
 */
function renderQrCode(container, text) {
    if (!container) return;
    container.innerHTML = '';
    if (!text) return;
    new QRCode(container, { text, width: 100, height: 100 });
}

function updateQrPreviews() {
    renderQrCode(qrChildPreviewEl, qrChildEl.value.trim());
    renderQrCode(qrGuardianPreviewEl, qrGuardianEl.value.trim());
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
    const isCheckedin = !!orderItem?.ckin;

    const checkoutState = () => {
        let stateLabel = 'Check Out'
        let dsAble = false

        if(!isCheckedin) {
            stateLabel = 'Not Checked in Yet'
            dsAble = true
        } else if (isCheckedOut) {
            stateLabel = 'Already Checked Out'
            dsAble = true
        }

        return {
            stateLabel, dsAble
        }
    }

    const { stateLabel, dsAble } = checkoutState();

    checkoutBtn.disabled = dsAble;
    checkoutLabelEl.textContent = stateLabel;
}

function applyCheckedInState() {
    const isCheckedIn = orderItem?.ckin ? true : false;
    checkinBtn.disabled = isCheckedIn;
    checkinLabelEl.textContent = isCheckedIn ? 'Already Checked In' : 'Check In';
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

    qrChildEl.value = orderItem.qr_child || '';
    qrGuardianEl.value = orderItem.qr_guardian || '';
    updateQrPreviews();

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
    applyCheckedInState();
    recompute();
    startCountdown();
}

function buildPayload() {
    return {
        qr_child: qrChildEl.value.trim() || null,
        qr_guardian: qrGuardianEl.value.trim() || null,
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

    qrChildEl.value = detail.qrChild || '';
    qrGuardianEl.value = detail.qrGuardian || '';
    bookingNumberEl.textContent = detail.bookId || '';
    updateQrPreviews();

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

async function checkIn() {
    if (saving || !currentId || orderItem.checked_out) return;
    saving = true;
    saveBtn.disabled = true;
    checkinBtn.disabled = true;

    const checkinPayload = {
        qr: qrChildEl.value.trim(),
        status: 'entrance'
    }

    try {
        const response = await submitData(API_ROUTES.checkInURL, checkinPayload, 'POST', currentId);

        if (!response.success) {
            App.component.showAlert(response.message || 'Checkin failed.', 'error');
            return;
        }

        App.component.showAlert('Checked in successfully.', 'success');
        setTimeout(() => window.location.reload(), 800);
    } catch (err) {
        App.component.showAlert('Failed to check in. Please try again.', 'error');
        App.component.criticalAlert(err?.data?.error || err?.statusText || 'Unknown error');
    } finally {
        saving = false;
        saveBtn.disabled = false;
        checkinBtn.disabled = false;
    }
}

function openPaymentModal() {
    if (!currentId) return;
    const id = currentId;
    closeModal();
    window.dispatchEvent(new CustomEvent('open-payment-modal', { detail: { id } }));
}

function getQrDataUrl(container) {
    const canvas = container?.querySelector('canvas');
    return canvas ? canvas.toDataURL('image/png') : null;
}

/**
 * Loads a PDF blob into a hidden iframe and opens the browser's print dialog for it.
 *
 * @param {string} blobUrl
 * @returns {void}
 */
function printPdfBlob(blobUrl) {
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    iframe.src = blobUrl;

    const cleanup = () => {
        iframe.remove();
        URL.revokeObjectURL(blobUrl);
    };

    iframe.addEventListener('load', () => {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            iframe.contentWindow.addEventListener('afterprint', cleanup);
        } catch (err) {
            console.error(err);
            window.open(blobUrl, '_blank');
            cleanup();
        }
    });

    document.body.appendChild(iframe);
    setTimeout(cleanup, 60000);
}

async function printQrCodes() {
    if (!currentId) return;

    const childCode = qrChildEl.value.trim();
    const guardianCode = qrGuardianEl.value.trim();

    if (!childCode && !guardianCode) {
        App.component.showAlert('No QR codes to print.', 'error');
        return;
    }

    printBtn.disabled = true;
    try {
        const response = await fetch(`${API_ROUTES.orderItemURL}/${currentId}/print-qr`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/pdf',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                qr_child_image: getQrDataUrl(qrChildPreviewEl),
                qr_guardian_image: getQrDataUrl(qrGuardianPreviewEl),
            }),
        });

        if (!response.ok) {
            throw new Error(`Server responded with ${response.status}`);
        }

        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);
        printPdfBlob(blobUrl);
    } catch (err) {
        console.error(err);
        App.component.showAlert('Failed to generate QR PDF.', 'error');
    } finally {
        printBtn.disabled = false;
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

    [qrChildEl, qrGuardianEl].forEach(el => el.addEventListener('input', updateQrPreviews));

    saveBtn.addEventListener('click', save);
    checkoutBtn.addEventListener('click', checkOut);
    checkinBtn.addEventListener('click', checkIn);
    payBtn.addEventListener('click', openPaymentModal);
    printBtn.addEventListener('click', printQrCodes);
}

document.addEventListener('DOMContentLoaded', init);
