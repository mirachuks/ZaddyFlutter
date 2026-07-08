@extends('admin.layouts.app')

@section('page_title', 'Active users')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Active users</h2>
            <p class="mt-1 text-sm text-slate-500">View and manage currently active customer and rider accounts.</p>
        </div>
        <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700">Live list</span>
    </div>

    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search active users" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Search</button>
        </form>
        @if(($search ?? '') !== '')
        <a href="{{ route('admin.active-users') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-[720px] w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-3">Name</th>
                    <th class="py-3">Email</th>
                    <th class="py-3">Role</th>
                    <th class="py-3">Status</th>
                    <th class="py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php $isProtected = $user->hasRole('admin') || $user->id === Auth::id(); @endphp
                <tr class="border-b">
                    <td class="py-3">{{ $user->first_name }} {{ $user->last_name }}</td>
                    <td class="py-3">{{ $user->email }}</td>
                    <td class="py-3">{{ $user->user_type ?? 'user' }}</td>
                    <td class="py-3">{{ $user->status }}</td>
                    <td class="py-3">
                        @if($isProtected)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">Protected</span>
                        @else
                        <form method="POST" action="{{ route('admin.users.status', $user) }}" class="flex gap-2">
                            @csrf
                            <button type="submit" name="status" value="suspended" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs text-rose-700">Suspend</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-4 text-slate-500">No active users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection