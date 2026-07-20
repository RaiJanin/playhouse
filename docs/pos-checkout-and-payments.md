# POS: Session Management & Counter Payments — Handoff

**Status as of:** 2026-07-16, commit `14a3bcbc` ("POS feature development")
**Context:** Built in a single extended session, iterating directly with the business owner. This doc exists so a fresh session (or another developer) can pick up without re-deriving the decisions below.

## What this covers

Two related features on the admin side of the Playhouse app:

1. **"Enter Play Hours" modal** — replaces the old bare QR-edit modal on `/admin` (bookings page). Lets staff adjust a child's play session (duration, socks, discount, misc charges, break/freeze toggle, guardian info) and check them out.
2. **Counter Payments** — a separate `/payments` flow where staff actually collect cash for children who've been checked out.

Payment was deliberately kept **out** of the play-hours modal — that modal only manages the session (Save = persist edits, Check Out = stop the timer and flag "ready to pay"). Money changes hands only in the Payments flow.

---

## Architecture conventions established (read this before touching either feature)

These aren't obvious from the code alone — they were explicit decisions made mid-session, several after building something a different way first and being corrected:

- **No Alpine.js business logic.** The whole app uses Alpine only for trivial UI toggles (`x-data="{ open: false }"` style). Both modals here were originally built with a large inline `x-data` object doing fetch/computation/submission — this was explicitly rejected and rewritten. Pattern now: Blade templates are static markup with stable `id` attributes; a plain JS module (`document.addEventListener('DOMContentLoaded', init)`, `getElementById` wiring, no framework) owns all fetch/render/submit logic. `x-breeze-modal` (`resources/views/components/breeze-modal.blade.php`) is still used for the modal shell itself (show/hide, backdrop, escape key) — that's existing shared infrastructure, not "business logic," and callers open/close it by dispatching plain `window.dispatchEvent(new CustomEvent('open-modal'|'close-modal', { detail: '<modal-name>' }))`, which works from vanilla JS with no Alpine involvement on the caller's side.
- **`ckin`/`ckout` (timestamps) are the source of truth for "has this child checked in/out," not the `checked_out` boolean column.** This bit us twice. The `ordlne_ph` table has substantial pre-existing/legacy data (see below) where `ckout` is populated but `checked_out` was never set — because that boolean is only ever written by this session's new `checkOut()`/`pay()` code paths, while `ckout` has been the app's real signal since before this work started (the bookings page's `status=ckout` filter and its status badges have always used it). **Anywhere you add new code that needs to know if a child is checked out, check `!empty($item->ckout)`, not `$item->checked_out`.** `PaymentsController` and `PlayHouseController::getOrderItem()`/`checkOut()` were fixed to do this; **`TurnstileController` and the SMS reminder console commands (`NotifyCheckouts`, `NotifyOvertimes`, `Notify10MinutesBeforeTimeOut`) were NOT fixed** and still key off the raw `checked_out` column — see Known Issues.
- **`layout/basic.blade.php` was missing infrastructure `layout/app.blade.php` already had**: no `<meta name="csrf-token">` and no `@include('components.alert')` (the `#alert-container`/`#alert-modal` elements `App.component.showAlert`/`criticalAlert` need). Both are fixed now, but if a *new* layout file ever gets added, check it has both — their absence fails silently/confusingly (fetch requests 419, or `Cannot read properties of null` deep inside alert code, masking the real error).
- **Request helpers**: `resources/js/services/requestApi.js` — `submitData(url, body, method, routeParam)` for POST/PATCH/DELETE, `getOrDelete(method, url, routeParam)` for GET/DELETE. Both build the URL as `` `${url}/${routeParam}` `` for non-GET-without-param cases, so compound param strings like `` `${id}/pay` `` work fine for nested action routes.
- **Alerts**: `App.component.showAlert(message, 'success'|'caution'|'error')` for toasts, `App.component.criticalAlert(message)` for a blocking error modal. Both defined in `resources/js/components/alertBlade.js`.
- **New Vite entries must be added to `vite.config.js`'s `input` array** — it does not glob.

---

## Feature 1: "Enter Play Hours" modal

**Trigger:** click a row in the bookings table (`resources/views/ui/bookings.blade.php`) → dispatches `open-order-modal` window event with `{id, child, qrChild, qrGuardian, bookId}`.

**Files:**
- `resources/views/ui/partials/order-item-modal.blade.php` — static markup, included globally via `layout/basic.blade.php`
- `resources/js/modules/orderItemModal.js` — all logic
- `app/Http/Controllers/PlayHouseController.php` — `getOrderItem($id)` (GET), `updateOrderItem($request, $id)` (PATCH), both newly implemented this session (the routes already existed in `routes/api.php` pointing at nonexistent methods — this is what they were scaffolded for)
- `app/Enums/PromoCode.php` — hardcoded placeholder promo codes (`NONE`, `WELCOME50`, `LOYALTY100`). **These are made-up examples**, not real business codes — replace with actual ones. Discount amount is always resolved server-side from this enum; a client-supplied discount amount is never trusted.
- Migration: `database/migrations/2026_07_15_000000_add_play_hours_fields_to_ordlne_ph_table.php` — adds `others_amnt`, `disc_amnt` to `ordlne_ph`

**Behavior:**
- **Save** → `PATCH /api/order-items/{id}` — persists duration/socks/others/discount/guardian/break-toggle edits, recomputes `subtotal`. Does not touch check-in/out state.
- **Check Out** → same PATCH (to persist any pending edits) followed by `PATCH /api/check-out/{id}` (pre-existing endpoint, `PlayHouseController::checkOut()`) — stamps `ckout`, `checked_out = true`, computes overtime charge (`lne_xtra_chrg`) same as before this session, adds it to the parent order's `total_amnt`/`xtra_chrg_amnt`.
- Once checked out (`ckout` present), the modal shows a "Checked Out" / "Ready to Pay" state instead of a live countdown, disables the break checkboxes, and disables+relabels the Check Out button to "Already Checked Out." This is driven by `getOrderItem()`'s `checked_out` field, which is computed as `!empty($orderItem->ckout)` — **not** the raw column.
- Break toggle ("Out for Break" / "In From Break") only uses the single `bkin`/`bkout`/`isfreeze` columns — deliberately does **not** hook into `TurnstileController`'s 5-slot QR freeze cycle (`bkin1`–`bkin4`/`bkout1`–`bkout4`), which exists in the live DB but isn't tracked by any migration (schema drift, pre-existing).
- Guardian/child-age edits write to the shared `m06_guardian`/`m06_child` master records (same `updateOrCreate` pattern as the original registration flow in `PlayHouseController::store()`), not scoped per-booking. If a child has multiple guardian rows, the modal edits/creates against `guardians->first()` — there's no per-order-item guardian FK to disambiguate further.

---

## Feature 2: Counter Payments

**Entry points:**
1. `/payments` — list of children who are checked out and unpaid ("Ready to Pay" only — everything else is filtered out), sorted by checkout time. Click "Pay" opens the payment modal.
2. Booking-number lookup box at the top of `/payments` — type/scan + Enter navigates to `/payments/{ord_code_ph}`, a per-booking view showing every child under that booking (all statuses, for context), each with its own "Pay"/"View Payment" button opening the same modal.

Both entry points converge on the **same payment modal** (`resources/views/ui/partials/payment-modal.blade.php` + `resources/js/modules/paymentModal.js`) — there is intentionally one payment UI, not two.

**Files:**
- `app/Http/Controllers/PaymentsController.php` (new) — `index` (list page), `show` (per-booking page), `details($id)` (GET, JSON, feeds the modal), `pay($id)` (PATCH), `cancelCheckout($id)` (PATCH)
- `resources/views/pages/playhouse-payments.blade.php` + `resources/views/ui/payments.blade.php` (list page)
- `resources/views/pages/playhouse-payment-show.blade.php` (per-booking page)
- `resources/views/ui/partials/payment-modal.blade.php` + `resources/js/modules/paymentModal.js`
- Migration: `database/migrations/2026_07_16_000000_add_payment_fields_to_ordlne_ph_table.php` — adds `cash_tendered`, `change_amnt`, `is_paid`, `paid_at` to `ordlne_ph`

**Behavior:**
- **Payment is per child (order item)**, even when a booking has multiple children — confirmed explicitly, not per-whole-order.
- **Pay**: validates `cash_tendered >= amount_due` server-side (rejects underpayment, no negative change). `amount_due = subtotal + lne_xtra_chrg`. On success sets `cash_tendered`, `change_amnt`, `is_paid = true`, `paid_at = now()`, and (for hygiene going forward) `checked_out = true`.
- **Cancel**: reverts a checkout — clears `checked_out`/`ckout`, and **critically** reverses the overtime math `checkOut()` applied to the parent order (`$order->total_amnt -= $item->lne_xtra_chrg`, same for `xtra_chrg_amnt`, then zeroes `lne_xtra_chrg` on the item) so the order total doesn't drift. Blocked server-side if `is_paid` is already true (no refund flow exists — out of scope).
- The modal's amount breakdown (Playtime/Socks/Others/Discount/Check-in Time/Subtotal, then a red "Extra Charge (Preview)" subsection with the overtime minute/rate/block math, then a large bold Total) intentionally mirrors the existing checkout-preview card style already used in `resources/js/modules/playhouseCheckout.js` (`viewOrder()`) — the overtime breakdown numbers are reconstructed server-side in `PaymentsController::overtimeBreakdown()` from the stored `ckin`/`ckout`/`lne_xtra_chrg`/`durationhours`, not re-derived client-side, so they can't drift from what was actually charged.
- Bookings list (`ui/bookings.blade.php`) status badges now distinguish **"Ready to Pay"** (blue, checked out + unpaid) from **"Paid"** (green) — both computed in `PlayHouseController::viewBookingsOnlyNamesTimes()`'s `through()` closure using `is_paid`.

---

## Known issues / explicitly deferred (not fixed this session)

1. **`TurnstileController::turnstileSrchPOST` and the SMS reminder commands** (`app/Console/Commands/NotifyCheckouts.php`, `NotifyOvertimes.php`, `Notify10MinutesBeforeTimeOut.php`) still filter on the raw `checked_out` boolean, not `ckout`. There are **86 pre-existing rows** in `ordlne_ph` where `ckout` is set but `checked_out = false` (legacy data, predates this session's work). Practical impact: the turnstile QR scanner could treat an already-checked-out child as still scannable for entrance/exit, and the reminder commands could text a guardian whose child is already checked out. Diagnosed and flagged mid-session; owner hadn't decided how to proceed before we wrapped up.
2. **Unrelated pre-existing migration drift**: `sms_blasts`/`sms_blast_recipients` tables exist in the DB but aren't recorded in Laravel's migration tracker, so a bare `php artisan migrate` fails with "relation already exists" before it reaches any of this session's migrations. Both of this session's migrations were applied via `php artisan migrate --path=...` to route around it. Not touched — outside this session's scope, flagged to the owner.
3. **Report module bugs** (found during an earlier, separate deep-dive of the admin module, explicitly deprioritized by the owner — "disregard the bugs for now"):
   - `ReportService::outletSalesReport()` queries `DB::table('outlet')`, a table that doesn't exist anywhere — the Outlet Sales report (`mR001`) will throw on load.
   - Dashboard (`MimoAdminController::index` + `ui/admin-panel/dashboard-grids.blade.php`) has 5 of 9 stat cards ("Others Today," "Sales Today," "Party Package Reservation," "Cash Amount," "Total Unpaid Amount") that the controller never populates — they silently render `0`/`0.00` via Blade's `??` fallback.
   - `FileManagementController::index` has an unguarded `per_page` (defaults to `null` if omitted from the query string, which currently only doesn't happen because every internal link hardcodes it).
4. **Promo codes are placeholders.** `app/Enums/PromoCode.php` has two made-up example codes. Needs real business discount codes/amounts before this is usable for real transactions.
5. No automated test coverage was added for any of this — all verification this session was manual (DB inspection via `php artisan tinker`, `route:list`, `npm run build`, manual click-through described to me by the owner).

---

## Manual verification checklist (for whoever picks this up)

1. `/admin` → click a checked-out-but-unpaid row → modal should show "Checked Out"/"Ready to Pay" state, Check Out button disabled — **this specifically needs re-testing on a legacy row** (one with `ckout` set from before this session, not one checked out via the new modal) since that's exactly the bug class that was found and fixed.
2. `/payments` → should list only checked-out+unpaid children, sorted earliest-checked-out-first.
3. Booking-number lookup → jumps to `/payments/{booking}`.
4. Pay with insufficient cash → rejected server-side. Pay with sufficient cash → `is_paid` true, bookings list flips to "Paid" badge.
5. Cancel a checkout → order total decrements correctly (verify in DB, not just UI), child becomes active/editable again in the play-hours modal.
6. Cancel on an already-paid item → rejected server-side.
