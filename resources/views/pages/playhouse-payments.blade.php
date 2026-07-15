@extends('layout.basic')

@section('title', 'Payments - PlayHouse')

@section('contents')
    <header class="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div>
            <x-application-logo-2 class="block fill-current text-gray-800" />
            <h1 class="text-3xl font-bold text-gray-800">Payments</h1>
        </div>
        <a href="{{ route('playhouse.bookings') }}" class="text-sm underline text-gray-800 font-semibold p-1 hover:opacity-80">
            <i class="fa-solid fa-arrow-left-long mr-2"></i>Back to Bookings
        </a>
    </header>

    @include('ui.payments')
@endsection

@section('scripts')
    @vite(['resources/js/modules/payments-list.js', 'resources/js/modules/paymentModal.js'])
@endsection
