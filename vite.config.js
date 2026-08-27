import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/dependencies.js",
                "resources/js/admin-app.js",
                "resources/js/modules/playhouseCheckout.js",
                "resources/js/modules/playhouseCheckinSource.js",
                "resources/js/modules/admin-panel-create.js",
                "resources/js/modules/playhouse-monitoring-polling.js",
                "resources/js/modules/playhouse-monitoring-websocket.js",
                "resources/js/modules/orderItemModal.js",
                "resources/js/modules/payments-list.js",
                "resources/js/modules/paymentModal.js",
                "resources/js/modules/bulkBookingActions.js",
                "resources/js/modules/addChildModal.js",
                "resources/js/modules/newCustomerModal.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
