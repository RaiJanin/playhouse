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
        {{-- <x-status-card
            title="Total kids"
            :value=" $statusMonitor['total_kids'] ?? 0 "
            width="min-w-[150px] max-w-[200px]"
            bg="bg-[var(--color-third-full-dark)]"
            icon="fa-solid fa-children"
        /> --}}
        {{-- <x-status-card
            title="Total guardians"
            :value=" $statusMonitor['total_guardians'] ?? 0 "
            width="min-w-[150px] max-w-[200px]"
            bg="bg-[var(--color-third-full-dark)]"
            icon="fa-solid fa-users"
        /> --}}
        {{-- <x-status-card
            title="For Checkouts"
            :value=" $statusMonitor['for_checkouts'] ?? 0 "
            width="min-w-[150px] max-w-[200px]"
            bg="bg-orange-700"
            icon="fa-solid fa-person-hiking"
        /> --}}
        {{-- <x-status-card
            title="Total Logged in"
            :value=" $statusMonitor['total_lgin'] ?? 0 "
            width="min-w-[150px] max-w-[200px]"
            bg="bg-[var(--color-third-full-dark)]"
            icon="fa-solid fa-user-clock"
        /> --}}
        {{-- <x-status-card
            title="Total Checkouts"
            :value=" $statusMonitor['total-ckouts'] ?? 0 "
            width="min-w-[150px] max-w-[200px]"
            bg="bg-[var(--color-third-full-dark)]"
            icon="fa-solid fa-right-from-bracket"
        /> --}}
        {{-- <x-status-card
            title="Number or Items sold"
            :value=" $statusMonitor['items_sold'] ?? '0.00' "
            width="min-w-[150px] max-w-[200px]"
            bg="bg-amber-600"
            icon="fa-solid fa-cart-plus"
        /> --}}
        {{-- <x-status-card
            title="Number of Socks Sold"
            :value=" $statusMonitor['socks_sold'] ?? 0 "
            width="min-w-[150px] max-w-[200px]"
            bg="bg-amber-600"
            icon="fa-solid fa-socks"
        /> --}}
        {{-- <div class="flex-1 min-w-full grid grid-cols-1 mb-4">
            <div class="flex flex-wrap gap-2">
                <x-status-card
                    title="Total Playhouse Sales"
                    :value=" $statusMonitor['playhouse_sales'] ?? '0.00' "
                    bg="bg-cyan-700"
                    icon="fa-solid fa-coins"
                />
                <x-status-card
                    title="Total Item Sales"
                    :value=" $statusMonitor['item_sales'] ?? '0.00' "
                    bg="bg-[var(--color-third-full-dark)]"
                    icon="fa-solid fa-coins"
                />
                <x-status-card
                    title="Total unpaid amount"
                    :value=" $statusMonitor['total_unpaid'] ?? 0 "
                    bg="bg-red-800"
                    icon="fa-solid fa-coins"
                />
            </div>
        </div> --}}
    </div>

    {{-- Right side --}}
    <div class="flex flex-wrap gap-2 text-sm">
        <x-reports-link-btn
            title="Outlet Sales Report"
            :link="'#'"
        />
        <x-reports-link-btn
            title="Cashier's Report"
            :link="'#'"
        />
        <x-reports-link-btn
            title="Sales Report by Hour"
            :link="'#'"
        />
        <x-reports-link-btn
            title="Sales Report by Item"
            :link="'#'"
        />
        {{-- <x-reports-link-btn
            title="Stock Adjustment Report"
            :link="'#'"
        /> --}}
        {{-- <x-reports-link-btn
            title="Stock Transfer Report"
            :link="'#'"
        /> --}}
        {{-- <x-reports-link-btn
            title="Stock Issuance Report"
            :link="'#'"
        /> --}}
        {{-- <x-reports-link-btn
            title="Sales Report by Staff"
            :link="'#'"
        /> --}}
        {{-- <x-reports-link-btn
            title="Direct Purchase Report"
            :link="'#'"
        /> --}}
        {{-- <x-reports-link-btn
            title="Inventory Valuation"
            :link="'#'"
        /> --}}
    </div>
</div>