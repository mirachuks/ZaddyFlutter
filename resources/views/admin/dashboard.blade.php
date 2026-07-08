@extends('admin.layouts.app')

@section('page_title', 'Admin dashboard')

@section('content')
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Customers</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['customers'] }}</div>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Riders</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['riders'] }}</div>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Active jobs</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['active_jobs'] }}</div>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Completed jobs</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['completed_jobs'] }}</div>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Pending KYC</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['kyc_pending'] }}</div>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Pending items</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['pending_disputes'] + $stats['pending_withdrawals'] }}</div>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Operations overview</h2>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700">Live admin panel</span>
        </div>
        <p class="mt-4 text-sm text-slate-500">The admin portal now includes rider approval, user suspension, job review, dispute resolution, withdrawals, reports, and profile/password management.</p>
        <div class="mt-5 space-y-3">
            <a href="{{ route('admin.active-users') }}" class="block rounded-lg border border-slate-200 p-3 transition hover:border-emerald-400 hover:bg-emerald-50">• View active users</a>
            <a href="{{ route('admin.riders') }}" class="block rounded-lg border border-slate-200 p-3 transition hover:border-emerald-400 hover:bg-emerald-50">• Review and manage riders</a>
            <a href="{{ route('admin.orders') }}" class="block rounded-lg border border-slate-200 p-3 transition hover:border-emerald-400 hover:bg-emerald-50">• Review delivery jobs and order details</a>
            <a href="{{ route('admin.disputes') }}" class="block rounded-lg border border-slate-200 p-3 transition hover:border-emerald-400 hover:bg-emerald-50">• Resolve disputes and review withdrawals</a>
            <a href="{{ route('admin.reports') }}" class="block rounded-lg border border-slate-200 p-3 transition hover:border-emerald-400 hover:bg-emerald-50">• Review platform charges and wallet balances</a>
        </div>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Priority queues</h2>
        <div class="mt-4 space-y-3 text-sm text-slate-500">
            <div class="rounded-lg border border-slate-200 p-3">KYC reviews: {{ $stats['kyc_pending'] }}</div>
            <div class="rounded-lg border border-slate-200 p-3">Disputes: {{ $stats['pending_disputes'] }}</div>
            <div class="rounded-lg border border-slate-200 p-3">Withdrawals: {{ $stats['pending_withdrawals'] }}</div>
            <div class="rounded-lg border border-slate-200 p-3">Manual payments: {{ $stats['pending_manual_payments'] }}</div>
        </div>
    </div>
</div>
@endsection