@extends('admin.layouts.app')

@section('page_title', 'Withdrawals')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Withdrawals and payouts</h2>
            <p class="mt-2 text-sm text-slate-500">Review pending rider payouts and approve or reject them.</p>
        </div>
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search withdrawals" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Search</button>
        </form>
    </div>
    @if(($search ?? '') !== '')
    <div class="mt-3"><a href="{{ route('admin.withdrawals') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear search</a></div>
    @endif
    <div class="mt-5 space-y-3">
        @forelse($withdrawals as $withdrawal)
        <div class="rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-medium">{{ $withdrawal->user->first_name ?? 'User' }} {{ $withdrawal->user->last_name ?? '' }}</div>
                    <div class="text-sm text-slate-500">Amount: {{ number_format($withdrawal->amount ?? 0, 2) }} • {{ $withdrawal->status }}</div>
                </div>
                <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="admin_note" placeholder="Admin note" class="rounded border border-slate-300 px-2 py-1 text-sm">
                    <button class="rounded-lg border border-emerald-300 px-3 py-1.5 text-xs text-emerald-700">Approve</button>
                </form>
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-slate-200 p-3">No withdrawal requests found.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $withdrawals->links() }}</div>
</div>
@endsection