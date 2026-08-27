<?php
require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

function calculateStatus($ckin, $durationHours, $bkin = null, $bkout = null, $ckout = null, $isPaid = false, $now = null) {
    if (!$now) {
        $now = Carbon::parse('2026-08-27 10:20:00');
    } else if (is_string($now)) {
        $now = Carbon::parse($now);
    }
    
    if (!empty($ckout)) {
        return [
            'remainmins' => 'done',
            'status' => $isPaid ? 'completed' : 'done'
        ];
    }
    
    if (empty($ckin)) {
        return [
            'remainmins' => '0hr 0min',
            'status' => $isPaid ? 'paid' : 'booked'
        ];
    }
    
    if ($durationHours == 5) {
        return [
            'remainmins' => 'unlimited',
            'status' => 'normal'
        ];
    }
    
    $ckin = Carbon::parse($ckin);
    $elapsedMinutes = $ckin->diffInMinutes($now);
    
    $breakMinutes = 0;
    if ($bkin) {
        if ($bkout) {
            $breakMinutes += Carbon::parse($bkin)->diffInMinutes(Carbon::parse($bkout));
        } else {
            $breakMinutes += Carbon::parse($bkin)->diffInMinutes($now);
        }
    }
    
    $elapsedMinutes = max(0, $elapsedMinutes - $breakMinutes);
    $totalMinutes = $durationHours * 60;
    $remainingMinutes = max(0, $totalMinutes - $elapsedMinutes);
    
    $hours = floor($remainingMinutes / 60);
    $minutes = $remainingMinutes % 60;
    $remainmins = "{$hours}hr {$minutes}min";
    
    $isOnBreak = !empty($bkin) && empty($bkout);
    $due = $ckin->copy()->addHours($durationHours)->addMinutes($breakMinutes);
    
    if ($isOnBreak) {
        $status = "normal";
    } else if ($now->lt($due)) {
        $status = "normal";
    } else {
        $lateMinutes = $due->diffInMinutes($now);
        $status = $lateMinutes <= 5 ? "due" : "overdue";
    }
    
    return [
        'remainmins' => $remainmins,
        'status' => $status,
        'elapsed' => $elapsedMinutes,
        'break' => $breakMinutes,
        'remaining' => $remainingMinutes,
        'late' => !$isOnBreak && $now->gte($due) ? $due->diffInMinutes($now) : 0
    ];
}

echo "=== BREAK IN/OUT STATUS TESTS ===\n\n";

// Scenario 1: Normal session, 30 min elapsed, 30 min remaining
$test1 = calculateStatus('2026-08-27 09:50:00', 1);
echo "Test 1: Normal session (30min elapsed)\n";
echo "  Status: {$test1['status']} (expected: normal)\n";
echo "  Remaining: {$test1['remainmins']} (expected: 0hr 30min)\n";
echo "  Result: " . ($test1['status'] === 'normal' && $test1['remaining'] == 30 ? "PASS" : "FAIL") . "\n\n";

// Scenario 2: On break, 45 min elapsed, 15 min break, 30 min effective, 30 min remaining
$test2 = calculateStatus(
    '2026-08-27 09:35:00',
    1,
    '2026-08-27 10:05:00',
    null
);
echo "Test 2: On break (45min elapsed, 15min break)\n";
echo "  Status: {$test2['status']} (expected: normal)\n";
echo "  Remaining: {$test2['remainmins']} (expected: 0hr 30min)\n";
echo "  Result: " . ($test2['status'] === 'normal' && $test2['remaining'] == 30 ? "PASS" : "FAIL") . "\n\n";

// Scenario 3: Break completed, 50 min elapsed, 15 min break, 35 min effective, 25 min remaining
$test3 = calculateStatus(
    '2026-08-27 09:30:00',
    1,
    '2026-08-27 10:00:00',
    '2026-08-27 10:15:00'
);
echo "Test 3: Break completed (50min elapsed, 15min break)\n";
echo "  Status: {$test3['status']} (expected: normal)\n";
echo "  Remaining: {$test3['remainmins']} (expected: 0hr 25min)\n";
echo "  Result: " . ($test3['status'] === 'normal' && $test3['remaining'] == 25 ? "PASS" : "FAIL") . "\n\n";

