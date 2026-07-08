<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register | ZaddyExpress</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-lg">
        <h1 class="text-2xl font-semibold">Create Admin Account</h1>
        <p class="mt-2 text-sm text-slate-500">Create a secure administrator profile for the web dashboard.</p>

        @if ($errors->any())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-600">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.register.post') }}" class="mt-6 space-y-4">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">First name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Last name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Mobile</label>
                <input type="text" name="mobile_number" value="{{ old('mobile_number') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Password</label>
                    <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Confirm password</label>
                    <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
            <button class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-medium text-white">Create admin</button>
            <p class="text-center text-sm text-slate-500"><a href="{{ route('admin.login') }}" class="text-emerald-600">Back to login</a></p>
        </form>
    </div>
</body>

</html>