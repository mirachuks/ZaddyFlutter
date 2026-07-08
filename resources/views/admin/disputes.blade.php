@extends('admin.layouts.app')

@section('page_title', 'Disputes')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Dispute center</h2>
            <p class="mt-2 text-sm text-slate-500">Review customer or rider disputes and mark them resolved or escalated.</p>
        </div>
        <form method="GET" class="flex flex-col gap-2 sm:flex-row">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search disputes" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-emerald-300 px-3 py-2 text-sm text-emerald-700">Search</button>
        </form>
    </div>
    @if(($search ?? '') !== '')
    <div class="mt-3"><a href="{{ route('admin.disputes') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear search</a></div>
    @endif
    <div class="mt-5 space-y-3">
        @forelse($disputes as $dispute)
        <div class="rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-medium">{{ $dispute->title ?? 'Dispute #' . $dispute->id }}</div>
                    <div class="text-sm text-slate-500">{{ $dispute->user->first_name ?? 'User' }} {{ $dispute->user->last_name ?? '' }} • {{ $dispute->status }}</div>
                </div>
                <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="resolution_note" placeholder="Resolution note" class="rounded border border-slate-300 px-2 py-1 text-sm">
                    <button type="submit" name="status" value="resolved" class="rounded-lg border border-emerald-300 px-3 py-1.5 text-xs text-emerald-700">Resolve</button>
                </form>
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ $dispute->description }}</p>
        </div>
        @empty
        <div class="rounded-lg border border-slate-200 p-3">No disputes recorded yet.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $disputes->links() }}</div>
</div>
@endsection