@extends('admin.layouts.app')

@section('page_title', 'Riders')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Rider management</h2>
            <p class="mt-1 text-sm text-slate-500">Search riders by name, email, phone, or service zone.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-600">Approval + suspension</span>
    </div>
    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search riders" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Search</button>
        </form>
        @if(($search ?? '') !== '')
        <a href="{{ route('admin.riders') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </div>
    <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-[900px] w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="py-3">Rider</th>
                    <th class="py-3">Status</th>
                    <th class="py-3">Service zone</th>
                    <th class="py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riders as $rider)
                @php $profile = $rider->riderProfile; @endphp
                <tr class="border-b align-top">
                    <td class="py-3">
                        {{ $rider->first_name ?? 'Rider' }} {{ $rider->last_name ?? '' }}
                        <div class="text-xs text-slate-500">{{ $profile->legal_name ?? 'Pending rider profile' }}</div>
                    </td>
                    <td class="py-3">{{ $profile->status ?? $rider->status ?? 'pending' }}</td>
                    <td class="py-3">{{ $profile->service_zone ?? 'N/A' }}</td>
                    <td class="py-3 space-y-2">
                        <button type="button" onclick="document.getElementById('rider-actions-{{ $rider->id }}').classList.toggle('hidden')" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">Manage rider</button>
                        <div id="rider-actions-{{ $rider->id }}" class="hidden space-y-2">
                            @if($profile)
                            <form method="POST" action="{{ route('admin.riders.status', $profile) }}" class="flex gap-2">
                                @csrf
                                <button type="submit" name="status" value="approved" class="rounded-lg border border-emerald-300 px-3 py-1.5 text-xs text-emerald-700">Approve</button>
                                <button type="submit" name="status" value="suspended" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs text-rose-700">Suspend</button>
                            </form>
                            <form method="POST" action="{{ route('admin.riders.update', $profile) }}" class="space-y-2 rounded-lg border border-slate-200 p-2">
                                @csrf
                                <input type="text" name="legal_name" value="{{ $profile->legal_name ?? '' }}" placeholder="Legal name" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                <input type="text" name="service_zone" value="{{ $profile->service_zone ?? '' }}" placeholder="Service zone" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                <input type="text" name="plate_number" value="{{ $profile->plate_number ?? '' }}" placeholder="Plate number" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">Save profile</button>
                            </form>
                            <form method="POST" action="{{ route('admin.riders.notify', $profile) }}" class="space-y-2 rounded-lg border border-slate-200 p-2">
                                @csrf
                                <textarea name="message" rows="2" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" placeholder="Send rider notice"></textarea>
                                <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">Notify rider</button>
                            </form>
                            @else
                            <span class="text-xs text-slate-500">No rider profile found yet.</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-4 text-slate-500">No riders found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $riders->links() }}</div>
</div>
@endsection