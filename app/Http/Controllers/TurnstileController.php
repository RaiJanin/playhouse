<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItems;
use App\Models\TurnstileLogs;
use App\Services\MonitoringService;
use Carbon\Carbon;

class TurnstileController extends Controller
{

    public function turnstileSrchPOST(Request $request)
    {
        $request->validate([
            'qr' => 'string|max:20',
            'status' => 'string|max:10',
            'time' => 'nullable|date'
        ]);

        $qrCode = $request->qr;
        $status = strtolower($request->status);
        $time = $request->time ? Carbon::parse($request->time) : now();

        try
        {
            DB::beginTransaction();
            $response = [];
            $hasSuccess = false;
            $validActions = [];

            if(!$status)
            {
                return response()->json([
                    'message' => "Status is required with values 'entrance' or 'exit'"
                ]);
            }
            if(!$qrCode)
            {
                return response()->json([
                    'message' => 'QRCode is required'
                ]);
            }

            $orderItems = OrderItems::with('child')->where(function ($query) use ($qrCode) {
                $query->where('qr_child', $qrCode)
                    ->orWhere('qr_guardian', $qrCode);
                })->where('checked_out', false)->get();

            if(!$orderItems)
            {
                return response()->json([
                    'message' => 'No reservations found or invalid qr code'
                ]);
            }

            foreach($orderItems as $orderItem)
            {
                $action = 'none';

                switch ($status)
                {
                    case 'entrance':
                        if ($orderItem->ckout)
                        {
                            $action = "<pre>Ignored(already checked-out)</pre>";
                            break;
                        }
                        // FIRST CHECK-IN
                        if (!$orderItem->ckin)
                        {
                            $orderItem->ckin = $time;
                            $orderItem->isfreeze = false;
                            $action = "<pre>Checked-in</pre>";
                            break;
                        }
                        // RESUME FROM FREEZE, and ensure all of the break times are empty
                        if ($orderItem->isfreeze && empty($orderItem->bkout4))
                        {
                            $orderItem->isfreeze = false;

                            if(!empty($orderItem->bkin) && empty($orderItem->bkout))
                            {
                                $orderItem->bkout = $time;
                                $action = "<pre>Resume from freeze</pre>";
                            }
                            else if(!empty($orderItem->bkout) && empty($orderItem->bkout1))
                            {
                                $orderItem->bkout1 = $time;
                                $action = "<pre>Resume from freeze (2nd time)</pre>";
                            }
                            else if(!empty($orderItem->bkout) && !empty($orderItem->bkout1) && empty($orderItem->bkout2))
                            {
                                $orderItem->bkout2 = $time;
                                $action = "<pre>Resume from freeze (3rd time)</pre>";
                            }
                            else if(!empty($orderItem->bkout) && !empty($orderItem->bkout1) && !empty($orderItem->bkout2) && empty($orderItem->bkout3))
                            {
                                $orderItem->bkout3 = $time;
                                $action = "<pre>Resume from freeze (4th time)</pre>";
                            }
                            else if(!empty($orderItem->bkout) && !empty($orderItem->bkout1) && !empty($orderItem->bkout2) && !empty($orderItem->bkout3) && empty($orderItem->bkout4))
                            {
                                $orderItem->bkout4 = $time;
                                $action = "<pre>Resume from freeze (5th time)</pre>";
                            }
                            break;
                        }
                        $action = "<pre>Ignored(already active)</pre>";
                        break;
                    case 'exit':
                        if(empty($orderItem->ckin))
                        {
                            $action = "<pre>Ignored(not checked-in, proceed to entrance)</pre>";
                            break;
                        }

                        if (empty($orderItem->bkin4))
                        {
                            $orderItem->isfreeze = true;

                            if(empty($orderItem->bkin))
                            {
                                $orderItem->bkin = $time;
                                $action = "<pre>Frozen</pre>";
                            }
                            else if(!empty($orderItem->bkin) && empty($orderItem->bkin1))
                            {
                                $orderItem->bkin1 = $time;
                                $action = "<pre>Frozen (2nd time)</pre>";
                            }
                            else if(!empty($orderItem->bkin) && !empty($orderItem->bkin1) && empty($orderItem->bkin2))
                            {
                                $orderItem->bkin2 = $time;
                                $action = "<pre>Frozen (3rd time)</pre>";
                            }
                            else if(!empty($orderItem->bkin) && !empty($orderItem->bkin1) && !empty($orderItem->bkin2) && empty($orderItem->bkin3))
                            {
                                $orderItem->bkin3 = $time;
                                $action = "<pre>Frozen (4th time)</pre>";
                            }
                            else if(!empty($orderItem->bkin) && !empty($orderItem->bkin1) && !empty($orderItem->bkin2) && !empty($orderItem->bkin3) && empty($orderItem->bkin4))
                            {
                                $orderItem->bkin4 = $time;
                                $action = "<pre>Frozen (5th time)</pre>";
                            }
                            break;
                        }
                        $action = "<pre>Ignored(all of the break times already filled)</pre>";
                        break;
                    default:
                        return response()->json([
                            'message' => "Status value is incorrect. Values must be 'entrance' or 'exit'"
                        ]);
                }

                $orderItem->save();

                $response[] = [
                    'order_item_id' => $orderItem->id,
                    'qr_child' => $orderItem->qr_child . ': ' . $orderItem->child->firstname.' '.$orderItem->child->lastname,
                    'action' => $action,
                    'timestamp' => $time->toDateTimeString(),
                ];

                $cleanAction = strip_tags($action);

                TurnstileLogs::create([
                    'datetime' => Carbon::now(),
                    'context' => "{$orderItem->child->firstname} {$orderItem->child->lastname}. QR:{$orderItem->qr_child} - {$cleanAction}"
                ]);

                if ($action !== "<pre>Ignored(already active)</pre>" && $action !== "<pre>Ignored(cannot freeze)</pre>") {
                    $hasSuccess = true;

                    $childName = $orderItem->child->firstname.' '.$orderItem->child->lastname ?? '';

                    $validActions[] = "{$childName} - {$cleanAction} (" . $time->format('h:i A') . ")";
                }

            }

            DB::commit();

            if(env('VITE_REALTIME_OPTION') === 'websocket')
            {
                app(MonitoringService::class)->broadcastUpdated();
            }

            return response()->json([
                'message' => 'Processed Successfully',
                'processedDatas' => array_map(function ($item) {
                    return [
                        'order_item_id' => $item['order_item_id'],
                        'child' => $item['qr_child'],
                        'action' => $item['action'],
                        'timestamp' => $item['timestamp'],
                    ];
                }, $response),
            ]);

        } catch(\Exception $e) {
            DB::rollback();

            TurnstileLogs::create([
                'datetime' => Carbon::now(),
                'context' => $e->getMessage()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

    }

    public function curlRequest(Request $request)
    {
        $status = $request->query('status');
        $qrCode = $request->query('qr');
        $time = $request->query('time');

        if(!$status && !$qrCode)
        {
            return response("<pre>Invalid fields</pre>");
        }

        $data = [
            'status' => $status,
            'qr' => $qrCode,
            'time' => $time ?? null
        ];

        $jsonData = json_encode($data);

        $url = env('APP_URL') . "api/turnstile-srch";

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            echo "Response: " . $response;
        }

        curl_close($ch);
    }
}
