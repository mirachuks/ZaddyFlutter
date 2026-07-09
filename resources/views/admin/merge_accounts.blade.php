@extends('admin.layouts.app')

@section('page_title', 'Assign Rider to Order')

@section('content')
<div class="rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold">Assign a rider to a job</h2>
            <p class="mt-1 text-sm text-slate-500">Pick a rider and assign them directly to an open job. This creates an accepted application and updates the job status.</p>
        </div>
    </div>

    @if ($errors->any())
    <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (session('success'))
    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.assign-job.post') }}" class="mt-6 space-y-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Select rider</label>
                <select name="rider_user_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Choose a rider</option>
                    @foreach($riders as $rider)
                    <option value="{{ $rider->id }}" {{ old('rider_user_id', $selectedRider->id ?? '') == $rider->id ? 'selected' : '' }}>
                        {{ $rider->first_name }} {{ $rider->last_name }} - {{ $rider->email }} @if($rider->mobile_number) ({{ $rider->mobile_number }})@endif
                    </option>
                    @endforeach
                </select>
                <div class="mt-2 text-xs text-slate-500">Select a rider by name, email, or phone.</div>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Select open job</label>
                <select name="job_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Choose an open job</option>
                    @foreach($openJobs as $job)
                    <option value="{{ $job->id }}" {{ old('job_id', $selectedJob->id ?? '') == $job->id ? 'selected' : '' }}>
                        Job #{{ $job->id }} - {{ $job->title ?? 'Untitled' }} ({{ $job->user?->email ?? 'no customer email' }})
                    </option>
                    @endforeach
                </select>
                <div class="mt-2 text-xs text-slate-500">Choose the open job to assign this rider to.</div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <p class="font-semibold">How assignment works</p>
            <ul class="list-disc space-y-1 pl-5 pt-2">
                <li>An accepted application record is created for the selected rider.</li>
                <li>Any pending applications on the job are rejected.</li>
                <li>The job status is updated to <strong>accepted</strong>.</li>
                <li>The rider receives a notification about the assignment.</li>
                <li>The job poster is notified that a rider was assigned.</li>
            </ul>
        </div>

        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Assign rider to job</button>
    </form>
</div>
@endsection