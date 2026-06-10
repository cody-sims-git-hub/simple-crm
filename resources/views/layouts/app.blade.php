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
<body class="bg-gray-950 text-gray-100 min-h-screen md:flex">

    {{-- Mobile top bar (hidden on desktop) --}}
    <header class="md:hidden sticky top-0 z-30 flex items-center gap-3 bg-gray-900 border-b border-gray-800 px-4 py-3">
        <button id="navToggle" type="button" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false"
            class="p-2 -ml-1 rounded-lg hover:bg-gray-800 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <h1 class="text-lg font-bold text-emerald-400 tracking-tight">SimpleCRM</h1>
    </header>

    {{-- Backdrop shown when the mobile drawer is open --}}
    <div id="navOverlay" class="hidden fixed inset-0 z-30 bg-black/60 md:hidden"></div>

    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 border-r border-gray-800 flex flex-col p-6 space-y-6 overflow-y-auto
               -translate-x-full transition-transform duration-200 ease-in-out
               md:static md:translate-x-0 md:z-auto">
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

    <main class="flex-1 p-6 md:p-8 overflow-y-auto">
        @if(session('success'))
            <div class="mb-6 bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 p-4 rounded-xl text-sm shadow-xl">
                ✨ {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>

    <script>
        (function () {
            const toggle = document.getElementById('navToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('navOverlay');
            if (!toggle || !sidebar || !overlay) return;

            const open = () => {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            };
            const close = () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
            };

            toggle.addEventListener('click', () =>
                sidebar.classList.contains('-translate-x-full') ? open() : close()
            );
            overlay.addEventListener('click', close);
            // Close after tapping a link so the drawer doesn't linger over the new page.
            sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
        })();
    </script>

</body>
</html>
