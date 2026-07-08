@extends('admin.layouts.app')

@section('page_title', 'Rider wallets')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Rider wallet balances</h2>
            <p class="mt-1 text-sm text-slate-500">Review rider wallet holdings and delivered job revenue for completed orders.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-600">Earnings overview</span>
    </div>

    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search rider name, email, phone" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Search</button>
        </form>
        @if(($search ?? '') !== '')
        <a href="{{ route('admin.rider-wallets') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-[950px] w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-3">Rider</th>
                    <th class="py-3">Email</th>
                    <th class="py-3">Wallet balance</th>
                    <th class="py-3">Delivered revenue</th>
                    <th class="py-3">Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riders as $rider)
                <tr class="border-b align-top bg-white hover:bg-slate-50">
                    <td class="py-3">
                        <div class="font-medium text-slate-800">{{ $rider->first_name ?? 'Rider' }} {{ $rider->last_name ?? '' }}</div>
                        <div class="text-xs text-slate-500">{{ optional($rider->riderProfile)->service_zone ?? 'Service zone missing' }}</div>
                    </td>
                    <td class="py-3">{{ $rider->email ?? 'N/A' }}</td>
                    <td class="py-3">{{ number_format($rider->userwallet->balance ?? 0, 2) }}</td>
                    <td class="py-3">{{ number_format($deliveredRevenue[$rider->id] ?? 0, 2) }}</td>
                    <td class="py-3">{{ optional($rider->created_at)->format('M j, Y') ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-4 text-slate-500">No riders found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $riders->links() }}</div>
</div>
@endsection