@extends('layout.basic')

@section('title', 'Booking ' . $order->ord_code_ph . ' - Payments')

@section('contents')
    <header class="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div>
            <x-application-logo-2 class="block fill-current text-gray-800" />
            <h1 class="text-3xl font-bold text-gray-800">Booking #{{ $order->ord_code_ph }}</h1>
            <p class="text-gray-600">{{ $order->parentPl?->d_name ?? $order->parent }}</p>
        </div>
        <a href="{{ route('payments.index') }}" class="text-sm underline text-gray-800 font-semibold p-1 hover:opacity-80">
            <i class="fa-solid fa-arrow-left-long mr-2"></i>Back to Payments
        </a>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($order->orderItems as $item)
            <div class="rounded-2xl p-6 bg-white border-2 border-gray-200 shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ $item->child ? $item->child->firstname . ' ' . $item->child->lastname : 'N/A' }}
                    </h2>
                    @if($item->status === 'paid')
                        <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                    @elseif($item->status === 'done')
                        <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Ready to Pay</span>
                    @elseif($item->status === 'booked')
                        <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Booked</span>
                    @else
                        <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Active</span>
                    @endif
                </div>

                <div class="text-base text-gray-800 mb-2">
                    <div class="flex justify-between py-1"><span>Play duration</span><span class="font-medium">{{ $item->durationhours == 5 ? 'Unlimited' : $item->durationhours . ' hr(s)' }} — ₱{{ number_format($item->durationsubtotal, 2) }}</span></div>
                    <div class="flex justify-between py-1"><span>Socks</span><span class="font-medium">{{ $item->socksqty }} pair(s) — ₱{{ number_format($item->socksprice, 2) }}</span></div>
                    <div class="flex justify-between py-1"><span>Others</span><span class="font-medium">₱{{ number_format($item->others_amnt, 2) }}</span></div>
                    <div class="flex justify-between py-1"><span>Discount</span><span class="font-medium">-₱{{ number_format($item->disc_amnt, 2) }}</span></div>
                    <div class="flex justify-between py-1"><span>Check-in Time</span><span class="font-medium">{{ $item->ckin ? \Carbon\Carbon::parse($item->ckin)->format('h:i A') : 'N/A' }}</span></div>
                </div>

                <div class="flex justify-between py-2 border-t-2 border-gray-300 text-lg font-bold text-gray-900">
                    <span>Subtotal</span><span>₱{{ number_format($item->durationsubtotal + $item->socksprice + $item->others_amnt - $item->disc_amnt, 2) }}</span>
                </div>

                @if($item->overtime_breakdown)
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <div class="flex justify-between text-base font-bold text-red-600">
                            <span>Extra Charge (Preview)</span><span>₱{{ number_format($item->lne_xtra_chrg, 2) }}</span>
                        </div>
                        <div class="text-sm text-gray-500 mt-1 space-y-0.5">
                            <p>Overtime: {{ $item->overtime_breakdown['extra_minutes'] }} minute(s) (actual {{ $item->overtime_breakdown['actual_minutes'] }} - paid {{ $item->overtime_breakdown['paid_minutes'] }})</p>
                            <p>Rate: ₱{{ $item->overtime_breakdown['rate'] }} per {{ $item->overtime_breakdown['minutes_per_charge'] }} minutes</p>
                            <p>Number of {{ $item->overtime_breakdown['minutes_per_charge'] }}-minute blocks: {{ $item->overtime_breakdown['charge_units'] }}</p>
                            <p>Extra Charge = {{ $item->overtime_breakdown['charge_units'] }} × ₱{{ $item->overtime_breakdown['rate'] }} = ₱{{ number_format($item->lne_xtra_chrg, 2) }}</p>
                        </div>
                    </div>
                @endif

                <div class="flex justify-between py-3 mt-2 border-t-2 border-gray-300 text-2xl font-bold text-gray-900">
                    <span>Total</span><span>₱{{ number_format($item->amount_due, 2) }}</span>
                </div>

                @if($item->status === 'done' || $item->status === 'paid')
                    <button type="button" class="open-payment-btn w-full px-4 py-2 {{ $item->status === 'paid' ? 'bg-gray-200 text-gray-700' : 'bg-green-600 text-white' }} font-semibold rounded-lg hover:opacity-80 transition-all"
                        data-id="{{ $item->id }}">
                        {{ $item->status === 'paid' ? 'View Payment' : 'Pay' }}
                    </button>
                @else
                    <p class="text-sm text-gray-500 italic">Not yet checked out — nothing to pay.</p>
                @endif
            </div>
        @empty
            <p class="text-gray-500">No order items found for this booking.</p>
        @endforelse
    </div>
@endsection

@section('scripts')
    @vite('resources/js/modules/paymentModal.js')
@endsection
