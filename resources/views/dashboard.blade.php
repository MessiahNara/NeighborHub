<x-app-layout>
    <div class="bg-slate-50 min-h-screen font-sans antialiased text-slate-900 pb-32">
        <header class="p-8 max-w-4xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-black text-[#36B3C9] tracking-tighter">NeighborHub</h1>
                <p class="font-bold uppercase tracking-widest text-[10px] text-slate-400 mt-1">Binmaley | Welcome, {{ Auth::user()->name }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="h-10 px-4 bg-white rounded-full shadow-sm text-red-500 font-bold text-[10px] uppercase">Logout</button>
            </form>
        </header>

        <main class="max-w-4xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @php
                    $btns = [
                        ['n' => 'Buy & Sell', 'i' => 'fa-cart-shopping', 'c' => 'bg-[#121212]', 'id' => 'buy_sell'],
                        ['n' => 'Borrow', 'i' => 'fa-hand-holding-heart', 'c' => 'bg-[#36B3C9]', 'id' => 'borrow'],
                        ['n' => 'Services', 'i' => 'fa-screwdriver-wrench', 'c' => 'bg-[#121212]', 'id' => 'service'],
                        ['n' => 'Events', 'i' => 'fa-calendar-check', 'c' => 'bg-[#36B3C9]', 'id' => 'event'],
                        ['n' => 'Places', 'i' => 'fa-map-location-dot', 'c' => 'bg-[#121212]', 'id' => 'place'],
                        ['n' => 'Announce', 'i' => 'fa-bullhorn', 'c' => 'bg-[#36B3C9]', 'id' => 'announcement'],
                        ['n' => 'Complaints', 'i' => 'fa-circle-exclamation', 'c' => 'bg-[#121212]', 'id' => 'complaint'],
                        ['n' => 'Request File', 'i' => 'fa-file-invoice', 'c' => 'bg-[#36B3C9]', 'id' => 'request'],
                    ];
                @endphp

                @foreach($btns as $b)
                <a href="{{ route('category.show', $b['id']) }}" class="{{ $b['c'] }} rounded-[2.5rem] h-40 p-6 flex flex-col items-center justify-center transition hover:scale-105 shadow-xl relative group">
                    <span class="absolute top-4 right-6 bg-white/20 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $counts[$b['id']] ?? 0 }}</span>
                    <i class="fas {{ $b['i'] }} text-white text-3xl mb-3"></i>
                    <span class="text-white font-bold text-sm text-center leading-tight">{{ $b['n'] }}</span>
                </a>
                @endforeach
            </div>
        </main>
    </div>
</x-app-layout>