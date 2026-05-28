<?php

namespace App\Services;

use App\Events\MonitoringUpdates;
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

        MonitoringUpdates::dispatch($items);
    }
}