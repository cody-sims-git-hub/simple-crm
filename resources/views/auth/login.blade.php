<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in • SimpleCRM</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    @include('partials.brand-theme')
    <style>
        /* Scale the whole UI up: all Tailwind text & spacing utilities are rem-based. */
        html { font-size: 125%; }
    </style>
</head>
<body class="bg-canvas text-slate-light min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-sm bg-surface border border-hairline rounded-2xl p-8 shadow-xl">
        <div class="mb-6 text-center">
            <h1 class="text-xl font-bold text-accent tracking-tight">SimpleCRM</h1>
            <p class="text-xs text-slate-dim font-mono">Sign in to your pipeline</p>
        </div>

        <div class="mb-6 bg-accent-muted/30 border border-accent/30 rounded-xl p-4 text-xs">
            <p class="text-accent font-semibold mb-2">Try the demo account</p>
            <div class="space-y-1 font-mono text-slate-light">
                <p>Email: <span class="text-slate-light">demo@example.com</span></p>
                <p>Password: <span class="text-slate-light">password</span></p>
            </div>
            <button type="button" onclick="fillDemo()"
                class="mt-3 text-accent hover:underline font-sans font-medium">
                Fill in demo credentials
            </button>
            <p class="mt-3 text-slate-dim font-sans leading-relaxed">
                Sign in with these to explore a pre-loaded pipeline.
                To use the full features with your own data,
                <a href="{{ route('register') }}" class="text-accent hover:underline">create an account</a>.
            </p>
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

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs text-slate mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full bg-canvas border border-hairline rounded-xl px-4 py-2.5 text-sm focus:border-accent focus:outline-none">
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs text-slate">Password</label>
                    <a href="{{ route('password.request') }}" class="text-xs text-accent hover:underline">Forgot password?</a>
                </div>
                <input type="password" name="password" required
                    class="w-full bg-canvas border border-hairline rounded-xl px-4 py-2.5 text-sm focus:border-accent focus:outline-none">
            </div>
            <label class="flex items-center gap-2 text-xs text-slate">
                <input type="checkbox" name="remember" class="rounded border-hairline bg-canvas"> Remember me
            </label>
            <button type="submit"
                class="w-full bg-accent hover:bg-accent-hover text-white font-semibold rounded-xl px-4 py-2.5 text-sm transition">
                Sign in
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-dim">
            No account?
            <a href="{{ route('register') }}" class="text-accent hover:underline">Create one</a>
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
