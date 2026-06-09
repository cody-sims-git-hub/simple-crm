<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SimpleCRM</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        /* Scale the whole UI up: all Tailwind text & spacing utilities are rem-based. */
        html { font-size: 125%; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex">

    <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col p-6 space-y-6">
        <div class="border-b border-gray-800 pb-4">
            <h1 class="text-xl font-bold text-emerald-400 tracking-tight">SimpleCRM</h1>
        </div>
        <nav class="flex-1 space-y-2 text-sm font-medium">
            <a href="{{ route('dashboard') }}" class="block px-4 py-2.5 rounded-xl hover:bg-gray-800 transition">📊 Core Dashboard</a>
            <a href="{{ route('leads.index') }}" class="block px-4 py-2.5 rounded-xl hover:bg-gray-800 transition">📋 Master Pipeline</a>
            <a href="{{ route('reporting') }}" class="block px-4 py-2.5 rounded-xl hover:bg-gray-800 transition">📈 Advanced Analytics</a>
            <a href="/api/leads" target="_blank" class="block px-4 py-2.5 rounded-xl hover:bg-gray-800 transition text-amber-400 font-mono text-xs">📡 GET /api/leads</a>
        </nav>

        @auth
            <div class="border-t border-gray-800 pt-4 space-y-2">
                <p class="px-4 text-xs text-gray-400">Signed in as</p>
                <p class="px-4 text-sm font-semibold text-gray-200 truncate">{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2.5 rounded-xl hover:bg-gray-800 transition text-sm text-rose-400">
                        ⏏ Sign out
                    </button>
                </form>
            </div>
        @endauth
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        @if(session('success'))
            <div class="mb-6 bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 p-4 rounded-xl text-sm shadow-xl">
                ✨ {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>

</body>
</html>