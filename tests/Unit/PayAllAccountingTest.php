<?php

namespace Tests\Unit;

use App\Http\Controllers\PaymentsController;
use App\Http\Requests\AddPaymentRequest;
use App\Models\M06;
use App\Models\M06Child;
use App\Models\OfficialReceipt;
use App\Models\OrderItems;
use App\Models\OrderPayment;
use App\Models\Orders;
use App\Models\Outlet;
use App\Models\PaymentMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Exercises PaymentsController::payAll() against the exact 6-child booking
 * described in the task and asserts the accounting rollups + the legacy POS
 * (orhdr Official Receipt) writes, so the POS feature can be validated end to end.
 *
 * Scenario (one order, the six enumerated children):
 *   1. Paid, but never checked in (ckin still null)
 *   2. Fresh booking — no payments, no check-ins
 *   3. Playtime started (ckin set), not paid
 *   4. Paid, playtime active, due within 10 min (no overtime)
 *   5. Paid and already checked out
 *   6. Unpaid, playtime active, registered hour overdue by > 10 min (overtime charged)
 */
class PayAllAccountingTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_RATE = 200.0;
    private const OVERTIME_CHARGED = 50.0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createLegacyTables();
        $this->seedPaymentModes();
    }

    /**
     * The legacy MIMO tables (outlet / orhdr / m10) are not owned by this app's
     * migrations, so create minimal schemas for them here.
     */
    private function createLegacyTables(): void
    {
        if (!Schema::hasTable('m10')) {
            Schema::create('m10', function ($table) {
                $table->string('mp_code')->primary();
                $table->string('mp_desc')->nullable();
                $table->boolean('active')->default(1);
                $table->boolean('iscredit')->default(0);
            });
        }

        if (!Schema::hasTable('outlet')) {
            Schema::create('outlet', function ($table) {
                $table->string('out_code')->primary();
                $table->string('ord_code');
                $table->string('branch')->nullable();
            });
        }

        // duration_prices is a legacy table whose create migration is commented out,
        // but ordlne_ph.durations_id has a FK to it. Create a minimal copy.
        if (!Schema::hasTable('duration_prices')) {
            Schema::create('duration_prices', function ($table) {
                $table->id();
                $table->string('duration_hour')->nullable();
                $table->string('label')->nullable();
                $table->decimal('price', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('orhdr')) {
            Schema::create('orhdr', function ($table) {
                $table->string('ord_code')->primary();
                $table->string('out_code')->nullable();
                $table->string('customer')->nullable();
                $table->date('ord_date')->nullable();
                $table->decimal('net_amnt', 10, 2)->default(0);
                $table->decimal('tax_amnt', 10, 2)->default(0);
                $table->decimal('total_amnt', 10, 2)->default(0);
                $table->decimal('disc_amnt', 10, 2)->default(0);
                $table->string('user_id')->nullable();
                $table->date('t_date')->nullable();
                $table->string('t_time')->nullable();
                $table->string('loc')->nullable();
                $table->string('user_id2')->nullable();
                $table->date('t_date2')->nullable();
                $table->string('t_time2')->nullable();
                $table->date('trnx_date')->nullable();
                $table->string('debt_code')->nullable();
                $table->string('branch')->nullable();
                $table->string('reference')->nullable();
                $table->decimal('payment', 10, 2)->default(0);
                $table->string('pay_code')->nullable();
                $table->string('pending')->nullable();
                $table->decimal('amnt_tendered', 10, 2)->default(0);
                $table->decimal('ord_amnt', 10, 2)->default(0);
                $table->decimal('amnt_due', 10, 2)->default(0);
                $table->date('due_date')->nullable();
                $table->string('ord_code_ph')->nullable();
                $table->decimal('payment2', 10, 2)->default(0);
                $table->string('pay_code2')->nullable();
                $table->decimal('amnt_tendered2', 10, 2)->default(0);
            });
        }

        // Outlet row is required by recordOfficialReceipt() to mint OR numbers.
        if (Outlet::count() === 0) {
            Outlet::insert([
                'out_code' => 'OUT1',
                'ord_code' => '1000',
                'branch'   => 'PLAYHOUSE',
            ]);
        }
    }

    private function seedPaymentModes(): void
    {
        if (PaymentMode::where('mp_code', PaymentMode::CASH_CODE)->doesntExist()) {
            PaymentMode::insert([
                'mp_code'  => PaymentMode::CASH_CODE,
                'mp_desc'  => 'Cash',
                'active'   => true,
                'iscredit' => false,
            ]);
        }
    }

    /**
     * Builds the order + six children exactly per the task scenario and returns
     * the controller-ready AddPaymentRequest for a payAll call.
     */
    private function seedScenarioAndGetRequest(float $cashTendered): array
    {
        $parent = M06::create([
            'd_name'   => 'Test Parent',
            'lastname' => 'Parent',
            'firstname'=> 'Test',
            'mobileno' => '09170000000',
        ]);

        $children = [];
        for ($i = 1; $i <= 6; $i++) {
            $children[$i] = M06Child::create([
                'lastname' => "Child{$i}",
                'firstname'=> "Kid{$i}",
                'birthday' => '2018-01-01',
                'd_code'   => $parent->d_code,
            ]);
        }

        // NOTE: Orders' boot() overwrites ord_code_ph with a generated monthly
        // code, so we read back whatever it assigned and use that everywhere.
        $order = Orders::create([
            'd_code'      => $parent->d_code,
            'parent'      => 'Test Parent',
        ]);
        $ordCodePh = $order->ord_code_ph;

        $now = now();
        $config = [
            1 => ['paid' => true,  'ckin' => null,                       'ckout' => null,             'xtra' => 0.0],
            2 => ['paid' => false, 'ckin' => null,                       'ckout' => null,             'xtra' => 0.0],
            3 => ['paid' => false, 'ckin' => $now->copy()->subHour(),    'ckout' => null,             'xtra' => 0.0],
            4 => ['paid' => true,  'ckin' => $now->copy()->subMinutes(52),'ckout' => null,            'xtra' => 0.0],
            5 => ['paid' => true,  'ckin' => $now->copy()->subHours(2),  'ckout' => $now->copy()->subMinutes(10), 'xtra' => 0.0],
            6 => ['paid' => false, 'ckin' => $now->copy()->subMinutes(75),'ckout' => null,           'xtra' => self::OVERTIME_CHARGED],
        ];

        $items = [];
        foreach ($config as $n => $c) {
            $item = OrderItems::create([
                'ord_code_ph'     => $ordCodePh,
                'd_code_child'    => $children[$n]->d_code_c,
                'durationhours'   => 1,
                'subtotal'        => self::BASE_RATE,
                'lne_xtra_chrg'   => $c['xtra'],
                'is_paid'         => $c['paid'],
                'paid_at'         => $c['paid'] ? $now : null,
                'ckin'            => $c['ckin'],
                'ckout'           => $c['ckout'],
                'checked_out'     => !empty($c['ckout']),
            ]);

            // Simulate the items that are described as "already paid".
            if ($c['paid']) {
                OrderPayment::create([
                    'ordlne_ph_id'  => $item->id,
                    'ord_code_ph'   => $ordCodePh,
                    'payment_method'=> PaymentMode::CASH_CODE,
                    'amount'        => self::BASE_RATE + $c['xtra'],
                    'cash_tendered' => self::BASE_RATE + $c['xtra'],
                    'paid_at'       => $now,
                ]);
            }

            $items[$n] = $item;
        }

        // Roll the order header totals up to reflect the pre-existing payments.
        $controller = new PaymentsController();
        $reflection = new \ReflectionMethod($controller, 'syncOrderPaymentTotals');
        $reflection->setAccessible(true);
        $reflection->invoke($controller, $ordCodePh);

        $request = $this->makePaymentRequest([
            'payment_method' => PaymentMode::CASH_CODE,
            'cash_tendered'  => $cashTendered,
            'reference'      => 'POS-TEST',
        ]);

        return [$order, $items, $request, $ordCodePh];
    }

    /**
     * Builds a real AddPaymentRequest without going through the HTTP pipeline
     * (which would require auth + routing). Authorize() is never invoked here.
     */
    private function makePaymentRequest(array $data): AddPaymentRequest
    {
        $request = new AddPaymentRequest();
        $request->setContainer($this->app);
        $request->merge($data);

        // Prime the protected validator so validated() works without going
        // through the HTTP pipeline (which would require auth + routing).
        $validator = \Illuminate\Support\Facades\Validator::make(
            $request->all(),
            $request->rules()
        );

        $ref = new \ReflectionProperty($request, 'validator');
        $ref->setAccessible(true);
        $ref->setValue($request, $validator);

        return $request;
    }

    public function test_pay_all_collects_only_unpaid_children_and_writes_pos_receipts(): void
    {
        [$order, $items, $request, $ordCodePh] = $this->seedScenarioAndGetRequest(1000.0);

        $controller = new PaymentsController();
        $response = $controller->payAll($request, $ordCodePh);
        $payload = $response->getData(true);

        // --- Controller-level accounting response ---
        $this->assertTrue($payload['success']);
        $this->assertSame(3, $payload['items_paid'], 'Only the 3 unpaid children (2,3,6) should be paid.');
        $this->assertSame(0, $payload['items_partial']);
        $this->assertEqualsWithDelta(650.0, $payload['total_applied'], 0.001, '200 + 200 + (200+50 overtime) = 650.');
        $this->assertEqualsWithDelta(350.0, $payload['change_amnt'], 0.001, '1000 tendered - 650 applied = 350 change.');

        // --- Per-item state after payAll ---
        $item2 = $items[2]->fresh();
        $item3 = $items[3]->fresh();
        $item6 = $items[6]->fresh();

        foreach ([$item2, $item3, $item6] as $paid) {
            $this->assertTrue($paid->is_paid);
            $this->assertNotNull($paid->paid_at);
            // NOTE: payAll forces checked_out = true on anything it pays, even
            // child 2 which was never checked in (ckin still null).
            $this->assertTrue($paid->checked_out);
        }

        // Child 2 was booked but never checked in — ckin stays null, yet it is
        // now flagged paid + checked_out by payAll.
        $this->assertNull($item2->ckin);

        // Already-paid children (1,4,5) must be untouched by payAll.
        $this->assertTrue($items[1]->fresh()->is_paid);
        $this->assertTrue($items[4]->fresh()->is_paid);
        $this->assertTrue($items[5]->fresh()->is_paid);
        $this->assertNull($items[1]->fresh()->ckin, 'Child 1 should remain never-checked-in.');

        // --- Overtime (registered hour overdue) is included in child 6's collection ---
        $this->assertEqualsWithDelta(250.0, $item6->subtotal + $item6->lne_xtra_chrg, 0.001);

        // --- Order header rollup ---
        $order = $order->fresh();
        $this->assertEqualsWithDelta(1250.0, $order->paid_amnt, 0.001, '6*200 + 50 overtime.');
        $this->assertTrue($order->is_paid, 'All six children paid => order fully paid.');
        $this->assertNotNull($order->paid_at);

        // --- POS: legacy orhdr Official Receipts ---
        $this->assertDatabaseCount('orlne_pay', 6);
        $this->assertSame(3, OfficialReceipt::count(), 'One OR row per newly-paid child (2,3,6).');

        $receipts = OfficialReceipt::where('ord_code_ph', $ordCodePh)
            ->orderBy('reference')
            ->get();

        $this->assertEqualsWithDelta(650.0, $receipts->sum('net_amnt'), 0.001);
        $this->assertEqualsWithDelta(-650.0, $receipts->sum('payment') + $receipts->sum('payment2'), 0.001);

        // Child 6 receipt carries the overtime in net_amnt and the folded change in amnt_tendered.
        $child6Receipt = OfficialReceipt::where('reference', 'SO#-' . $ordCodePh . '-' . $item6->id)->first();
        $this->assertNotNull($child6Receipt);
        $this->assertEqualsWithDelta(250.0, $child6Receipt->net_amnt, 0.001);
        $this->assertEqualsWithDelta(-250.0, $child6Receipt->payment, 0.001);
        $this->assertSame(PaymentMode::CASH_CODE, $child6Receipt->pay_code);
        // 250 applied + 350 change folded onto the last item's payment.
        $this->assertEqualsWithDelta(600.0, $child6Receipt->amnt_tendered, 0.001);
        // The OR records amnt_due = 0; the returned change is the gap between
        // cash tendered (600) and the amount applied to the bill (250).
        $this->assertEqualsWithDelta(0.0, $child6Receipt->amnt_due, 0.001);
        $this->assertEqualsWithDelta(350.0, $child6Receipt->amnt_tendered + $child6Receipt->payment, 0.001,
            'Cash tendered (600) minus bill (250) = 350 change.');
    }

    public function test_pay_all_with_exact_tender_leaves_no_change_and_sets_no_partial(): void
    {
        [$order, $items, $request, $ordCodePh] = $this->seedScenarioAndGetRequest(650.0);

        $controller = new PaymentsController();
        $payload = $controller->payAll($request, $ordCodePh)->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame(3, $payload['items_paid']);
        $this->assertSame(0, $payload['items_partial']);
        $this->assertEqualsWithDelta(650.0, $payload['total_applied'], 0.001);
        $this->assertEqualsWithDelta(0.0, $payload['change_amnt'], 0.001);

        $item6 = $items[6]->fresh();
        $lastPayment = $item6->payments()->latest('id')->first();
        $this->assertEqualsWithDelta(250.0, $lastPayment->cash_tendered, 0.001);
        $this->assertEqualsWithDelta(0.0, $lastPayment->change_amnt, 0.001);
    }

    /**
     * Cash tender over the outstanding balance must return the difference as
     * change, folded onto the last item's payment so per-item cash/change
     * totals stay internally consistent. This is the POS change-handling path.
     */
    public function test_pay_all_cash_tender_over_balance_returns_change_to_last_item(): void
    {
        // Outstanding = child 2 (200) + child 3 (200) + child 6 (200 + 50) = 650.
        // Tender 900 => 250 change expected.
        [$order, $items, $request, $ordCodePh] = $this->seedScenarioAndGetRequest(900.0);

        $controller = new PaymentsController();
        $payload = $controller->payAll($request, $ordCodePh)->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame(3, $payload['items_paid']);
        $this->assertEqualsWithDelta(650.0, $payload['total_applied'], 0.001);
        $this->assertEqualsWithDelta(250.0, $payload['change_amnt'], 0.001, '900 tendered - 650 applied = 250 change.');

        // All three unpaid children are fully settled with the cash given.
        foreach ([2, 3, 6] as $n) {
            $this->assertTrue($items[$n]->fresh()->is_paid);
        }

        // The 250 change is folded onto the last paid item's payment record.
        $lastPayment = $items[6]->fresh()->payments()->latest('id')->first();
        $this->assertEqualsWithDelta(250.0, $lastPayment->amount, 0.001);
        $this->assertEqualsWithDelta(250.0, $lastPayment->change_amnt, 0.001, 'Change returned on the last item.');
        $this->assertEqualsWithDelta(500.0, $lastPayment->cash_tendered, 0.001, '250 applied + 250 change.');

        // The OR for child 6 reflects the same: tendered (500) - bill (250) = 250 change.
        $receipt = OfficialReceipt::where('reference', 'SO#-' . $ordCodePh . '-' . $items[6]->id)->first();
        $this->assertNotNull($receipt);
        $this->assertEqualsWithDelta(500.0, $receipt->amnt_tendered, 0.001);
        $this->assertEqualsWithDelta(-250.0, $receipt->payment, 0.001);
        $this->assertEqualsWithDelta(250.0, $receipt->amnt_tendered + $receipt->payment, 0.001);
    }
}
