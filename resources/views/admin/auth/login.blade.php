<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ZaddyExpress</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-lg">
        <h1 class="text-2xl font-semibold">Admin Login</h1>
        <p class="mt-2 text-sm text-slate-500">Sign in to manage riders, jobs, commissions, and support workflows.</p>

        @if ($errors->any())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-600">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Password</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
                <a href="{{ route('admin.register') }}" class="text-emerald-600">Create admin</a>
            </div>
            <button class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-medium text-white">Sign in</button>
        </form>
    </div>
</body>

</html>