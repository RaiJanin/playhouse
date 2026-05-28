# WebSocket Refactoring Documentation for Monitoring Module (Laravel Reverb)

## Overview

This document outlines the steps to replace the 5-second polling mechanism in the Playhouse Monitoring module with real-time WebSocket updates using Laravel Reverb.

## Current Implementation

- **Route**: `Route::get('/monitoring')` → `pages.playhouse-monitoring`
- **View**: `resources/views/pages/playhouse-monitoring.blade.php`
- **JS Module**: `resources/js/modules/playhouse-monitoring.js`
- **API**: `/api/get-inhouse` (`MimoAdminController::monitoring`)
- **Polling**: Every 5 seconds via `setInterval`

---

## Step 1: Install Laravel Reverb

```bash
composer require laravel/reverb
npm install laravel-echo pusher-js
php artisan vendor:publish --tag=reverb-server --tag=reverb-config
```

---

## Step 2: Configure Reverb

### Run Reverb Installation
```bash
php artisan reverb:install
```

### `.env` Configuration (Development)
```env
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=sync

REVERB_APP_ID=playhouse
REVERB_APP_KEY=your-reverb-key
REVERB_APP_SECRET=your-reverb-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### `.env` Configuration (Production)
```env
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=redis

REVERB_APP_ID=playhouse
REVERB_APP_KEY=your-production-key
REVERB_APP_SECRET=your-production-secret
REVERB_HOST=ws.yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### `config/broadcasting.php` (Reverb Connection)
```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT') ?: 443,
        'scheme' => env('REVERB_SCHEME') ?: 'https',
        'useTLS' => env('REVERB_SCHEME') === 'https',
    ],
    'guards' => [],
],
```

### `config/reverb.php`
```php
'server' => [
    'host' => env('REVERB_HOST', '0.0.0.0'),
    'port' => env('REVERB_PORT', 8080),
],
```

---

## Step 3: Create Event Class

Create `app/Events/MonitoringDataUpdated.php`:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringDataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    public function __construct(array $items)
    {
        $this->data = $items;
    }

    public function broadcastOn()
    {
        return new Channel('monitoring');
    }

    public function broadcastAs()
    {
        return 'monitoring-updated';
    }

    public function broadcastWith()
    {
        return ['data' => $this->data];
    }
}
```

---

## Step 4: Create Service to Broadcast Data

Create `app/Services/MonitoringService.php`:

```php
<?php

namespace App\Services;

use App\Events\MonitoringDataUpdated;
use App\Models\OrderItems;
use Carbon\Carbon;

class MonitoringService
{
    public function broadcastUpdated(): void
    {
        $items = OrderItems::query()
            ->whereNotNull('ckin')
            ->whereNull('ckout')
            ->select([
                'id',
                'd_code_child',
                'ord_code_ph',
                'ckin',
                'ckout',
                'durationhours',
                'qr_child',
                'qr_guardian',
            ])
            ->with([
                'child:d_code_c,firstname,lastname',
                'order:ord_code_ph,d_code',
                'order.parentPl:d_code,d_name',
            ])
            ->get()
            ->map(function ($item) {
                $now = Carbon::now();

                if ($item->durationhours === 5) {
                    $item->remainmins = "unlimited";
                    $item->status = "normal";
                } elseif (!empty($item->ckin) && empty($item->ckout)) {
                    $ckin = Carbon::parse($item->ckin);
                    $elapsedMinutes = $ckin->diffInMinutes($now);
                    $totalMinutes = $item->durationhours * 60;
                    $remainingMinutes = max(0, $totalMinutes - $elapsedMinutes);
                    $hours = floor($remainingMinutes / 60);
                    $minutes = $remainingMinutes % 60;
                    $item->remainmins = "{$hours}hr {$minutes}min";
                    $item->status = "normal";
                } else {
                    $item->remainmins = "0hr 0min";
                    $item->status = "due";
                }

                if (!$item->ckout) {
                    if ($now->copy()->subMinutes(30) > $item->ckin) {
                        $item->status = "overdue";
                    } elseif ($now->copy() >= $item->ckin && $now->copy()->subMinutes(30) <= $item->ckin) {
                        $item->status = "due";
                    }
                }

                return [
                    'childName' => $item->child
                        ? "{$item->child->firstname} {$item->child->lastname}"
                        : "N/A",
                    'parentName' => $item->order->parent_pl->d_name
                        ?? ($item->guardian ?? "N/A"),
                    'durationHours' => !$item->durationhours
                        ? "N/A"
                        : ($item->durationhours == 5 ? "Unlimited" : "{$item->durationhours}hr"),
                    'checkedIn' => $item->ckin
                        ? Carbon::parse($item->ckin)->diffForHumans()
                        : null,
                    'checkedOut' => $item->ckout,
                    'remainingTime' => $item->remainmins,
                    'checkStatus' => $item->status,
                    'orderCode' => $item->ord_code_ph,
                    'qrChild' => $item->qr_child,
                    'qrGuardian' => $item->qr_guardian,
                ];
            })
            ->toArray();

        MonitoringDataUpdated::dispatch($items);
    }
}
```

---

## Step 5: Update Controllers to Trigger Broadcasting

### In `PlayHouseController.php`

```php
use App\Services\MonitoringService;

// In checkInSource method - after successful check-in:
app(MonitoringService::class)->broadcastUpdated();

