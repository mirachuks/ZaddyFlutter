@extends('admin.layouts.app')

@section('page_title', 'Credit wallet')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Credit wallet</h2>
            <p class="mt-1 text-sm text-slate-500">Add funds directly to any user's wallet balance.</p>
        </div>
    </div>

    @if ($errors->any())
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    @if (session('success'))
    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
        {{ session('success') }}
    </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
            <form method="POST" action="{{ route('admin.wallet-credit.post') }}">
                @csrf

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="user_id">Select user</label>
                    <select id="user_id" name="user_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Choose a user</option>
                        @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id')==$user->id)>{{ $user->first_name }} {{ $user->last_name }} — {{ $user->email }} ({{ $user->user_type ?? 'user' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="amount">Amount</label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Enter amount" required>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="purpose">Purpose</label>
                    <input id="purpose" name="purpose" type="text" value="{{ old('purpose', 'admin_credit') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Optional description">
                </div>

                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Credit wallet</button>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold">User search</h3>
                    <p class="mt-1 text-sm text-slate-500">Search users by name, email, or phone.</p>
                </div>
            </div>

            <form class="mt-4 grid gap-3" method="GET">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search users" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <select name="user_type" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">All user types</option>

                    <option value="user" @selected(($userType ?? '' )==='user' )>Customer</option>
                    <option value="rider" @selected(($userType ?? '' )==='rider' )>Rider</option>
                    <option value="admin" @selected(($userType ?? '' )==='admin' )>Admin</option>
                </select>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Find users</button>
            </form>

            <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-[700px] w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-slate-500">
                            <th class="px-3 py-3">User</th>
                            <th class="px-3 py-3">Email</th>
                            <th class="px-3 py-3">Phone</th>
                            <th class="px-3 py-3">Type</th>
                            <th class="px-3 py-3">Wallet balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        <tr class="border-t border-slate-200 bg-white hover:bg-slate-50">
                            <td class="px-3 py-3">{{ $user->first_name }} {{ $user->last_name }}</td>
                            <td class="px-3 py-3">{{ $user->email }}</td>
                            <td class="px-3 py-3">{{ $user->mobile_number }}</td>
                            <td class="px-3 py-3">{{ ucfirst($user->user_type ?? 'user') }}</td>
                            <td class="px-3 py-3">{{ number_format(optional($user->userwallet)->balance ?? 0, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-slate-500">No users found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold">Recent wallet activity</h3>
                <p class="mt-1 text-sm text-slate-500">Latest wallet transactions across users.</p>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-[700px] w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-slate-500">
                        <th class="px-3 py-3">User</th>
                        <th class="px-3 py-3">Type</th>
                        <th class="px-3 py-3">Amount</th>
                        <th class="px-3 py-3">Purpose</th>
                        <th class="px-3 py-3">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($walletTransactions as $transaction)
                    <tr class="border-t border-slate-200 bg-white hover:bg-slate-50">
                        <td class="px-3 py-3">{{ optional($transaction->user)->first_name }} {{ optional($transaction->user)->last_name }}</td>
                        <td class="px-3 py-3">{{ ucfirst($transaction->transaction_type ?? 'credit') }}</td>
                        <td class="px-3 py-3">{{ number_format($transaction->amount ?? 0, 2) }}</td>
                        <td class="px-3 py-3">{{ $transaction->purpose ?? '-' }}</td>
                        <td class="px-3 py-3">{{ optional($transaction->created_at)->format('M j, Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-3 py-4 text-slate-500">No wallet history found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection