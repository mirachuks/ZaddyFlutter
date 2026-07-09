<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | ZaddyExpress</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <aside class="w-full bg-slate-900 text-slate-100 lg:w-72 lg:flex lg:flex-col">
            <div class="p-6 border-b border-slate-800">
                <div class="text-xl font-semibold">ZaddyExpress Admin</div>
                <div class="text-sm text-slate-400">Operations dashboard</div>
            </div>
            <nav class="p-4 space-y-2 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Dashboard</a>
                <a href="{{ route('admin.users') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.users') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Users</a>
                <a href="{{ route('admin.active-users') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.active-users') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Active users</a>
                <a href="{{ route('admin.riders') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.riders') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Riders</a>
                <a href="{{ route('admin.orders') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.orders') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Orders</a>
                <a href="{{ route('admin.virtual-accounts') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.virtual-accounts') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Virtual accounts</a>
                <a href="{{ route('admin.rider-wallets') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.rider-wallets') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Rider wallets</a>
                <a href="{{ route('admin.manual-payments') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.manual-payments') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Confirm payments</a>
                <a href="{{ route('admin.assign-job') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.assign-job') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Assign rider to job</a>
                <a href="{{ route('admin.reports') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.reports') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Platform charges</a>
                <a href="{{ route('admin.kyc') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.kyc') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">KYC approvals</a>
                <a href="{{ route('admin.profile-updates') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.profile-updates') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Profile updates</a>
                <a href="{{ route('admin.disputes') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.disputes') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Disputes</a>
                <a href="{{ route('admin.withdrawals') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.withdrawals') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Withdrawals</a>
                <a href="{{ route('admin.settings') }}" class="block rounded-lg px-4 py-3 {{ request()->routeIs('admin.settings') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }}">Settings</a>
            </nav>
        </aside>
        <div class="flex-1 flex flex-col">
            <header class="bg-white border-b border-slate-200 px-4 py-4 sm:px-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-lg font-semibold">@yield('page_title', 'Admin dashboard')</h1>
                    <p class="text-sm text-slate-500">Manage operations, riders, users, and payouts from one place.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-500">{{ auth()->user()->first_name ?? 'Admin' }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-100">Logout</button>
                    </form>
                </div>
            </header>
            <main class="p-4 sm:p-6">
                @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>