<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunCommandsViaHttp extends Controller
{
    private function commandCall($command, &$logs)
    {
        try {
            Artisan::call($command);

            $logs[] = "✔ {$command} executed";
            $logs[] = trim(Artisan::output()) ?: '(no output)';

        } catch (\Throwable $e) {

            $logs[] = "✖ {$command} failed: " . $e->getMessage();
            $logs[] = "trace: {$e->getTraceAsString()}";
        }
    }
    private function initiateScheduler($request, callable $callback)
    {
        if ($request->query('key') !== env('SCHEDULER_KEY'))
        {
            abort(403, 'Unauthorized');
        }

        $logs = [];
        $start = now();

        $logs[] = "Scheduler started at: " . $start;

        try {
            $callback($logs);
        } catch (\Exception $e) {
            $logs[] = "Scheduler Fatal Error: " . $e->getMessage();
        }

        $end = now();
        $logs[] = "Finished at: " . $end;
        $logs[] = "Duration: " . $start->diffInSeconds($end) . " seconds";

        Log::info('Scheduler run', $logs);

        return response("<pre>" . implode("\n", $logs) . "</pre>");
    }

    public function recurring(Request $request)
    {
        return $this->initiateScheduler($request, function (&$logs) {
            $this->commandCall('otp:clean-expired', $logs);

            $this->commandCall('sms:timeout-reminder',$logs);

            $this->commandCall('sms:checkout-reminder',$logs);

            $this->commandCall('sms:overtime-reminder',$logs);
        });
    }

    public function scheduled(Request $request)
    {
        return $this->initiateScheduler($request, function (&$logs) {
            $this->commandCall('sms:process-scheduled-blasts',$logs);
        });
    }

    public function timeBased(Request $request)
    {
        return $this->initiateScheduler($request, function (&$logs) {
            $this->commandCall('sms:birthday-greetings',$logs);
        });
    }
}
