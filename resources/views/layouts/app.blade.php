<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NeighborHub') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 overflow-x-hidden">
    
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-[70] w-64 bg-white shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out">
        <div class="flex flex-col h-full p-6">
            <div class="flex justify-between items-center mb-10 px-2">
                <h1 class="text-[#36B3C9] font-black text-2xl uppercase tracking-tighter">NeighborHub</h1>
                <button onclick="toggleSidebar()" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <nav class="flex-1 space-y-2">
                <a href="{{ route('dashboard') }}" class="flex items-center p-4 w-full rounded-2xl font-bold transition-all hover:bg-[#36B3C9] hover:text-white {{ request()->routeIs('dashboard') ? 'bg-[#36B3C9] text-white' : 'text-slate-600' }}">
                     <i class="fas fa-th-large mr-4 text-lg"></i> Dashboard
                </a>
                <a href="{{ route('profile.edit') }}" class="flex items-center p-4 w-full rounded-2xl font-bold transition-all hover:bg-[#36B3C9] hover:text-white {{ request()->routeIs('profile.edit') ? 'bg-[#36B3C9] text-white' : 'text-slate-600' }}">
                    <i class="fas fa-user-circle mr-4 text-lg"></i> My Profile
                </a>
            </nav>
            <div class="pt-6 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full p-4 text-red-500 font-black uppercase tracking-widest text-xs hover:bg-red-50 rounded-2xl transition-all">
                        <i class="fas fa-sign-out-alt mr-4"></i> Log Out
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/40 z-[60] hidden backdrop-blur-sm"></div>

    <div class="relative min-h-screen">
        <div class="fixed top-6 left-6 z-[50]">
            <button onclick="toggleSidebar()" class="bg-white/90 backdrop-blur-md p-4 rounded-2xl shadow-xl border border-slate-100 text-[#36B3C9] hover:scale-110 transition-all active:scale-95">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>

        <main class="pt-28 pb-12 px-6 lg:px-12 max-w-7xl mx-auto relative z-10">
            {{ $slot }}
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>