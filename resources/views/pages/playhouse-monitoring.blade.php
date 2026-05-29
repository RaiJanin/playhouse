@extends('layout.basic')

@section('title', 'Monitoring - Playhouse')

@section('scripts')
    @if(env('APP_REALTIME_METHOD') === 'polling')
        @vite('resources/js/modules/playhouse-monitoring-polling.js')
    @elseif (env('APP_REALTIME_METHOD') === 'websocket')
        @vite('resources/js/modules/playhouse-monitoring-websocket.js')
    @endif
@endsection

@section('contents')
    <header class="mb-2 flex flex-wrap gap-1">
        <div class="flex-1">
            <x-application-logo-2 class="block fill-current text-gray-800" />
            <h1 class="text-3xl font-bold text-gray-800">Monitoring</h1>
        </div>
    </header>
    <a href="{{ route('playhouse.bookings') }}" class="underline text-[var(--color-primary-mid-dark)] font-semibold p-1 hover:opacity-80">
       <i class="fa-solid fa-arrow-right-long mr-3"></i>Go to bookings
    </a>
    <div class="mt-4 flex flex-row items-center gap-2 px-8">
        <div class="flex flex-col">
            <label for="search" class="block text-sm font-semibold text-gray-700 mb-1">Search Item/s</label>
            <div class="relative group">
                <div class="flex flex-row">
                    <input 
                        type="text" 
                        id="search-it" 
                        class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-l-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"
                    >
                    <button 
                        id="filter-btn"
                        type="button" 
                        class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-r-xl hover:bg-[var(--color-primary-light)] transition-all duration-300"
                    >
                        <i class="fa-solid fa-magnifying-glass text-xl"></i>
                    </button>
                </div>
                <div class="absolute left-0 -top-12 hidden group-hover:block bg-gray-800 text-white text-xs px-3 py-2 rounded-lg shadow-lg whitespace-nowrap z-10">
                    Search by child's name or QR codes
                </div>
            </div>
        </div>
    </div>
    <div class="overflow-x-auto mt-3 sm:px-8 py-2">
        <table class="min-h-full w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                        Child Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10" style="left: 120px;">
                        Parent Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Booking Number
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        QR Child
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        QR Guardian
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Duration Hours
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Checked In
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Remaining Time
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody id="data-rows" class="bg-white divide-y divide-gray-200">

            </tbody>
        </table>
        <div class="flex flex-row justify-between mt-5 text-xxs sm:text-sm">
            <div id="pagination-meta" class="mt-2 font-semibold"></div>
            <nav id="pagination" aria-label="Pagination Navigation" class="inline-flex items-center space-x-2 text-sm font-semibold"></nav>
        </div>
    </div>
@endsection