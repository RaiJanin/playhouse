<div class="p-4">
    <div class="mb-6 rounded-2xl p-6 bg-[var(--color-primary-full-dark)]">
        <label for="booking-lookup" class="block text-sm font-semibold text-gray-100 mb-2">
            Scan or type Booking Number, then press Enter
        </label>
        <input
            type="text"
            id="booking-lookup"
            autofocus
            placeholder="e.g. 26G0448"
            class="w-full text-2xl sm:text-3xl font-bold tracking-wider text-center py-4 px-4 rounded-xl border-0 shadow-inner focus:outline-none focus:ring-4 focus:ring-[var(--color-accent)]"
        >
    </div>

    <form class="mb-4 flex flex-col sm:flex-row items-end gap-4" method="GET">
        <div class="flex flex-col">
            <label for="start_date" class="block text-sm font-semibold text-gray-700 mb-1">From Date</label>
            <input type="date" id="start_date" name="start_date"
                class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold"
                value="{{ request('start_date', now()->format('Y-m-d')) }}">
        </div>
        <div class="flex flex-col">
            <label for="end_date" class="block text-sm font-semibold text-gray-700 mb-1">To Date</label>
            <input type="date" id="end_date" name="end_date"
                class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold"
                value="{{ request('end_date', now()->format('Y-m-d')) }}">
        </div>
        <div class="flex flex-col">
            <label for="search" class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
            <input type="text" id="search" name="search"
                placeholder="Booking # or child name"
                class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold"
                value="{{ request('search') }}">
        </div>
        <button type="submit"
            class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-xl hover:bg-[var(--color-primary-light)] transition-all duration-300">
            Filter
        </button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Child Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent / Guardian</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Due</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($orderItems as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->child ? $item->child->firstname . ' ' . $item->child->lastname : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->order?->parentPl?->d_name ?? $item->guardian ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->ord_code_ph }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₱{{ number_format($item->amount_due, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button type="button" class="open-payment-btn px-3 py-1.5 bg-[var(--color-primary)] text-white font-semibold rounded-lg hover:opacity-80 transition-all"
                                data-id="{{ $item->id }}">
                                Pay
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No children are ready to pay right now.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">
        {{ $orderItems->onEachSide(2)->links() }}
    </div>
</div>
