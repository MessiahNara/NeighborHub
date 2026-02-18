<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-end md:items-center mb-12 gap-6">
                <div>
                    <h1 class="text-4xl font-black text-slate-800 tracking-tight">
                        Welcome, {{ Auth::user()->name }}!
                    </h1>
                    <p class="text-slate-500 mt-1 font-medium">Binmaley | Neighborhood Dashboard</p>
                </div>
                
                <div class="relative w-full md:w-80 group">
                    <input type="text" placeholder="Search NeighborHub..." 
                           class="w-full pl-12 pr-4 py-3 rounded-2xl border-none bg-white shadow-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-[#36B3C9] transition-all text-slate-600">
                    <svg class="w-6 h-6 text-slate-400 absolute left-4 top-3 group-focus-within:text-[#36B3C9] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-6 pb-24">

                <a href="{{ route('category.show', 'buy-sell') }}" class="relative group bg-slate-900 p-8 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span class="font-bold text-white text-lg relative z-10">Buy & Sell</span>
                    
                    @if($counts['buy_sell'] > 0)
                    <span class="absolute top-5 right-5 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg border-2 border-slate-900 z-20">
                        {{ $counts['buy_sell'] }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'borrow') }}" class="relative group bg-[#36B3C9] p-8 rounded-[2.5rem] shadow-xl shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span class="font-bold text-white text-lg">Borrow</span>
                    @if($counts['borrow'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-[#36B3C9] text-xs font-bold px-2 py-1 rounded-full shadow-lg border-2 border-[#36B3C9]">
                        {{ $counts['borrow'] }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'services') }}" class="relative group bg-slate-900 p-8 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span class="font-bold text-white text-lg relative z-10">Services</span>
                    @if($counts['services'] > 0)
                    <span class="absolute top-5 right-5 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg border-2 border-slate-900">
                        {{ $counts['services'] }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'events') }}" class="relative group bg-[#36B3C9] p-8 rounded-[2.5rem] shadow-xl shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="font-bold text-white text-lg">Events</span>
                    @if($counts['events'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-[#36B3C9] text-xs font-bold px-2 py-1 rounded-full shadow-lg border-2 border-[#36B3C9]">
                        {{ $counts['events'] }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'places') }}" class="relative group bg-slate-900 p-8 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="font-bold text-white text-lg relative z-10">Places</span>
                    @if($counts['places'] > 0)
                    <span class="absolute top-5 right-5 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg border-2 border-slate-900">
                        {{ $counts['places'] }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'announce') }}" class="relative group bg-[#36B3C9] p-8 rounded-[2.5rem] shadow-xl shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                    <span class="font-bold text-white text-lg">Announce</span>
                    @if($counts['announce'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-[#36B3C9] text-xs font-bold px-2 py-1 rounded-full shadow-lg border-2 border-[#36B3C9]">
                        {{ $counts['announce'] }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'complaints') }}" class="relative group bg-slate-900 p-8 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="font-bold text-white text-lg relative z-10">Complaints</span>
                    @if($counts['complaints'] > 0)
                    <span class="absolute top-5 right-5 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg border-2 border-slate-900">
                        {{ $counts['complaints'] }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'requests') }}" class="relative group bg-[#36B3C9] p-8 rounded-[2.5rem] shadow-xl shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="font-bold text-white text-lg">Request File</span>
                    @if($counts['requests'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-[#36B3C9] text-xs font-bold px-2 py-1 rounded-full shadow-lg border-2 border-[#36B3C9]">
                        {{ $counts['requests'] }}
                    </span>
                    @endif
                </a>

            </div>
        </div>
    </div>
</x-app-layout>