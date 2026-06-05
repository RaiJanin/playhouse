{{-- Top --}}
<div class="grid grid-cols-3 gap-8 items-start">
    {{-- Left side --}}
    <div class="col-span-2 flex flex-wrap gap-2 self-start">
        <x-status-card
            title="In house guardians"
            :value=" $statusMonitor['in_house_kids'] ?? 0 "
            width="min-w-[200px] max-w-[300px]"
            bg="bg-[var(--color-primary-mid-dark)]"
            icon="fa-solid fa-users-between-lines"
        />
        <x-status-card
            title="In house kids"
            :value=" $statusMonitor['in_house_guardians'] ?? 0 "
            width="min-w-[200px] max-w-[300px]"
            bg="bg-[var(--color-primary-mid-dark)]"
            icon="fa-solid fa-child-reaching"
        />
        <x-status-card
            title="Others Today"
            :value=" $statusMonitor['today_others'] ?? 0 "
            width="min-w-[200px] max-w-[300px]"
            bg="bg-[var(--color-third-full-dark)]"
            icon="fa-solid fa-hand-holding-droplet"
        />
        <x-status-card
            title="Total Counts today"
            :value=" ($statusMonitor['in_house_guardians'] ?? 0) + ($statusMonitor['in_house_kids'] ?? 0) "
            bg="bg-[var(--color-primary-mid-dark)]"
            width="min-w-[200px] max-w-[300px]"
            icon="fa-solid fa-child-reaching"
        />
        <x-status-card
            title="Current Overdue"
            :value=" $statusMonitor['overdue'] ?? 0 "
            width="min-w-[300px] max-w-[400px]"
            bg="bg-red-800"
            icon="fa-solid fa-stopwatch"
        />
        <x-status-card
            title="Sales Today"
            :value=" $statusMonitor['today_sales'] ?? '0.00' "
            width="min-w-[200px] max-w-[300px]"
            bg="bg-cyan-700"
            icon="fa-solid fa-coins"
        />
        <x-status-card
            title="Child Reservations"
            :value=" $statusMonitor['today_reserves'] ?? 0 "
            width="min-w-[200px] max-w-[300px]"
            bg="bg-[var(--color-third-full-dark)]"
            icon="fa-solid fa-clipboard-user"
        />
        <x-status-card
            title="Party Package Reservation"
            :value=" $statusMonitor['party_pckge_rsrv'] ?? 0 "
            width="min-w-[200px] max-w-[300px]"
            bg="bg-[var(--color-primary-full-dark)]"
            icon="fa-solid fa-democrat"
        />
        <x-status-card
            title="Cash Amount"
            :value=" $statusMonitor['cash_amount'] ?? 0 "
            width="min-w-[200px] max-w-[300px]"
            bg="bg-[var(--color-primary-full-dark)]"
            icon="fa-solid fa-democrat"
        />
        <x-status-card
            title="Total unpaid amount"
            :value=" $statusMonitor['total_unpaid'] ?? 0 "
            width="min-w-[300px] max-w-[400px]"
            bg="bg-red-800"
            icon="fa-solid fa-coins"
        />
    </div>

    {{-- Right side --}}
    <div class="flex flex-wrap gap-2 text-sm">
        @foreach(\App\Enums\MimoReport::cases() as $report)
            <x-reports-link-btn
                :title="$report->label()"
                :link="route('reports.index', ['mimo_report' => $report->value])"
            />
        @endforeach
    </div>
</div>
