<x-app-layout>
    <div class="flex-wrap gap-2">
        <div class="p-6">
            <x-slot name="header">
                <h2 class="font-semibold text-xl text-gray-50 leading-tight">
                    {{ $reportType->label() }}
                </h2>
            </x-slot>
        </div>

        <div class="p-6">
            <div class="bg-[var(--color-accent-mid-dark)] rounded-lg shadow-md p-6 mb-6">
                <form method="GET" action="{{ route('reports.index', $reportType->value) }}" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="mimo_report" value="{{ $reportType->value }}">

                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                            class="rounded-md border-gray-600 py-2 px-4 bg-[var(--color-primary-mid-dark)] text-gray-100 shadow-sm focus:border-[var(--color-primary-light)] focus:[var(--color-primary)]">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1">End Date</label>
                        <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                            class="rounded-md border-gray-600 py-2 px-4 bg-[var(--color-primary-mid-dark)] text-gray-100 shadow-sm focus:border-[var(--color-primary-light)] focus:[var(--color-primary)]">
                    </div>

                    @if($reportType->value === 'mR002')
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1">Guardian Filter (optional)</label>
                        <input type="text" name="guardian" value="{{ $filters['guardian'] }}" placeholder="Guardian name or code"
                            class="rounded-md border-gray-600 py-2 px-4 bg-[var(--color-primary-mid-dark)] text-gray-100 shadow-sm focus:border-[var(--color-primary-light)] focus:[var(--color-primary)] w-64">
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1">Per Page</label>
                        <select name="per_page" onchange="this.form.submit()"
                                class="rounded-md border-gray-600 py-2 px-4 bg-[var(--color-primary-mid-dark)] text-gray-100 shadow-sm">
                            @foreach([25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ $filters['per_page'] == $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                        class="rounded-md bg-[var(--color-primary)] px-4 py-2 font-semibold text-white shadow-sm hover:opacity-75 transition-all">
                        Apply Filter
                    </button>
                </form>
            </div>

            @php
                $start_date = request()->query('start_date') ?? now()->format('Y-m-d');
                $end_date = request()->query('end_date') ?? now()->format('Y-m-d');
            @endphp

            <div class="mb-6 flex flex-wrap gap-3">
                <a href="{{ route('reports.export', [$reportType->value, 'pdf', 'start_date' => $start_date, 'end_date' => $end_date]) }}"
                   class="rounded-md bg-[var(--color-accent-mid-dark)] px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:opacity-75 transition-all">
                    <i class="fa-solid fa-file-pdf mr-2"></i>Export PDF
                </a>
                {{-- <a href="{{ route('reports.export', [$reportType->value, 'csv']) }}"
                   class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700 transition-all">
                    <i class="fa-solid fa-file-csv mr-2"></i>Export CSV
                </a> --}}
                <a href="{{ route('dashboard') }}"
                   class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-75 transition-all">
                    Close
                </a>
            </div>

            @php
                $totals = $report['totals'];
            @endphp

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-[var(--color-primary-mid-dark)] rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-300 uppercase tracking-wider">Transactions</div>
                    <div class="text-2xl font-bold text-white mt-1">{{ number_format($totals['total_transactions']) }}</div>
                </div>
                <div class="bg-[var(--color-primary-mid-dark)] rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-300 uppercase tracking-wider">Total Sales</div>
                    <div class="text-2xl font-bold text-white mt-1">₱{{ number_format($totals['total_sales'], 2) }}</div>
                </div>
                <div class="bg-[var(--color-primary-mid-dark)] rounded-lg p-4 text-center">
                    <div class="text-xs text-gray-300 uppercase tracking-wider">Total Socks</div>
                    <div class="text-2xl font-bold text-white mt-1">{{ number_format( $totals['total_socks']) }}</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-[var(--color-primary-mid-dark)]">
                        <tr>
                            @php
                                $data = $report['data']->first();
                                if ($data) {
                                    foreach ($data as $key => $value) {
                                        $header = str_replace('_', ' ', ucfirst($key));
                                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-200 uppercase tracking-wider">' . $header . '</th>';
                                    }
                                }
                            @endphp
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 bg-[var(--color-primary-dark)]">
                        @forelse($report['data'] as $row)
                            <tr class="bg-(--color-third-full-dark) hover:bg-[var(--color-primary-mid-dark)] transition-colors">
                                @foreach($row as $key => $value)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-100">
                                        @if(is_iterable($value))
                                            {{ collect($value)->implode(', ') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="px-6 py-8 text-center text-gray-100">
                                    No data found for the selected date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-6">
                    {{ $report['data']->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
