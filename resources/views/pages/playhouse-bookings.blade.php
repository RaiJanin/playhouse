@extends('layout.basic')

@section('title', 'Bookings - PlayHouse')

@section('contents')
    <header class="mb-6 flex flex-wrap gap-1">
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
        <div class="flex-1">
            @if($searchedOrder)
                <div class="rounded-lg p-3 border border-white bg-[var(--color-primary-full-dark)] backdrop-blur">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-white font-bold text-lg">Booking # {{ $searchedOrder->ord_code_ph }}</h2>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $searchedOrder->balance <= 0 ? 'bg-green-100 text-green-800' : ($searchedOrder->paid_amnt > 0 ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                            {{ $searchedOrder->balance <= 0 ? 'Fully Paid' : ($searchedOrder->paid_amnt > 0 ? 'Partially Paid' : 'Unpaid') }}
                        </span>
                    </div>
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm">
                        <div>
                            <dt class="text-gray-300">Guardian</dt>
                            <dd class="text-white font-semibold truncate">{{ $searchedOrder->parentPl->d_name ?? $searchedOrder->parent }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-300">Children</dt>
                            <dd class="text-white font-semibold">{{ $searchedOrder->orderItems->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-300">Visit Date</dt>
                            <dd class="text-white font-semibold">{{ optional($searchedOrder->visitdate)->format('M d, Y') ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-300">Total Amount</dt>
                            <dd class="text-white font-semibold">₱{{ number_format($searchedOrder->total_amnt, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-300">Paid Amount</dt>
                            <dd class="text-white font-semibold">₱{{ number_format($searchedOrder->paid_amnt, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-300">Balance</dt>
                            <dd class="text-white font-semibold">₱{{ number_format($searchedOrder->balance, 2) }}</dd>
                        </div>
                    </dl>
                </div>
            @else
                <x-application-logo-2 class="block fill-current text-gray-800" />
                <h1 class="text-3xl font-bold text-gray-800">Mimo POS</h1>
            @endif
        </div>
    </header>
    <a href="{{ route('playhouse.monitoring') }}" class="underline text-[var(--color-primary-mid-dark)] font-semibold p-1 hover:opacity-80">
       <i class="fa-solid fa-arrow-right-long mr-3"></i>Go to monitoring
    </a>
    <a href="{{ route('payments.index') }}" class="underline text-[var(--color-primary-mid-dark)] font-semibold p-1 hover:opacity-80">
       <i class="fa-solid fa-cash-register mr-3"></i>Go to payments
    </a>
    @include('ui.bookings')
    @include('masterFiles')

@endsection

@section('scripts')
    @vite(['resources/js/modules/orderItemModal.js', 'resources/js/modules/paymentModal.js'])
@endsection