@extends('admin.layouts.app')

@section('page_title', 'Virtual accounts')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Static virtual accounts</h2>
            <p class="mt-1 text-sm text-slate-500">View virtual account records created during signup and lookup account status.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-600">Bank account overview</span>
    </div>

    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search account number, name, bank, status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Search</button>
        </form>
        @if(($search ?? '') !== '')
        <a href="{{ route('admin.virtual-accounts') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-[1000px] w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-3">User</th>
                    <th class="py-3">Account number</th>
                    <th class="py-3">Bank</th>
                    <th class="py-3">Status</th>
                    <th class="py-3">Reference</th>
                    <th class="py-3">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($virtualAccounts as $account)
                <tr class="border-b align-top bg-white hover:bg-slate-50">
                    <td class="py-3">
                        <div class="font-medium text-slate-800">{{ $account->user?->first_name ?? 'User' }} {{ $account->user?->last_name ?? '' }}</div>
                        <div class="text-xs text-slate-500">{{ $account->user?->email ?? $account->customer_email ?? 'No email' }}</div>
                    </td>
                    <td class="py-3">{{ $account->account_number ?? 'N/A' }}</td>
                    <td class="py-3">{{ $account->bank_name ?? 'N/A' }}</td>
                    <td class="py-3">{{ ucfirst($account->status ?? 'unknown') }}</td>
                    <td class="py-3">
                        <div class="text-slate-700">{{ $account->txt_ref ?? '-' }}</div>
                        <div class="text-xs text-slate-500">{{ $account->order_ref ?? '' }}</div>
                    </td>
                    <td class="py-3">{{ optional($account->created_at)->format('M j, Y H:i') ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-4 text-slate-500">No virtual accounts found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $virtualAccounts->links() }}</div>
</div>
@endsection