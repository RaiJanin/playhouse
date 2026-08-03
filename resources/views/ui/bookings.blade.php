<div class="flex justify-between items-center p-4">
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
            <label for="sort" class="block text-sm font-semibold text-gray-700 mb-1">Sort by</label>
            <select 
                id="sort" 
                name="sort" 
                class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"
            >
                <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Date Booked</option>
                <option value="ckin" {{ request('sort') === 'ckin' ? 'selected' : '' }}>Checked-In</option>
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
        <div class="flex flex-col">
            <label for="search" class="block text-sm font-semibold text-gray-700 mb-1">Search Booking/s</label>
            <div class="relative group">
                <input 
                    type="text" 
                    id="search" 
                    name="search"
                    class="bg-white w-full px-4 py-2 border border-[var(--color-primary)] shadow rounded-xl font-semibold focus:outline-none focus:border-[var(--color-primary-lighter)] focus:shadow-none transition-all duration-300"
                    value="{{ request('search') }}"
                >
                <div class="absolute left-0 -top-12 hidden group-hover:block bg-gray-800 text-white text-xs px-3 py-2 rounded-lg shadow-lg whitespace-nowrap z-10">
                    Search by booking number, child's name, or QR codes
                </div>
            </div>
        </div>
        <button 
            type="submit" 
            class="px-4 py-2 bg-[var(--color-primary)] text-white font-semibold rounded-xl hover:bg-[var(--color-primary-light)] transition-all duration-300"
        >
            Filter
        </button>
        <a
            href="{{ route('playhouse.bookings', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
            class="px-4 py-2 bg-gray-500 text-white font-semibold rounded-xl hover:bg-gray-600 transition-all duration-300 disabled:cursor-not-allowed disabled:bg-gray-400"
        >
            Today
        </a>
    </form>
    <a
        href="{{ route('playhouse.bookings', [
                        'start_date' => request()->query('start_date') ?? '',
                        'end_date' => request()->query('end_date') ?? '',
                        'status' => request()->query('status') ?? ''
                    ])
                }}"
        class="px-4 h-10 py-2 bg-[var(--color-primary-full-dark)] text-white font-semibold rounded-xl hover:bg-[var(--color-primary-mid-dark)] transition-all duration-300 disabled:cursor-not-allowed disabled:bg-gray-400"
    >
        <i class="fa-solid fa-rotate"></i> Refresh
    </a>
</div>

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
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
                    Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Checked In
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Checked Out
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Remaining Time
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($orderItems as $item)
                <tr class="data-row cursor-pointer hover:bg-gray-100 transition"
                    data-id="{{ $item->id }}"
                    data-child="{{ $item->child->firstname.' '.$item->child->lastname}}"
                    data-qr-child="{{ $item->qr_child }}"
                    data-qr-guardian="{{ $item->qr_guardian }}"
                    data-book-id="{{ $item->ord_code_ph }}"
                >
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-white z-10">
                        @if($item->child)
                            {{ $item->child->firstname }} {{ $item->child->lastname }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-white z-10" style="left: 120px;">
                        @if($item->order->parentPl)
                            {{ $item->order->parentPl->d_name }}
                        @else
                            {{ $item->guardian ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $item->ord_code_ph }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $item->qr_child ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $item->qr_guardain ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if(!$item->durationhours)
                            {{ 'N/A' }}
                        @elseif($item->durationhours == 5)
                            {{ 'Unlimited' }}
                        @else
                            {{ $item->durationhours . 'hr' }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($item->status === 'normal')
                            {!!
                                '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Normal</span>'
                            !!}
                        @elseif ($item->status === 'due')
                            {!!
                                '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Due</span>'
                            !!}
                        @elseif ($item->status === 'overdue')
                            {!!
                                '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">Overdue</span>'
                            !!}
                        @elseif ($item->status === 'booked')
                            {!!
                                '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Booked</span>'
                            !!}
                        @elseif ($item->status === 'done')
                            {!!
                                '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Ready to Pay</span>'
                            !!}
                        @elseif ($item->status === 'paid')
                            {!!
                                '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>'
                            !!}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {!! $item->ckin ? 
                            \Carbon\Carbon::parse($item->ckin)->format('M d, Y h:i A') 
                            : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-200 text-gray-800">Not started</span>' 
                        !!}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if(empty($item->ckin) && empty($item->ckout))
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-200 text-gray-800">Not started</span>
                        @elseif(!empty($item->ckin) && empty($item->ckout))
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-200 text-gray-800">Active</span>
                        @else
                            {{ \Carbon\Carbon::parse($item->ckout)->format('M d, Y h:i A') ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($item->remainmins === 'done')
                            {!! 
                                '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Checked out</span>' 
                            !!}
                        @elseif($item->remainmins === 'unlimited')
                            {!! 
                                '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-200 text-gray-800">Unlimited</span>' 
                            !!}
                        @else
                            {{ $item->remainmins }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.data-row').forEach(row => {
            row.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('open-order-modal', { 
                    detail: {
                        id: row.dataset.id,
                        child: row.dataset.child,
                        qrChild: row.dataset.qrChild,
                        qrGuardian: row.dataset.qrGuardian,
                        bookId: row.dataset.bookId,
                    }
                }))
            })
        })
    })
</script>
