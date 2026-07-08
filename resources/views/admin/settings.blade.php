@extends('admin.layouts.app')

@section('page_title', 'Settings')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Admin profile</h2>
        <p class="mt-2 text-sm text-slate-500">Update your profile details or password.</p>
        <form method="POST" action="{{ route('admin.settings.profile') }}" class="mt-5 space-y-3">
            @csrf
            <input type="text" name="first_name" value="{{ auth()->user()->first_name ?? '' }}" placeholder="First name" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <input type="text" name="last_name" value="{{ auth()->user()->last_name ?? '' }}" placeholder="Last name" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <input type="text" name="mobile_number" value="{{ auth()->user()->mobile_number ?? '' }}" placeholder="Mobile number" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Save profile</button>
        </form>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Change password</h2>
        <p class="mt-2 text-sm text-slate-500">Keep the web portal secure.</p>
        <form method="POST" action="{{ route('admin.settings.password') }}" class="mt-5 space-y-3">
            @csrf
            <input type="password" name="current_password" placeholder="Current password" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <input type="password" name="password" placeholder="New password" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <input type="password" name="password_confirmation" placeholder="Confirm new password" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Update password</button>
        </form>
    </div>
</div>
@endsection