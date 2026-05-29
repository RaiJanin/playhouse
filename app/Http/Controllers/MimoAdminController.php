<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\M06Guardian;
use App\Models\M06Child;
use App\Models\OrderItems;
use Carbon\Carbon;

class MimoAdminController extends Controller
{
    private $checkoutExpr = "ckin + (durationhours * interval '1 hour')";

    private function dateFilters($query, $request, $startDate, $endDate, $column)
    {
        return $query->when(
            $request->filled(['start_date', 'end_date']),
            function ($q) use ($startDate, $endDate, $column) {
                $q->whereDate($column, '>=', $startDate)
                ->whereDate($column, '<=', $endDate);
            }
        );
    }

    private function baseOrderQuery($request, $startDate, $endDate, $column)
    {
        $query = OrderItems::query();

        return $this->dateFilters($query, $request, $startDate, $endDate, $column);
    }

    private function allItemsQuery($request, $startDate, $endDate, $status)
    {
        $query = $this->baseOrderQuery($request, $startDate, $endDate, 'created_at');

        switch($status)
        {
            case 'ckin':
                $query->whereNotNull('ckin')->whereNull('ckout');
                break;
            case 'ckout':
                $query->whereNotNull('ckout');
                break;
            case 'reservation':
                $query->whereNull('ckin')->whereNull('ckout');
                break;
        }

        return $query->with(['child', 'order.parentPl'])
                     ->orderBy('created_at', 'desc')
                     ->paginate(20)
                     ->withQueryString();
    }

    public function index(Request $request)
    {
        $request->merge([
            'start_date' => $request->input('start_date', now()->format('Y-m-d')),
            'end_date'   => $request->input('end_date', now()->format('Y-m-d')),
        ]);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $now = Carbon::now();

        $inHouseGuardians = $this->baseOrderQuery($request, $startDate, $endDate, 'ckin')->whereNotNull('guardian')->whereNotNull('ckin')->count();
        $inHouseKids = $this->baseOrderQuery($request, $startDate, $endDate, 'ckin')->whereNotNull('d_code_child')->whereNotNull('ckin')->count();
        $todayReservations = $this->baseOrderQuery($request, $startDate, $endDate, 'created_at')->whereNull('ckin')->count();
        $forCheckouts = $this->baseOrderQuery($request, $startDate, $endDate, 'ckin')->whereRaw( "{$this->checkoutExpr} < ?", [ $now->copy()->addMinutes(10)] )->whereNotNull('ckin')->count();
        $overdueCount = $this->baseOrderQuery($request, $startDate, $endDate, 'ckin')->whereRaw( "{$this->checkoutExpr} < ?", [ $now->copy() ] )->whereNotNull('ckin')->count();
        $totalKids = M06Child::count();
        $totalGuardians = M06Guardian::count();
        $totalCheckouts = OrderItems::whereNotNull('ckout')->count();
        $todaySales = OrderItems::whereDate('ckin', $now)->count();
        $totalSocks = OrderItems::sum('socksqty');

        $statusMonitor = [
            'in_house_guardians' => $inHouseGuardians,
            'in_house_kids' => $inHouseKids,
            'today_reserves' => $todayReservations,
            'total_kids' => $totalKids,
            'total_guardians' => $totalGuardians,
            'for_checkouts' => $forCheckouts,
            'total-ckouts' => $totalCheckouts,
            'socks_sold' => $totalSocks,
            'overdue' => $overdueCount,
        ];

        $orderItems = $this->allItemsQuery($request, $startDate, $endDate, $request->query('status'));

        $columns = \DB::getSchemaBuilder()->getColumnListing('ordlne_ph');

        $labels = [
            'id' => 'ID',
            'ord_code_ph' => 'Order Code',
            'd_code_child' => 'Child Code',
            'guardian' => 'Guardian',
            'durationhours' => 'Duration (Hours)',
            'durationsubtotal' => 'Duration Subtotal',
            'socksqty' => 'Socks Qty',
            'socksprice' => 'Socks Price',
            'subtotal' => 'Subtotal',
            'disc_code' => 'Discount Code',
            'checked_out' => 'Checked Out',
            'lne_xtra_chrg' => 'Extra Charge',
            'notified_timeout' => 'Notified Timeout',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'ckin' => 'Checked In',
            'ckout' => 'Checked Out',
        ];

        return view('pages.admin-panel.dashboard', compact('orderItems', 'columns', 'labels', 'statusMonitor'));
    }

    public function updateQr(Request $request, $selectedId)
    {
        $request->validate([
            'qr_child' => 'required|string|max:20',
            'qr_guardian' => 'nullable|string|max:20',
        ]);

        $orderItem = OrderItems::findOrFail($selectedId);

        $orderItem->update([
            'qr_child' => $request->qr_child,
            'qr_guardian' => $request->qr_guardian,
        ]);

        return back()->with('success', 'Updated Successfully');
    }

    public function monitoring(Request $request)
    {
        try{

            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            $query = OrderItems::query();
            $query->whereNotNull('ckin')->whereNull('ckout');
            $this->dateFilters($query, $request, $startDate, $endDate, 'ckin');

            $meta = $query->select([
                    'id',
                    'd_code_child',
                    'ord_code_ph',
                    'ckin',
                    'ckout',
                    'durationhours',
                    'qr_child',
                    'qr_guardian',
                ])->with([
                    'child:d_code_c,firstname,lastname',
                    'order:ord_code_ph,d_code',
                    'order.parentPl:d_code,d_name'
                ])->where(
                    function ($search) use ($request) {
                        $search->where('qr_child', 'like', '%' . $request->query('search') . '%')
                            ->orWhere('qr_guardian', 'like', '%' . $request->query('search') . '%')
                            ->orWhereHas('child',
                                function ($childSearch) use ($request) {
                                    $childSearch->where('firstname', 'like', '%' . $request->query('search') . '%');
                                }
                            );
                    }
                )->orderByRaw("
                    CASE
                        WHEN ckout IS NULL
                        AND durationhours != 5
                        AND NOW() > (ckin + (durationhours * INTERVAL '1 hour'))
                        THEN 0
                        ELSE 1
                    END
                ")->orderBy('ckin', 'desc')
                ->paginate(20)
                ->through(function ($item){
                    $now = Carbon::now();

                    if($item->durationhours === 5)
                    {
                        $item->remainmins = "unlimited";
                        $item->status = "normal";
                    }
                    else if(!empty($item->ckin) && empty($item->ckout))
                    {
                        $ckin = Carbon::parse($item->ckin);
                        $elapsedMinutes = $ckin->diffInMinutes($now);
                        $totalMinutes = $item->durationhours * 60;

                        $remainingMinutes = max(0, $totalMinutes - $elapsedMinutes);

                        $hours = floor($remainingMinutes / 60);
                        $minutes = $remainingMinutes % 60;
                        $item->remainmins = "{$hours}hr {$minutes}min";
                        $item->status = "normal";
                    }
                    else
                    {
                        $item->remainmins = "0hr 0min";
                        $item->status = "due";
                    }

                    if(!$item->ckout)
                    {
                        $checkin = Carbon::parse($item->ckin);
                        $due = $checkin->copy()->addHours($item->durationhours);

                        $lateMinutes = $due->diffInMinutes($now);

                        if ($lateMinutes <= 30) {
                            $item->status = "due";
                        } else {
                            $item->status = "overdue";
                        }
                        
                    }

                    return $item;

                })->withQueryString();

            return response()->json([
                'success' => true,
                'meta' => $meta,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
                'trace' => 'Trace: '.$e->getTraceAsString(),
            ]);
        }

    }
}
