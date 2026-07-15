@extends('layout.basic')

@section('title', 'Bookings - PlayHouse')

@section('contents')
    <header class="mb-6 flex flex-wrap gap-1">
        <div class="flex-1">
            <x-application-logo-2 class="block fill-current text-gray-800" />
            <h1 class="text-3xl font-bold text-gray-800">Bookings</h1>
        </div>
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
    @vite('resources/js/modules/orderItemModal.js')
@endsection