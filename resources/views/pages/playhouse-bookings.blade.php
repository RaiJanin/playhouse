@extends('layout.basic')

@section('title', 'Bookings - PlayHouse')

@section('contents')
    <header class="mb-6 flex justify-between gap-1">
        <div class="flex flex-col gap-2">
            <div class="flex flex-wrap gap-2">
                <div class="flex-1 min-w-[150px] max-w-[200px] rounded-lg p-2 border border-white bg-[var(--color-primary-mid-dark)] backdrop-blur">
                    <h1 class="text-gray-100 font-medium">In house kids</h1>
                    <span class="text-white text-2xl flex flex-row items-center gap-4">
                        <i class="fa-solid fa-child-reaching"></i>
                        <p>{{ $statusMonitor['in_house_guardians'] }}</p>
                    </span>
                </div>
                <div class="flex-1 min-w-[150px] max-w-[200px] rounded-lg p-2 border border-white bg-[var(--color-third-full-dark)] backdrop-blur">
                    <h1 class="text-gray-100 font-medium">In house guardians</h1>
                    <span class="text-white text-2xl flex flex-row items-center gap-4">
                        <i class="fa-solid fa-users-between-lines"></i>
                        <p>{{ $statusMonitor['in_house_kids'] }}</p>
                    </span>
                </div>
                <div class="flex-1 min-w-[150px] max-w-[200px] rounded-lg p-2 border border-white bg-[var(--color-primary-mid-dark)] backdrop-blur">
                    <h1 class="text-gray-100 font-medium">Total kids</h1>
                    <span class="text-white text-2xl flex flex-row items-center gap-4">
                        <i class="fa-solid fa-children"></i>
                        <p>{{ $statusMonitor['total_kids'] }}</p>
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <div class="flex-1 min-w-[150px] max-w-[200px] rounded-lg p-2 border border-white bg-[var(--color-third-full-dark)] backdrop-blur">
                    <h1 class="text-gray-100 font-medium">Total guardians</h1>
                    <span class="text-white text-2xl flex flex-row items-center gap-4">
                        <i class="fa-solid fa-users"></i>
                        <p>{{ $statusMonitor['total_guardians'] }}</p>
                    </span>
                </div>
                <div class="flex-1 min-w-[150px] max-w-[200px] rounded-lg p-2 border border-white bg-[var(--color-third-full-dark)] backdrop-blur">
                    <h1 class="text-gray-100 font-medium">Today's Reservations</h1>
                    <span class="text-white text-2xl flex flex-row items-center gap-4">
                        <i class="fa-solid fa-clipboard-user"></i>
                        <p>{{ $statusMonitor['today_reserves'] }}</p>
                    </span>
                </div>
            </div>
        </div>
        <div class="{{ $searchedOrder ? 'flex-1' : ''}}">
            @if($searchedOrder)
                @php
                    $pendingCheckIn = $searchedOrder->orderItems->whereNull('ckin');
                    $pendingCheckOut = $searchedOrder->orderItems->whereNotNull('ckin')->whereNull('ckout');
                    $unpaidCheckedOut = $searchedOrder->orderItems->whereNotNull('ckout')->where('is_paid', false);
                @endphp
                <div class="rounded-xl border border-white/30 bg-[var(--color-primary-full-dark)] backdrop-blur shadow-lg p-4">
                    <div class="flex items-center justify-between gap-3 mb-3 pb-3 border-b border-white/20">
                        <div>
                            <p class="text-sm uppercase tracking-wide text-gray-300">Booking</p>
                            <h2 class="text-white font-bold text-2xl leading-tight">#{{ $searchedOrder->ord_code_ph }}</h2>
                        </div>
                        <span class="px-3 py-1.5 text-sm font-semibold rounded-full whitespace-nowrap {{ $searchedOrder->balance <= 0 ? 'bg-green-100 text-green-800' : ($searchedOrder->paid_amnt > 0 ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                            {{ $searchedOrder->balance <= 0 ? 'Fully Paid' : ($searchedOrder->paid_amnt > 0 ? 'Partially Paid' : 'Unpaid') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-3">
                        <div class="lg:col-span-2">
                            <dt class="text-sm text-gray-300 uppercase tracking-wide">Customer</dt>
                            <dd class="text-white font-semibold text-lg truncate">{{ $searchedOrder->parentPl->d_name ?? $searchedOrder->parent }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-300 uppercase tracking-wide">Mobile</dt>
                            <dd class="text-white font-semibold text-lg">{{ $searchedOrder?->parentPl?->mobileno ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-300 uppercase tracking-wide">Children</dt>
                            <dd class="text-white font-semibold text-lg">{{ $searchedOrder->orderItems->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-300 uppercase tracking-wide">Visit Date</dt>
                            <dd class="text-white font-semibold text-lg">{{ optional($searchedOrder->visitdate)->format('M d, Y') ?? 'N/A' }}</dd>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-white/20">
                        <div class="flex gap-6">
                            <div>
                                <dt class="text-sm text-gray-300 uppercase tracking-wide">Total</dt>
                                <dd class="text-white font-semibold text-lg">₱{{ number_format($searchedOrder->total_amnt, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-300 uppercase tracking-wide">Paid</dt>
                                <dd class="text-white font-semibold text-lg">₱{{ number_format($searchedOrder->paid_amnt, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-300 uppercase tracking-wide">Booked on</dt>
                                <dd class="text-white font-semibold text-lg">{{ \Carbon\Carbon::parse($searchedOrder?->created_at)->format('M d, Y') }}</dd>
                            </div>
                        </div>
                        <div class="rounded-lg bg-[var(--color-primary)] px-5 py-2 text-right">
                            <dt class="text-xs text-gray-200 uppercase tracking-wide">Balance</dt>
                            <dd class="text-white font-bold text-3xl leading-tight">₱{{ number_format($searchedOrder->balance, 2) }}</dd>
                        </div>
                    </div>

                    <div class="justify-end flex flex-wrap gap-2 pt-3 mt-3 border-t border-white/20">
                        <button hidden type="button" id="booking-check-in-all-btn"
                            data-ord-code="{{ $searchedOrder->ord_code_ph }}"
                            data-pending='@json($pendingCheckIn->map(fn ($item) => [
                                "id" => $item->id,
                                "name" => trim(($item->child->firstname ?? "").' '.($item->child->lastname ?? "")) ?: "Child",
                            ])->values())'
                            class="px-3 py-1.5 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:opacity-80 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed"
                            {{ $pendingCheckIn->isEmpty() ? 'disabled' : '' }}>
                            <i class="fa-solid fa-right-to-bracket mr-1"></i> Check-In All 
                        </button>
                        <button hidden type="button" id="booking-check-out-all-btn"
                            data-ord-code="{{ $searchedOrder->ord_code_ph }}"
                            class="px-3 py-1.5 text-sm font-semibold rounded-lg bg-orange-600 text-white hover:opacity-80 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed"
                            {{ $pendingCheckOut->isEmpty() ? 'disabled' : '' }}>
                            <i class="fa-solid fa-right-from-bracket mr-1"></i> Check-Out All 
                        </button>
                        <button type="button" id="booking-pay-all-btn"
                            data-ord-code="{{ $searchedOrder->ord_code_ph }}"
                            data-total-due="{{ $searchedOrder->balance }}"
                            data-items-count="{{ $unpaidCheckedOut->count() }}"
                            class="px-3 py-1.5 text-sm font-semibold rounded-lg bg-green-600 text-white hover:opacity-80 transition-all duration-300 disabled:opacity-40 disabled:cursor-not-allowed"
                            {{ $unpaidCheckedOut->isEmpty() || $searchedOrder->balance <= 0 ? 'disabled' : '' }}>
                            <i class="fa-solid fa-money-bill-wave mr-1"></i> Pay All
                        </button>
                    </div>
                </div>
            @else
                <x-application-logo-2 class="block fill-current text-gray-800" />
                <h1 class="text-3xl font-bold text-gray-800">Mimo POS</h1>
            @endif
        </div>
    </header>
    <a href="{{ route('playhouse.monitoring') }}" class="text-[var(--color-accent)] font-semibold p-1 bg-[var(--color-primary)] rounded shadow hover:opacity-80 mr-2">
       <i class="fa-solid fa-arrow-right-long mr-3"></i>Go to monitoring
    </a>
    <a href="{{ route('payments.index') }}" class="text-[var(--color-accent)] font-semibold p-1 bg-[var(--color-primary)] rounded shadow hover:opacity-80">
       <i class="fa-solid fa-cash-register mr-3"></i>Go to payments
    </a>
    @include('ui.bookings')
    @include('masterFiles')

@endsection

@section('scripts')
 @vite(['resources/js/modules/orderItemModal.js', 'resources/js/modules/paymentModal.js'])
@endsection