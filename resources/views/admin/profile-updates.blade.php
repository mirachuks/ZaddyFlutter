@extends('admin.layouts.app')

@section('page_title', 'Profile Update Requests')

@section('content')
<div class="grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Pending requests</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['pending'] }}</div>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Approved</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['approved'] }}</div>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Rejected</div>
        <div class="mt-2 text-3xl font-semibold">{{ $stats['rejected'] }}</div>
    </div>
</div>

<div class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold">Pending profile update requests</h2>
    <p class="mt-2 text-sm text-slate-500">Review and approve rider profile changes before they are committed.</p>

    @if ($requests->count() === 0)
    <div class="mt-6 rounded-lg border border-slate-200 p-8 text-center text-slate-500">
        <p>No pending profile updates at this time.</p>
    </div>
    @else
    <div class="mt-6 space-y-4">
        @foreach ($requests as $request)
        <div class="rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold">{{ $request->user->first_name }} {{ $request->user->last_name }}</h3>
                    <p class="text-sm text-slate-500">{{ $request->user->email }} · Requested {{ $request->created_at->diffForHumans() }}</p>
                </div>
                <button type="button" class="text-sm text-slate-500 hover:text-slate-700" onclick="toggleDetails('request-{{ $request->id }}')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <div id="request-{{ $request->id }}" class="hidden mt-4">
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Current profile</h4>
                        @foreach ($request->old_data as $field => $value)
                        <div class="mb-3">
                            <label class="block text-xs text-slate-600 uppercase">{{ str_replace('_', ' ', $field) }}</label>
                            <p class="text-sm font-medium text-slate-900">{{ $value ?? '(empty)' }}</p>
                        </div>
                        @endforeach
                    </div>

                    <div class="rounded-lg bg-emerald-50 p-4">
                        <h4 class="text-sm font-semibold text-emerald-700 mb-3">Requested changes</h4>
                        @foreach ($request->new_data as $field => $value)
                        <div class="mb-3">
                            <label class="block text-xs text-emerald-600 uppercase">{{ str_replace('_', ' ', $field) }}</label>
                            <p class="text-sm font-medium text-emerald-900">{{ $value ?? '(empty)' }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <form method="POST" action="{{ route('admin.profile-updates.approve', $request->id) }}" class="space-y-3">
                        @csrf
                        <textarea name="admin_note" placeholder="Optional admin note (max 500 chars)" class="w-full rounded-lg border border-slate-300 p-3 text-sm" maxlength="500" rows="3"></textarea>
                        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Approve
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.profile-updates.reject', $request->id) }}" class="space-y-3">
                        @csrf
                        <textarea name="admin_note" placeholder="Reason for rejection (max 500 chars)" class="w-full rounded-lg border border-slate-300 p-3 text-sm" maxlength="500" rows="3"></textarea>
                        <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Reject
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if ($requests->hasPages())
    <div class="mt-6">
        {{ $requests->links() }}
    </div>
    @endif
    @endif
</div>

<script>
    function toggleDetails(id) {
        const element = document.getElementById(id);
        element.classList.toggle('hidden');
    }
</script>

<style>
    .prose table {
        width: 100%;
        border-collapse: collapse;
    }

    .prose th,
    .prose td {
        border: 1px solid #e2e8f0;
        padding: 0.5rem;
        text-align: left;
    }

    .prose th {
        background-color: #f1f5f9;
        font-weight: 600;
    }
</style>
@endsection