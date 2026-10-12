<div class="mb-2">
    <h1 class="text-3xl font-bold text-gray-800">Admin Panel</h1>
    <a href="{{ route('playhouse.bookings') }}" target="_blank" class="text-sm underline text-gray-800 font-semibold p-1 hover:opacity-80">
        <i class="fa-solid fa-arrow-right-long mr-3"></i>Access admin for cashiers
    </a>
</div>
<div class="p-4">
    <form class="mb-4 flex flex-col sm:flex-row items-end gap-4" method="GET">
        <div class="flex flex-col">
            <label for="status" class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
            <select
                id="status"
                name="status"
                class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"
            >
                <option value="">All</option>
                <option value="ckin" {{ request('status') === 'ckin' ? 'selected' : '' }}>Active Check-ins</option>
                <option value="ckout" {{ request('status') === 'ckout' ? 'selected' : '' }}>Check-outs</option>
                <option value="reservation" {{ request('status') === 'reservation' ? 'selected' : '' }}>Reservations</option>
            </select>
        </div>
        <div class="flex flex-col">
            <label for="start_date" class="block text-sm font-semibold text-gray-700 mb-1">From Date</label>
            <input
                type="date"
                id="start_date"
                name="start_date"
                class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"
                value="{{ request('start_date', now()->format('Y-m-d')) }}"
            >
        </div>
        <div class="flex flex-col">
            <label for="end_date" class="block text-sm font-semibold text-gray-700 mb-1">To Date</label>
            <input
                type="date"
                id="end_date"
                name="end_date"
                class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"
                value="{{ request('end_date', now()->format('Y-m-d')) }}"
            >
        </div>
        <button
            type="submit"
            class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-xl hover:bg-[var(--color-primary-light)] transition-all duration-300"
        >
            Filter
        </button>
        <a
            href="{{ route('dashboard', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
            class="px-4 py-2 bg-gray-500 text-white font-semibold rounded-xl hover:bg-gray-600 transition-all duration-300 disabled:cursor-not-allowed disabled:bg-gray-400"
        >
            Today
        </a>
        <a
            href="{{ route('dashboard', [
                            'start_date' => request()->query('start_date') ?? '',
                            'end_date' => request()->query('end_date') ?? '',
                            'status' => request()->query('status') ?? ''
                        ])
                  }}"
            class="px-4 py-2 bg-[var(--color-primary-full-dark)] text-white font-semibold rounded-xl hover:bg-[var(--color-primary-mid-dark)] transition-all duration-300 disabled:cursor-not-allowed disabled:bg-gray-400"
        >
           <i class="fa-solid fa-rotate"></i> Refresh
        </a>
    </form>

</div>

<div class="rounded-lg">
    <!-- Top Scrollbar -->
    <div class="overflow-x-auto" id="topScrollbar" style="cursor: grab;">
        <div style="width: 1200px; height: 16px;"></div>
    </div>
    <div class="overflow-x-auto" id="tableContainer">
        <table class="min-w-full divide-y divide-gray-200" style="min-width: 1200px;">
            <thead class="bg-[var(--color-accent-mid-dark)]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-large text-gray-800 uppercase tracking-wider sticky left-0 bg-[var(--color-accent-mid-dark)] z-10">Child Name</th>
                    <th class="px-6 py-3 text-left text-xs font-large text-gray-800 uppercase tracking-wider sticky left-0 bg-[var(--color-accent-mid-dark)] z-10" style="left: 120px;">Parent Name</th>
                    @foreach($columns as $column)
                        <th class="px-6 py-3 text-left text-xs font-large text-gray-800 uppercase tracking-wider">
                            {{ $labels[$column] ?? ucfirst($column) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-[var(--color-accent)] divide-y divide-gray-200">
                @forelse($orderItems as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-[var(--color-accent)] z-10">
                            @if($item->child)
                                {{ $item->child->firstname }} {{ $item->child->lastname }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-[var(--color-accent)] z-10" style="left: 120px;">
                            @if($item->order && $item->order->parentPl)
                                {{ $item->order->parentPl->d_name ?? $item->order->parentPl->firstname . ' ' . $item->order->parentPl->lastname }}
                            @else
                                {{ $item->guardian ?? 'N/A' }}
                            @endif
                        </td>
                        @foreach($columns as $column)
                            @php
                                $value = $item->{$column};
                                $isBoolean = in_array($column, ['checked_out', 'notified_timeout', 'isfreeze']);
                                $isCurrency = in_array($column, ['durationsubtotal', 'socksprice', 'subtotal', 'lne_xtra_chrg']);
                            @endphp

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($isBoolean)
                                    @if($value)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">No</span>
                                    @endif
                                @elseif($isCurrency)
                                    ₱{{ number_format($value, 2) }}
                                @elseif(is_null($value))
                                    N/A
                                @elseif($column === 'durationhours')
                                    {{ $value == '5' ? 'Unlimited' : $value }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 2 }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No order items found.
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const topScrollbar = document.getElementById('topScrollbar');
        const tableContainer = document.getElementById('tableContainer');

        if (topScrollbar && tableContainer) {
            let isSyncing = false;
            let isDragging = false;
            let startX = 0;
            let scrollLeft = 0;

            // Drag to scroll functionality
            topScrollbar.addEventListener('mousedown', function(e) {
                isDragging = true;
                topScrollbar.style.cursor = 'grabbing';
                startX = e.pageX - topScrollbar.offsetLeft;
                scrollLeft = topScrollbar.scrollLeft;
            });

            document.addEventListener('mouseup', function() {
                isDragging = false;
                topScrollbar.style.cursor = 'grab';
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                e.preventDefault();
                const x = e.pageX - topScrollbar.offsetLeft;
                const walk = (x - startX) * 2;
                topScrollbar.scrollLeft = scrollLeft - walk;
            });

            // Sync scroll position from top to table
            topScrollbar.addEventListener('scroll', function() {
                if (!isSyncing) {
                    isSyncing = true;
                    tableContainer.scrollLeft = topScrollbar.scrollLeft;
                    // Small delay to prevent infinite loop
                    setTimeout(() => { isSyncing = false; }, 10);
                }
            });

            // Sync scroll position from table to top
            tableContainer.addEventListener('scroll', function() {
                if (!isSyncing) {
                    isSyncing = true;
                    topScrollbar.scrollLeft = tableContainer.scrollLeft;
                    setTimeout(() => { isSyncing = false; }, 10);
                }
            });

            // Sync initial widths
            const table = tableContainer.querySelector('table');
            if (table) {
                topScrollbar.firstElementChild.style.width = table.offsetWidth + 'px';
            }
        }
    });
</script>
