<div class="rounded-lg backdrop-blur-lg bg-white/60 p-6 lg:p-8">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('sms_blast.index') }}" class="inline-flex items-center text-gray-800 hover:text-gray-700 mb-4">
            <i class="fas fa-arrow-left mr-2"></i>Back to SMS Blasts
        </a>
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center text-[var(--color-primary-full-dark)] gap-3">
                    <h1 class="text-3xl font-bold">Blast #{{ $smsBlast->id }}</h1>
                    @include('components.sms-blasts.status-badge', ['blast' => $smsBlast])
                </div>
                <p class="text-gray-500 mt-1">{{ $smsBlast->title }} - {{ $smsBlast->created_at->format('M d, Y \a\t g:i A') }}</p>
            </div>
            <div class="flex gap-3">
                @if ($smsBlast->status === 'sent' || $smsBlast->status === 'failed')
                    <button type="button" onclick="resendFailed({{ $smsBlast->id }})"
                        class="btn-outline inline-flex items-center text-[var(--color-primary-full-dark)]">
                        <i class="fas fa-redo mr-2"></i>Resend to Failed
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Total Recipients</div>
            <div class="text-2xl font-bold">{{ $smsBlast->total_recipients }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-green-600">Sent Successfully</div>
            <div class="text-2xl font-bold text-green-600">{{ $smsBlast->sent_count }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-red-600">Failed</div>
            <div class="text-2xl font-bold text-red-600">{{ $smsBlast->failed_count }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Pending</div>
            <div class="text-2xl font-bold">{{ $smsBlast->total_recipients - ($smsBlast->sent_count - $smsBlast->failed_count) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Success Rate</div>
            <div class="text-2xl font-bold @if($smsBlast->success_rate >= 90) text-green-600 @elseif($smsBlast->success_rate >= 50) text-yellow-600 @else text-red-600 @endif">
                {{ $smsBlast->success_rate }}%
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Message & Recipients -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Message Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-envelope mr-2 text-blue-500"></i>
                    Message Content
                </h2>
                <div class="text-sm text-gray-600 mb-4">{{ $smsBlast->title }}</div>
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 font-mono text-sm whitespace-pre-line break-words">
                    {{ $smsBlast->message }}
                </div>
                <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
                    <div class="flex items-center gap-4">
                        <div>
                            <i class="fas fa-hashtag mr-1"></i>
                            {{ Str::length($smsBlast->message) }} characters
                        </div>
                        <div class="text-gray-300">|</div>
                        <div>
                            <i class="fas fa-paper-plane mr-1"></i>
                            {{ $smsBlast->total_recipients }} recipients
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recipients Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between flex-wrap gap-4">
                    <h2 class="text-xl font-semibold">Recipients ({{ $smsBlast->total_recipients }})</h2>
                    <div class="flex gap-2">
                        <button class="btn-outline btn-sm" onclick="filterRecipients('all', this)">All</button>
                        <button class="btn-outline btn-sm text-green-600 border-green-200" onclick="filterRecipients('sent', this)">Sent</button>
                        <button class="btn-outline btn-sm text-red-600 border-red-200" onclick="filterRecipients('failed', this)">Failed</button>
                        <button class="btn-outline btn-sm text-yellow-600 border-yellow-200" onclick="filterRecipients('pending', this)">Pending</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mobile</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent At</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 recipient-rows">
                            @forelse ($smsBlast->recipients()->with('smsBlast')->latest()->paginate(50) as $recipient)
                            <tr class="table-row recipient-row" data-status="{{ $recipient->status }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $recipient->recipient_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $recipient->mobile_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full @if($recipient->recipient_type === 'parent') bg-blue-100 text-blue-800 @else bg-purple-100 text-purple-800 @endif">
                                        {{ ucfirst($recipient->recipient_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @include('components.sms-blasts.recipient-status-badge', ['recipient' => $recipient])
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if ($recipient->sent_at)
                                        {{ $recipient->sent_at->format('M d, g:i A') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-envelope-open-text text-gray-400 text-2xl mb-2 block"></i>
                                    No recipients found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $smsBlast->recipients()
                            ->paginate(20)
                            ->appends(request()->except('page'))
                            ->onEachSide(2)
                            ->links()
                    }}
                </div>
            </div>
        </div>

        <!-- Right Column - Blast Info -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold mb-4">Blast Information</h3>
                <dl class="space-y-3">
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <dt class="text-sm text-gray-500">Title</dt>
                        <dd class="text-sm font-medium text-right">{{ Str::limit($smsBlast->title, 25) }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd>
                            @include('components.sms-blasts.status-badge', ['blast' => $smsBlast])
                        </dd>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <dt class="text-sm text-gray-500">Created</dt>
                        <dd class="text-sm font-medium">{{ $smsBlast->created_at->format('M d, Y • g:i A') }}</dd>
                    </div>
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <dt class="text-sm text-gray-500">Sent</dt>
                        <dd class="text-sm font-medium">{{ $smsBlast->sent_at ? $smsBlast->sent_at->format('M d, Y • g:i A') : '—' }}</dd>
                    </div>
                    @if ($smsBlast->scheduled_at)
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <dt class="text-sm text-gray-500">Scheduled</dt>
                        <dd class="text-sm font-medium">{{ $smsBlast->scheduled_at->format('M d, Y • g:i A') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold mb-4">Recipient Breakdown</h3>
                @php
                    $parentCount = $smsBlast->recipients()->where('recipient_type', 'parent')->count();
                    $guardianCount = $smsBlast->recipients()->where('recipient_type', 'guardian')->count();
                @endphp
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-sm">Parents</span>
                        </div>
                        <span class="text-sm font-semibold">{{ $parentCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                            <span class="text-sm">Guardians</span>
                        </div>
                        <span class="text-sm font-semibold">{{ $guardianCount }}</span>
                    </div>
                </div>
            </div>

            @if ($smsBlast->status === 'sent' || $smsBlast->status === 'failed')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold mb-4">Actions</h3>
                <div class="space-y-2">
                    @if ($smsBlast->failed_count > 0)
                    <button onclick="resendFailed({{ $smsBlast->id }})"
                        class="w-full btn-outline flex items-center justify-center text-green-600">
                        <i class="fas fa-redo mr-2"></i>Resend to Failed
                    </button>
                    @endif
                    @if ($smsBlast->failed_count > 0)
                    <button onclick="duplicateBlast({{ $smsBlast->id }})"
                        class="w-full btn-outline flex items-center justify-center">
                        <i class="fas fa-copy mr-2"></i>Duplicate
                    </button>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function filterRecipients(status, btn) {
    document.querySelectorAll('.recipient-row').forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });

    document.querySelectorAll('.btn-outline.btn-sm').forEach(b => b.classList.remove('bg-blue-50'));
    if (btn) btn.classList.add('bg-blue-50');
}

async function resendFailed(blastId) {
    const result = await Swal.fire({
        title: "Resend?",
        text: "Resend to all failed recipients?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Resend"
    });

    if (!result.isConfirmed) return;

    fetch(`/admin/sms-blasts/${blastId}/resend`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(async r => await r.json())
    .then(d => {
        Swal.fire({
            title: "Complete",
            text: `Resend complete: ${d?.sent || 0} sent, ${d?.failed || 0} failed`,
            icon: "success"
        });
        if (d.sent > 0) location.reload();
    })
    .catch(() => {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: 'Error resending',
        });
    });
}

function duplicateBlast(blastId) {
    Swal.fire({
        icon: "info",
        title: "Duplicate blast",
        text: `Duplicate blast feature (mockup) - ID: ${blastId}`,
    });
}
</script>
