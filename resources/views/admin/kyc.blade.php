@extends('admin.layouts.app')

@section('page_title', 'KYC approvals')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Rider KYC review</h2>
            <p class="mt-1 text-sm text-slate-500">Search KYC requests by rider name, email, NIN, or status.</p>
        </div>
        <span class="rounded-full bg-amber-100 px-3 py-1 text-sm text-amber-700">Manual review</span>
    </div>
    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search KYC requests" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Search</button>
        </form>
        @if(($search ?? '') !== '')
        <a href="{{ route('admin.kyc') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </div>
    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-[720px] w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-3">Rider</th>
                    <th class="py-3">NIN</th>
                    <th class="py-3">Status</th>
                    <th class="py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($profiles as $profile)
                <tr class="border-b">
                    <td class="py-3">{{ $profile->user->first_name ?? 'Rider' }} {{ $profile->user->last_name ?? '' }}</td>
                    <td class="py-3">{{ $profile->nin ?? 'N/A' }}</td>
                    <td class="py-3">{{ $profile->status ?? 'pending' }}</td>
                    <td class="py-3">
                        <form method="POST" action="{{ route('admin.kyc.review', $profile) }}" class="flex gap-2">
                            @csrf
                            <button type="submit" name="status" value="approved" class="rounded-lg border border-emerald-300 px-3 py-1.5 text-xs text-emerald-700">Approve</button>
                            <button type="submit" name="status" value="rejected" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs text-rose-700">Reject</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-4 text-slate-500">No KYC requests found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $profiles->links() }}</div>
</div>
@endsection