# WebSocket Production Configuration Guide

## Overview
This document details production-ready WebSocket configuration options for the Monitoring module.

---

## Option 1: Pusher (Recommended for Small-Medium Scale)

### Installation
```bash
composer require pusher/pusher-php-server
npm install laravel-echo pusher-js
```

### `.env` Configuration
```env
BROADCAST_DRIVER=pusher
QUEUE_CONNECTION=redis

PUSHER_APP_ID=your-production-app-id
PUSHER_APP_KEY=your-production-key
PUSHER_APP_SECRET=your-production-secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_HOST=
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### `config/broadcasting.php`
```php
'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'useTLS' => true,
    ],
],
```

### Pros
- Managed service (no server maintenance)
- Automatic SSL/TLS
- Global CDN
- Built-in presence channels

### Cons
- Paid service at scale
- External dependency
- Message limits (100k/day free tier)

---

## Option 2: Redis + Socket.IO (Recommended for Full Control)

### Installation
```bash
composer require predis/predis
npm install laravel-echo socket.io-client
```

### `.env` Configuration
```env
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

LARAVEL_WEBSOCKET_PORT=6001
LARAVEL_WEBSOCKET_HOST=127.0.0.1
```

### `config/broadcasting.php`
```php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
],

'socketio' => [
    'driver' => 'pusher',
    'key' => null,
    'secret' => null,
    'app_id' => null,
    'options' => [
        'host' => env('LARAVEL_WEBSOCKET_HOST', '127.0.0.1'),
        'port' => env('LARAVEL_WEBSOCKET_PORT', 6001),
        'scheme' => 'http',
        'encrypted' => false,
    ],
],
```

### Install Laravel WebSockets Package
```bash
composer require beyondcode/laravel-websockets
```

### `config/websockets.php` (publish via vendor:publish)
```php
'statistics' => [
    'model' => \Beyondco\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,
    'interval_in_seconds' => 60,
],

'apps' => [
    [
        'id' => env('PUSHER_APP_ID'),
        'name' => 'Playhouse Monitoring',
        'host' => env('LARAVEL_WEBSOCKET_HOST'),
        'port' => env('LARAVEL_WEBSOCKET_PORT', 6001),
        'scheme' => 'http',
        'encrypted' => false,
        'broadcast' => true,
        'statistics' => true,
    ],
],
```

### Bootstrap.js for Socket.IO
```js
window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':6001',
    transports: ['websocket'],
    upgrade: false,
});
```

### Start WebSocket Server
```bash
php artisan websockets:serve
```

---

## Option 3: Laravel Echo Server (Alternative)

### Installation
```bash
npm install -g laravel-echo-server
```

### `laravel-echo-server.json`
```json
{
    "authHost": "http://localhost",
    "port": "6001",
    "protocol": "http",
    "sslKeyPath": "",
    "sslCertPath": "",
    "database": "redis",
    "databaseConfig": {
        "redis": { "port": "6379", "host": "127.0.0.1" }
    },
    "presence": {
        "updateEveryXSeconds": 10,
        "defaultRoom": "global"
    },
    "socketio": {
        "key": "{PUSHER_APP_KEY}",
        "namespace": "App.Events"
    }
}
```

### Start Server
```bash
laravel-echo-server start
```

---

## Queue Worker Configuration (All Options)

### Supervisor Configuration (`/etc/supervisor/conf.d/laravel-worker.conf`)
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/playhouse/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/worker.log
stopwaitsecs=60
```

### Commands
```bash
# Development
php artisan queue:work --tries=1

# Production
php artisan queue:work --daemon --processes=4 --sleep=3
```

---

## Load Balancing Considerations

### Multiple WebSocket Servers
Use Redis as the broadcast driver for horizontal scaling:
```env
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Sticky Sessions Required
Configure load balancer to maintain session affinity for WebSocket connections.

---

## SSL/TLS Configuration (Self-Hosted)

### Nginx Reverse Proxy
```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

upstream websockets {
    server 127.0.0.1:6001;
}

server {
    listen 443 ssl http2;
    server_name ws.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/ws.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ws.yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://websockets;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

### Bootstrap.js SSL
```js
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.PUSHER_APP_KEY,
    wsHost: 'ws.yourdomain.com',
    wsPort: 443,
    wssPort: 443,
    encrypted: true,
    forceTLS: true,
    enabledTransports: ['wss'],
});
```

---

## Monitoring & Debugging

### Check WebSocket Connections
```bash
# Logs
tail -f /var/log/worker.log

# Redis monitor
redis-cli monitor

# Connection stats (if using laravel-websockets)
php artisan websockets:dashboard
```

### Health Check Endpoint
Create `routes/api.php`:
```php
Route::get('/ws-health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'connections' => DB::table('websocket_statistics_entries')
            ->where('created_at', '>', now()->subMinutes(5))
            ->sum('peak_connection_count'),
    ]);
});
```

---

## Environment-Specific Configuration

### `.env.production`
```env
BROADCAST_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=redis.internal.domain
REDIS_PORT=6379
REDIS_PASSWORD="${REDIS_PROD_PASSWORD}"
```

### `.env.staging`
```env
BROADCAST_DRIVER=pusher
QUEUE_CONNECTION=sync
VITE_PUSHER_CLUSTER=eu
```

### `.env.development`
```env
BROADCAST_DRIVER=pusher
QUEUE_CONNECTION=sync
VITE_PUSHER_CLUSTER=mt1
```

---

## Performance Tuning

### Redis Optimization
```env
REDIS_QUEUE=default
REDIS_CACHE=default
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### Production Commands
```bash
# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Horizon for Redis queue monitoring (optional)
composer require laravel/horizon
php artisan horizon:install
```

---

## Security Considerations

### Private Channels (Optional)
If authentication is required:
```php
// routes/channels.php
Broadcast::channel('monitoring', function ($user) {
    return in_array($user->role, ['admin', 'staff']);
});
```

### JS Authentication
```js
window.Echo.private('monitoring')
    .listen('monitoring-updated', (e) => {
        // Handle private update
    });
```

---

## Deployment Checklist

- [ ] Set `BROADCAST_DRIVER` in production `.env`
- [ ] Configure Redis/Socket.io/Pusher credentials
- [ ] Run `php artisan config:cache`
- [ ] Start queue workers: `php artisan queue:work --daemon`
- [ ] Start WebSocket server (if self-hosted)
- [ ] Configure SSL certificates
- [ ] Set up monitoring alerts
- [ ] Test WebSocket connection in browser dev tools