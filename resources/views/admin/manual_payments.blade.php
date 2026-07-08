@extends('admin.layouts.app')

@section('page_title', 'Confirm Payments')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Manual payment confirmations</h2>
            <p class="text-sm text-slate-500">Review bank-transfer notifications from customers and approve them once the payment is verified.</p>
        </div>
    </div>

    @if ($pendingPayments->isEmpty())
    <div class="mt-6 rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
        No manual payments are waiting for confirmation.
    </div>
    @else
    <div class="mt-6 space-y-4">
        @foreach ($pendingPayments as $transaction)
        <div class="rounded-xl border border-slate-200 p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="font-semibold">{{ $transaction->user?->first_name }} {{ $transaction->user?->last_name }}</div>
                    <div class="text-sm text-slate-500">{{ $transaction->user?->email }}</div>
                    <div class="mt-2 text-sm text-slate-600">Amount: ₦{{ number_format($transaction->balance, 2) }}</div>
                    <div class="text-sm text-slate-600">Reference: {{ $transaction->payment_reference ?? '—' }}</div>
                    <div class="text-sm text-slate-600">Method: {{ $transaction->payment_method ?? 'bank_transfer' }}</div>
                    @if ($transaction->payment_proof_path)
                    <div class="mt-3">
                        <div class="text-sm font-medium text-slate-700">Payment proof</div>
                        <div class="mt-2 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 p-2">
                            <img src="{{ Storage::disk('public')->url($transaction->payment_proof_path) }}" alt="Payment proof" class="max-h-64 w-full rounded-md object-contain" />
                        </div>
                        <a href="{{ Storage::disk('public')->url($transaction->payment_proof_path) }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm text-emerald-700 underline">Open full image</a>
                    </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.manual-payments.approve', $transaction) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $pendingPayments->links() }}
    </div>
    @endif
</div>
@endsection