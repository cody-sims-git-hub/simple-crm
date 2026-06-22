<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password • SimpleCRM</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-slate-light min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-sm bg-surface border border-hairline rounded-2xl p-8 shadow-xl">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-accent tracking-tight">SimpleCRM</h1>
            <p class="text-xs text-slate-dim font-mono">Reset your password</p>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-success/10 border border-success/30 text-success p-3 rounded-xl text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-rose-950/40 border border-rose-500/30 text-rose-300 p-3 rounded-xl text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <p class="mb-4 text-xs text-slate leading-relaxed">
            Enter the email address for your account and we'll send you a link to set a new password.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-slate mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-canvas border border-hairline rounded-xl px-4 py-2.5 text-sm focus:border-accent focus:outline-none">
            </div>
            <button type="submit"
                class="w-full bg-accent hover:bg-accent-hover text-white font-semibold rounded-xl px-4 py-2.5 text-sm transition">
                Send reset link
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-dim">
            Remembered it?
            <a href="{{ route('login') }}" class="text-accent hover:underline">Back to sign in</a>
        </p>
    </div>

</body>
</html>
