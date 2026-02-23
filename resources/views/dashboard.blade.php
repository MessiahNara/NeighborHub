<x-app-layout>
    <div class="min-h-screen bg-slate-50 py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="relative z-50 flex flex-col md:flex-row justify-between items-end md:items-center mb-12 gap-6">
                <div>
                    <h1 class="text-4xl font-black text-black tracking-tight">
                        Welcome, <span class="text-[#00BCD4]">{{ Auth::user()->name }}</span>
                    </h1>
                    <p class="text-slate-500 mt-1 font-medium">Binmaley | Neighborhood Dashboard</p>
                </div>
                
                <div class="relative flex items-center gap-4">
                    <div class="relative inline-block text-left">
                        <button id="notificationBtn" onclick="toggleNotifications()" class="relative p-3 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 text-slate-400 hover:text-[#36B3C9] hover:ring-[#36B3C9] transition-all group focus:outline-none">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            
                            <span id="notifBadge" class="hidden absolute top-2 right-2.5 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-white"></span>
                            </span>
                        </button>

                        <div id="notificationTab" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-[2.5rem] shadow-2xl ring-1 ring-black ring-opacity-5 transition-all transform origin-top-right overflow-hidden border border-slate-100">
                            
                            <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter leading-none">Updates</h3>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">Neighborhood</p>
                                </div>
                                <button onclick="clearAllNotifications()" class="text-[10px] font-black text-red-400 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-xl transition-colors shadow-sm">
                                    Clear
                                </button>
                            </div>

                            <div id="notifList" class="max-h-80 overflow-y-auto scrollbar-hide">
                                </div>
                            
                            <div class="p-4 bg-slate-50 text-center border-t border-slate-100">
                                <button onclick="toggleNotifications()" class="text-[9px] font-black text-slate-400 uppercase tracking-widest hover:text-[#36B3C9] transition-colors">Close notification</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-6 pb-24">
                <a href="{{ route('category.show', 'buy-sell') }}" class="relative group bg-slate-900 p-8 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span class="font-bold text-white text-lg relative z-10">Buy & Sell</span>
                    @if($counts['buy_sell'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-slate-800 text-xs font-bold px-3 py-1 rounded-full shadow-lg z-20">{{ $counts['buy_sell'] }}</span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'borrow') }}" class="relative group bg-[#36B3C9] p-8 rounded-[2.5rem] shadow-xl shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span class="font-bold text-white text-lg">Borrow</span>
                    @if($counts['borrow'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-slate-800 text-xs font-bold px-3 py-1 rounded-full shadow-lg z-20">{{ $counts['borrow'] }}</span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'services') }}" class="relative group bg-slate-900 p-8 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span class="font-bold text-white text-lg relative z-10">Services</span>
                    @if($counts['services'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-slate-800 text-xs font-bold px-3 py-1 rounded-full shadow-lg z-20">{{ $counts['services'] }}</span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'events') }}" class="relative group bg-[#36B3C9] p-8 rounded-[2.5rem] shadow-xl shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="font-bold text-white text-lg">Events</span>
                    @if($counts['events'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-slate-800 text-xs font-bold px-3 py-1 rounded-full shadow-lg z-20">{{ $counts['events'] }}</span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'places') }}" class="relative group bg-slate-900 p-8 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="font-bold text-white text-lg relative z-10">Places</span>
                    @if($counts['places'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-slate-800 text-xs font-bold px-3 py-1 rounded-full shadow-lg z-20">{{ $counts['places'] }}</span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'announce') }}" class="relative group bg-[#36B3C9] p-8 rounded-[2.5rem] shadow-xl shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                    <span class="font-bold text-white text-lg">Announcements</span>
                    @if($counts['announce'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-slate-800 text-xs font-bold px-3 py-1 rounded-full shadow-lg z-20">{{ $counts['announce'] }}</span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'complaints') }}" class="relative group bg-slate-900 p-8 rounded-[2.5rem] shadow-xl hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <svg class="w-10 h-10 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span class="font-bold text-white text-lg relative z-10">Complaint</span>
                    @if($counts['complaints'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-slate-800 text-xs font-bold px-3 py-1 rounded-full shadow-lg z-20">{{ $counts['complaints'] }}</span>
                    @endif
                </a>

                <a href="{{ route('category.show', 'requests') }}" class="relative group bg-[#36B3C9] p-8 rounded-[2.5rem] shadow-xl shadow-cyan-500/30 hover:shadow-cyan-500/50 hover:-translate-y-2 transition-all duration-300 flex flex-col items-center justify-center gap-4 text-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="font-bold text-white text-lg">Request File</span>
                    @if($counts['requests'] > 0)
                    <span class="absolute top-5 right-5 bg-white text-slate-800 text-xs font-bold px-3 py-1 rounded-full shadow-lg z-20">{{ $counts['requests'] }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>

    <script>
        // 1. Get real data from Laravel
        const currentCounts = @json($counts);
        const recentUpdates = @json($recentUpdates ?? []); 
        
        // 2. Configuration for "Identity" (Icons/Names)
        const categoryConfig = {
            'buy-sell':      { name: 'Buy & Sell', icon: 'fa-store' },
            'borrow':        { name: 'Borrow',     icon: 'fa-hand-holding' },
            'services':      { name: 'Services',   icon: 'fa-tools' },
            'events':        { name: 'Events',     icon: 'fa-calendar-alt' },
            'places':        { name: 'Places',     icon: 'fa-map-marked-alt' },
            'announcements': { name: 'Announcements', icon: 'fa-bullhorn' },
            'complaints':    { name: 'Complaints', icon: 'fa-exclamation-triangle' },
            'requests':      { name: 'Requests',   icon: 'fa-file-signature' }
        };

        // 3. Load saved state from LocalStorage
        let readNotifs = JSON.parse(localStorage.getItem('neighborhub_read_notifs')) || [];
        
        const badge = document.getElementById('notifBadge');
        const notifList = document.getElementById('notifList');

        function renderNotifications() {
            let hasNew = false;
            let html = '';

            if (recentUpdates && recentUpdates.length > 0) {
                recentUpdates.forEach(post => {
                    // If this specific post ID is NOT in our read array, it's a new notification!
                    if (!readNotifs.includes(post.id)) {
                        hasNew = true;
                        
                        // Map the db category back to the exact URL slug needed
                        const routeMap = { 'announcements': 'announce' };
                        const routeCat = routeMap[post.category] || post.category;
                        
                        // Creates a link to the category page with the exact Post ID attached
                        const url = `/category/${routeCat}?post=${post.id}`;
                        const config = categoryConfig[post.category] || { name: post.category, icon: 'fa-bell' };

                        html += `
                            <a href="${url}" onclick="markAsRead(${post.id})" class="flex items-center gap-4 p-5 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0 group">
                                <div class="h-11 w-11 rounded-2xl bg-[#36B3C9]/10 flex items-center justify-center text-[#36B3C9] group-hover:scale-110 transition-transform">
                                    <i class="fas ${config.icon} text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] font-bold text-[#36B3C9] uppercase tracking-wider mb-0.5">${config.name}</p>
                                    <p class="text-sm font-black text-slate-700 leading-tight truncate">${post.title}</p>
                                    <p class="text-[9px] font-bold text-slate-400 mt-1"><i class="fas fa-user mr-1"></i> ${post.user ? post.user.name : 'Neighbor'}</p>
                                </div>
                            </a>
                        `;
                    }
                });
            }

            // Show/Hide Red Dot based on actual unread status
            if (hasNew) {
                if(badge) badge.classList.remove('hidden');
                notifList.innerHTML = html;
            } else {
                if(badge) badge.classList.add('hidden');
                notifList.innerHTML = `
                    <div class="p-12 text-center opacity-70">
                        <div class="bg-slate-100 h-16 w-16 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest leading-snug">All caught up!<br>No new posts.</p>
                    </div>
                `;
            }
        }

        // Run on Page Load
        renderNotifications();

        // Save exactly which post you clicked
        window.markAsRead = function(postId) {
            readNotifs.push(postId);
            localStorage.setItem('neighborhub_read_notifs', JSON.stringify(readNotifs));
        };

        // Push ALL current post IDs into the read array to clear everything at once
        window.clearAllNotifications = function() {
            if (recentUpdates) {
                recentUpdates.forEach(post => {
                    if (!readNotifs.includes(post.id)) {
                        readNotifs.push(post.id);
                    }
                });
                localStorage.setItem('neighborhub_read_notifs', JSON.stringify(readNotifs));
                renderNotifications();
            }
        };

        // Open/Close Tab Logic
        function toggleNotifications() {
            const tab = document.getElementById('notificationTab');
            const btn = document.getElementById('notificationBtn');
            tab.classList.toggle('hidden');

            // Logic to close when clicking outside
            if (!tab.classList.contains('hidden')) {
                const closeHandler = (e) => {
                    if (!tab.contains(e.target) && !btn.contains(e.target)) {
                        tab.classList.add('hidden');
                        document.removeEventListener('click', closeHandler);
                    }
                };
                setTimeout(() => document.addEventListener('click', closeHandler), 10);
            }
        }
    </script>
</x-app-layout>