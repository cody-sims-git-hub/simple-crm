<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in • SimpleCRM</title>
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
            <p class="text-xs text-gray-500 font-mono">Sign in to your pipeline</p>
        </div>

        <div class="mb-6 bg-emerald-950/30 border border-emerald-500/30 rounded-xl p-4 text-xs">
            <p class="text-emerald-300 font-semibold mb-2">Try the demo account</p>
            <div class="space-y-1 font-mono text-gray-300">
                <p>Email: <span class="text-gray-100">demo@example.com</span></p>
                <p>Password: <span class="text-gray-100">password</span></p>
            </div>
            <button type="button" onclick="fillDemo()"
                class="mt-3 text-emerald-400 hover:underline font-sans font-medium">
                Fill in demo credentials
            </button>
            <p class="mt-3 text-gray-500 font-sans leading-relaxed">
                Sign in with these to explore a pre-loaded pipeline.
                To use the full features with your own data,
                <a href="{{ route('register') }}" class="text-emerald-400 hover:underline">create an account</a>.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-4 bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 p-3 rounded-xl text-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-rose-950/40 border border-rose-500/30 text-rose-300 p-3 rounded-xl text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-400 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs text-gray-400">Password</label>
                    <a href="{{ route('password.request') }}" class="text-xs text-emerald-400 hover:underline">Forgot password?</a>
                </div>
                <input type="password" name="password" required
                    class="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
            </div>
            <label class="flex items-center gap-2 text-xs text-gray-400">
                <input type="checkbox" name="remember" class="rounded border-gray-700 bg-gray-950"> Remember me
            </label>
            <button type="submit"
                class="w-full bg-emerald-500 hover:bg-emerald-400 text-gray-950 font-semibold rounded-xl px-4 py-2.5 text-sm transition">
                Sign in
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-gray-500">
            No account?
            <a href="{{ route('register') }}" class="text-emerald-400 hover:underline">Create one</a>
        </p>
    </div>

    <script>
        function fillDemo() {
            document.querySelector('input[name="email"]').value = 'demo@example.com';
            document.querySelector('input[name="password"]').value = 'password';
        }
    </script>

</body>
</html>