// In checkOut method - after successful checkout:
app(MonitoringService::class)->broadcastUpdated();
```

---

## Step 6: Update Bootstrap.js for Reverb

Update `resources/js/bootstrap.js`:

```js
import axios from 'axios';
import Echo from 'laravel-echo';
import { defineConfig } from 'vite';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wsPath: '/app/playhouse',
    wssPath: '/app/playhouse',
    encrypted: import.meta.env.VITE_REVERB_SCHEME === 'https',
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    disableStats: true,
    enableTransports: ['ws', 'wss'],
});
```

---

## Step 7: Refactor JavaScript Module

Replace `resources/js/modules/playhouse-monitoring.js`:

```js
import '../bootstrap.js'
import '../config/global.js'
import { showConsole } from '../config/debug.js'
import { renderPagination } from "../utilities/pagination.js"
import { emptyStateTable, tableSkeleton } from '../components/tablePlaceholders.js'

let searchIn, searchState = '', meta = null, dataRowsBody, pageState = 1;

const api = window.axios;

const parseItem = (item) => ({
    childName: item.childName || "N/A",
    parentName: item.parentName || "N/A",
    durationHours: item.durationHours || "N/A",
    checkedIn: item.checkedIn
        ? item.checkedIn
        : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-200 text-gray-800">Not started</span>',
    checkedOut: !item.checkedIn && !item.checkedOut
        ? '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-orange-200 text-gray-800">Not started</span>'
        : (item.checkedIn && !item.checkedOut
            ? '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-200 text-gray-800">Active</span>'
            : `${item.checkedOut}`),
    remainingTime: item.remainingTime === "done"
        ? '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Checked out</span>'
        : (item.remainingTime === "unlimited"
            ? '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-blue-200 text-gray-800">Unlimited</span>'
            : item.remainingTime),
    checkStatus: item.checkStatus === 'overdue'
        ? '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800">Overdue</span>'
        : (item.checkStatus === 'due'
            ? '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Due</span>'
            : '<span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-gray-800">Normal</span>'),
    orderCode: item.orderCode,
    qrChild: item.qrChild,
    qrGuardian: item.qrGuardian,
});

const tableRow = (item) => `
    <tr class="data-row cursor-pointer hover:bg-gray-100 transition">
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-white z-10">${item.childName}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 sticky left-0 bg-white z-10">${item.parentName}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.orderCode}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.qrChild}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.qrGuardian}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.durationHours}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.checkedIn}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.remainingTime}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.checkStatus}</td>
    </tr>
`;

const renderTableRows = (items) => {
    dataRowsBody.innerHTML = items.length === 0
        ? emptyStateTable()
        : items.map(item => tableRow(parseItem(item))).join('');
};

const displayData = async (page) => {
    pageState = page;
    try {
        const response = await api.get(`/api/get-inhouse?search=${searchState}&page=${page}`);
        if (response.data.success) {
            meta = response.data.meta;
            renderTableRows(meta.data);
            renderPagination(meta, async (p) => await displayData(p), true);
        }
    } catch (err) {
        showConsole("error", err);
        App.component.criticalAlert("Load error");
    }
};

const handleSearch = async () => {
    if (!searchIn.value.trim()) return;
    searchState = searchIn.value.trim();
    await displayData(1);
};

const init = async () => {
    searchIn = document.getElementById('search-it');
    const searchBtn = document.getElementById('filter-btn');
    dataRowsBody = document.getElementById("data-rows");

    await displayData(1);

    // Reverb WebSocket subscription
    window.Echo.channel('monitoring')
        .listen('monitoring-updated', (e) => {
            showConsole('log', 'WebSocket update received', e);
            if (searchState) return;
            renderTableRows(e.data);
        });

    searchBtn?.addEventListener('click', handleSearch);
};

document.addEventListener('DOMContentLoaded', init);
```

---

## Step 8: Configure Vite

Update `resources/views/pages/playhouse-monitoring.blade.php` for Vite environment variables:

```html
@vite(['resources/css/app.css', 'resources/js/modules/playhouse-monitoring.js'])
```

Add to `vite.config.js`:
```js
export default defineConfig({
    // ...
    define: {
        'process.env': process.env,
    },
});
```

---

## Step 9: Start Reverb Server

### Development
```bash
php artisan reverb:start
```

### Production (with Supervisor)
```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

---

## Step 10: Queue Workers (Required for Broadcasting)

### Development
```bash
php artisan queue:work
```

### Production (Supervisor config)
```ini
[program:laravel-reverb]
command=php /path/to/playhouse/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
```

---

## Key Changes Summary

| Aspect | Before | After |
|--------|--------|-------|
| Data fetching | HTTP polling every 5s | WebSocket push via Reverb |
| Server load | Constant requests | Event-driven only on changes |
| Latency | Up to 5 seconds delay | Real-time updates |
| Dependencies | None | `laravel/reverb` package |
| Code removed | `startLoop()`, `stopLoop()`, `refreshInterval` | WebSocket listener |

---

## Production SSL Configuration

### For HTTPS WebSocket (Reverb behind reverse proxy)

Update `.env`:
```env
REVERB_SCHEME=https
REVERB_HOST=ws.yourdomain.com
REVERB_PORT=443
```

---

## Testing

1. Start Reverb server: `php artisan reverb:start`
2. Start queue worker: `php artisan queue:work`
3. Navigate to `/monitoring`
4. Perform check-in/check-out operations
5. Verify updates appear in real-time without page refresh

Check browser console for WebSocket connection logs.