@extends('admin.layouts.app')

@section('page_title', 'Platform charges')

@section('content')
<div class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Commission and platform charges</h2>
        <p class="mt-2 text-sm text-slate-500">Platform charges are grouped by today, this week, the current month, the current year, and all time.</p>
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($periods as $period)
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-medium text-slate-700">{{ $period['label'] }}</div>
                <div class="mt-1 text-xs uppercase tracking-wide text-slate-500">{{ $period['range'] }}</div>
                <div class="mt-3 text-2xl font-semibold text-emerald-700">{{ number_format($period['value'] ?? 0, 2) }}</div>
            </div>
            @endforeach
        </div>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Balance overview</h2>
        <p class="mt-2 text-sm text-slate-500">Wallet and escrow balances are surfaced with account counts and availability checks.</p>
        <div class="mt-5 space-y-3">
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-medium text-slate-700">Customer wallet balance</div>
                <div class="mt-2 text-2xl font-semibold">{{ number_format($customerWalletTotal ?? 0, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-medium text-slate-700">Rider wallet balance</div>
                <div class="mt-2 text-2xl font-semibold">{{ number_format($riderWalletTotal ?? 0, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-medium text-slate-700">Escrow balance</div>
                <div class="mt-2 text-2xl font-semibold">{{ number_format($escrowTotal ?? 0, 2) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <div class="text-sm font-medium text-slate-700">Static virtual accounts</div>
                <div class="mt-2 text-2xl font-semibold">{{ number_format($totalVirtualAccounts ?? 0, 0) }}</div>
            </div>
        </div>
    </div>
</div>
@endsection