// Scenario 4: Break completed, 65 min elapsed, 15 min break, 50 min effective, 10 min remaining
// Due = 09:15 + 1:00 + 0:15 = 10:30. Now = 10:20. Not late yet.
$test4 = calculateStatus(
    '2026-08-27 09:15:00',
    1,
    '2026-08-27 10:00:00',
    '2026-08-27 10:15:00'
);
echo "Test 4: Normal after break (65min elapsed, 15min break)\n";
echo "  Status: {$test4['status']} (expected: normal)\n";
echo "  Remaining: {$test4['remainmins']} (expected: 0hr 10min)\n";
echo "  Late: {$test4['late']} min\n";
echo "  Result: " . ($test4['status'] === 'normal' && $test4['remaining'] == 10 ? "PASS" : "FAIL") . "\n\n";

// Scenario 5: Due after break (within 5 min grace)
// ckin = 09:15, break = 15 min, due = 10:30, now = 10:33 (3 min late)
// elapsed = 78 min, break = 15, effective = 63, remaining = 0
// Actually let's use: ckin = 09:15, now = 10:18, break 10:00-10:15
// elapsed = 63, break = 15, effective = 48, remaining = 12
// due = 09:15 + 1:00 + 0:15 = 10:30. now = 10:18. Not late yet -> normal
// To be due, we need now > 10:30. Let's use now = 10:33.
// elapsed = 78, break = 15, effective = 63, remaining = 0
// due = 10:30, late = 3 -> due
$test5 = calculateStatus(
    '2026-08-27 09:15:00',
    1,
    '2026-08-27 10:00:00',
    '2026-08-27 10:15:00',
    null,
    false,
    '2026-08-27 10:33:00'
);
echo "Test 5: Due after break (78min elapsed, 15min break)\n";
echo "  Status: {$test5['status']} (expected: due)\n";
echo "  Remaining: {$test5['remainmins']} (expected: 0hr 0min)\n";
echo "  Late: {$test5['late']} min\n";
echo "  Result: " . ($test5['status'] === 'due' && $test5['remaining'] == 0 ? "PASS" : "FAIL") . "\n\n";

// Scenario 6: Overdue after break (10 min late)
// ckin = 09:15, break = 15 min, due = 10:30, now = 10:40 (10 min late)
// elapsed = 85, break = 15, effective = 70, remaining = 0
// late = 10 -> overdue
$test6 = calculateStatus(
    '2026-08-27 09:15:00',
    1,
    '2026-08-27 10:00:00',
    '2026-08-27 10:15:00',
    null,
    false,
    '2026-08-27 10:40:00'
);
echo "Test 6: Overdue after break (85min elapsed, 15min break)\n";
echo "  Status: {$test6['status']} (expected: overdue)\n";
echo "  Remaining: {$test6['remainmins']} (expected: 0hr 0min)\n";
echo "  Late: {$test6['late']} min\n";
echo "  Result: " . ($test6['status'] === 'overdue' && $test6['remaining'] == 0 ? "PASS" : "FAIL") . "\n\n";

// Scenario 7: On break but past original due time
// ckin = 09:35, break started at 10:30, now = 10:35
// Original due = 10:35, but break extends it to 10:50
// Should still be normal, remaining = 5 min
$test7 = calculateStatus(
    '2026-08-27 09:35:00',
    1,
    '2026-08-27 10:30:00',
    null,
    null,
    false,
    '2026-08-27 10:35:00'
);
echo "Test 7: On break past original due time\n";
echo "  Status: {$test7['status']} (expected: normal)\n";
echo "  Remaining: {$test7['remainmins']} (expected: 0hr 5min)\n";
echo "  Result: " . ($test7['status'] === 'normal' && $test7['remaining'] == 5 ? "PASS" : "FAIL") . "\n\n";

echo "=== TESTS COMPLETE ===\n";
