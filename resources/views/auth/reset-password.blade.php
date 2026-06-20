<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose a new password • SimpleCRM</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        /* Scale the whole UI up: all Tailwind text & spacing utilities are rem-based. */
        html { font-size: 125%; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-sm bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-emerald-400 tracking-tight">SimpleCRM</h1>
            <p class="text-xs text-gray-500 font-mono">Choose a new password</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-rose-950/40 border border-rose-500/30 text-rose-300 p-3 rounded-xl text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="block text-xs text-gray-400 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                    class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-sm text-gray-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">New password</label>
                <input type="password" name="password" required autofocus
                    class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Confirm new password</label>
                <input type="password" name="password_confirmation" required
                    class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
            </div>
            <button type="submit"
                class="w-full bg-emerald-500 hover:bg-emerald-400 text-gray-950 font-semibold rounded-xl px-4 py-2.5 text-sm transition">
                Reset password
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-gray-500">
            <a href="{{ route('login') }}" class="text-emerald-400 hover:underline">Back to sign in</a>
        </p>
    </div>

</body>
</html>
