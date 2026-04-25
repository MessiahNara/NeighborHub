<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <title>NeighborHub | {{ str_replace(['_', '-'], ' ', $type ?? 'category') }}</title>
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        #detImg { scroll-behavior: smooth; -webkit-overflow-scrolling: touch; }
        .animate-pop { animation: pop 0.4s cubic-bezier(0.26, 0.53, 0.74, 1.48); }
        @keyframes pop { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .fc { background: white; border-radius: 2.5rem; padding: 2rem; border: none !important; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); }
        .fc-toolbar-title { font-weight: 900 !important; text-transform: uppercase; color: #1e293b; font-size: 1.5rem !important; }
        .fc-button-primary { background-color: #36B3C9 !important; border: none !important; border-radius: 1rem !important; font-weight: bold !important; }
        .fc-daygrid-event { background-color: #36B3C9; border: none; border-radius: 6px; padding: 2px 6px; cursor: pointer; }
        .fc-day-today { background-color: #f1fbfd !important; border-radius: 1.5rem; }
        .tag-checkbox:checked + label { background-color: #36B3C9; color: white; border-color: #36B3C9; }

        /* Custom Styles for Map Labels (Names on top of pins) */
        .leaflet-tooltip.landmark-label {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            font-weight: 900;
            color: #1e293b;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 10px;
        }
        .leaflet-tooltip-top.landmark-label::before { border-top-color: rgba(255, 255, 255, 0.95); }
        
        .leaflet-tooltip.welcome-label {
            background: #e11d48; /* Red 600 */
            color: white;
            border: 2px solid white;
            border-radius: 9999px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            font-weight: 900;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 6px 14px;
        }
        .leaflet-tooltip-top.welcome-label::before { border-top-color: #e11d48; }

        /* Modern card hover */
        .post-item { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .post-item:hover { transform: translateY(-4px); }

        /* Smooth filter tab transitions */
        .filter-tab { transition: all 0.15s ease; }
    </style>
</head>

@php
    $normalizedType = str_replace('_', '-', $type);
    
    // Format Title cleanly
    $displayTitle = str_replace(['_', '-'], ' ', $type);
    if ($normalizedType === 'buy-sell') {
        $displayTitle = 'Buy & Sell';
    }
    
    $isPlaces    = ($normalizedType === 'places');
    $isBuySell   = (str_contains($normalizedType, 'buy') && str_contains($normalizedType, 'sell'));
    $isBorrow    = ($normalizedType === 'borrow');
    $isEvent     = in_array($normalizedType, ['event', 'events']);
    $isComplaint = ($normalizedType === 'complaints');
    $isRequest   = ($normalizedType === 'requests');
    $isWishlist  = ($normalizedType === 'wishlist');
    
    // Only allow Wishlists on these specific categories
    $hasWishlistFeature = in_array($normalizedType, ['buy-sell', 'borrow', 'services', 'wishlist']);
    
    // ALL categories can pin a location now
    $showLocation = true;

    $adminOnlyCategories = ['events', 'places', 'announcements', 'announce', 'event'];
    $isAdminOnly = in_array($normalizedType, $adminOnlyCategories);
    $isAdmin     = (Auth::check() && Auth::user()->role === 'admin');
    $isModerator = (Auth::check() && Auth::user()->role === 'moderator');

    // CHECK IF CATEGORY REQUIRES VERIFICATION (Added requests & complaints)
    $requiresVerification = in_array($normalizedType, ['buy-sell', 'borrow', 'services', 'complaints', 'requests']);
    $isVerified = (Auth::check() && Auth::user()->is_verified == 1);
    $canPost = !$requiresVerification || $isVerified || $isAdmin;

    $useGrid = ($isBuySell || $isBorrow || $isWishlist || in_array($normalizedType, ['services', 'places']));
    
    $searchSlug = $normalizedType;
    if($searchSlug === 'announce') $searchSlug = 'announcements';
    if($searchSlug === 'request')  $searchSlug = 'requests';
    if($searchSlug === 'event')    $searchSlug = 'events';

    $availableTags = \App\Models\Tag::where('category_slug', $searchSlug)->pluck('name')->toArray() ?? [];
    
    $filter = request('filter', 'all');
    if ($filter == 'my_posts') {
        $posts = collect($posts)->where('user_id', Auth::id())->where('transaction_status', '!=', 'sold');
    } elseif ($filter == 'history') {
        $posts = collect($posts)->where('user_id', Auth::id())->where('transaction_status', 'sold');
    } elseif ($filter == 'wishlist' && Auth::check()) {
        $wishlistIds = \App\Models\Wishlist::where('user_id', Auth::id())->pluck('post_id')->toArray();
        $posts = collect($posts)->whereIn('id', $wishlistIds);
    } elseif ($filter == 'my_borrows' && Auth::check()) {
        // Items where I am NOT the owner, but I have a conversation about it (Items I want to borrow/buy)
        $myConversations = \App\Models\Conversation::where('sender_id', Auth::id())->orWhere('receiver_id', Auth::id())->pluck('post_id')->toArray();
        $posts = collect($posts)->whereIn('id', $myConversations)->where('user_id', '!=', Auth::id());
    } else {
        // Default 'all' view hides sold items
        $posts = collect($posts)->where('transaction_status', '!=', 'sold');
    }

    $calendarEvents = [];
    if($isEvent && isset($posts)) {
        foreach($posts as $p) {
            if($p->event_date) {
                try {
                    $calendarEvents[] = [
                        'id' => $p->id,
                        'title' => $p->title,
                        'start' => \Carbon\Carbon::parse($p->event_date)->format('Y-m-d'),
                    ];
                } catch(\Exception $e) {}
            }
        }
    }

    $unreadCount = 0;
    if (Auth::check()) {
        $unreadCount = \App\Models\Message::where('is_read', false)
            ->where('user_id', '!=', Auth::id())
            ->whereHas('conversation', function($q) {
                $q->where(function($query) {
                    $query->where('sender_id', Auth::id())
                          ->orWhere('receiver_id', Auth::id());
                });
            })->count();
    }
@endphp

<body class="bg-slate-50 font-sans antialiased min-h-screen text-slate-900 overflow-x-hidden">

    @if(session('success'))
        <div id="flash-message" class="fixed top-6 left-1/2 -translate-x-1/2 z-[400] bg-green-50 border border-green-200 text-green-700 px-6 py-3 rounded-2xl shadow-lg animate-pop flex items-center gap-3">
            <i class="fas fa-check-circle text-lg"></i>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div id="flash-message" class="fixed top-6 left-1/2 -translate-x-1/2 z-[400] bg-red-50 border border-red-200 text-red-700 px-6 py-3 rounded-2xl shadow-lg animate-pop flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-lg"></i>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    {{-- NAV --}}
    <nav class="sticky top-0 z-[100] bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 py-3">
        <div class="max-w-6xl mx-auto flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="h-10 w-10 bg-slate-100 text-slate-400 rounded-xl flex items-center justify-center transition hover:bg-[#36B3C9] hover:text-white active:scale-90 flex-shrink-0">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            
            <form action="{{ url()->current() }}" method="GET" class="flex-1 flex gap-2">
                @if(request('tag')) <input type="hidden" name="tag" value="{{ request('tag') }}"> @endif
                @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
                
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-sm z-10"></i>
                    <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                           placeholder="Search {{ $displayTitle }}..."
                           class="w-full bg-slate-100 border-none rounded-xl py-2.5 pl-11 pr-4 focus:ring-2 focus:ring-[#36B3C9]/30 focus:bg-white transition font-bold text-sm placeholder:text-slate-300">
                </div>
                <button type="submit" class="hidden md:block bg-slate-100 text-slate-500 px-5 rounded-xl font-bold text-sm hover:bg-[#36B3C9] hover:text-white transition">Search</button>
            </form>
            
            @if($isPlaces)
                @if($isAdmin)
                    <button onclick="toggleModal('addModal')" class="bg-[#36B3C9] text-white h-10 px-5 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-md shadow-cyan-200 transition hover:brightness-110 active:scale-95 flex items-center gap-2 flex-shrink-0">
                        <i class="fas fa-map-pin"></i> <span class="hidden md:inline">Add Landmark</span>
                    </button>
                @endif
            @elseif(!$isRequest && !$isWishlist && (!$isAdminOnly || $isAdmin || $isModerator))
                @if(!$canPost)
                    <button onclick="toggleModal('unverifiedModal')"
                        class="bg-slate-200 text-slate-400 h-10 px-5 rounded-xl font-black uppercase tracking-widest text-[10px] flex items-center gap-2 flex-shrink-0 hover:bg-slate-300 hover:text-slate-500 transition">
                        <i class="fas fa-lock"></i> <span class="hidden md:inline">Unverified</span>
                    </button>
                @else
                    <button onclick="toggleModal('addModal')" class="bg-[#36B3C9] text-white h-10 px-5 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-md shadow-cyan-200 transition hover:brightness-110 active:scale-95 flex items-center gap-2 flex-shrink-0">
                        @if($isComplaint)
                            <i class="fas fa-exclamation-triangle"></i> <span class="hidden md:inline">File Complaint</span>
                        @else
                            <i class="fas fa-plus"></i> <span class="hidden md:inline">Post</span>
                        @endif
                    </button>
                @endif
            @endif
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 pt-8 pb-28">

        {{-- PAGE HEADER --}}
        @if(!$isPlaces)
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-5">
            <div>
                <h1 class="text-4xl md:text-5xl font-black uppercase tracking-tighter text-slate-800 leading-none">
                    {{ $displayTitle }}
                </h1>
                @if($isAdmin || !in_array($normalizedType, ['places', 'complaints', 'requests', 'wishlist']))
                    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest mt-2">
                        {{ count($posts ?? []) }} Records Found
                    </p>
                @endif
            </div>

            @if(in_array($normalizedType, ['buy-sell', 'borrow', 'services', 'requests', 'complaints', 'wishlist']))
                <div class="flex flex-wrap bg-slate-100 p-1 rounded-2xl gap-1">
                    <a href="{{ request()->fullUrlWithQuery(['filter' => 'all']) }}"
                        class="filter-tab px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest {{ request('filter', 'all') == 'all' ? 'bg-white text-[#36B3C9] shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">All</a>
                    <a href="{{ request()->fullUrlWithQuery(['filter' => 'my_posts']) }}"
                        class="filter-tab px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest {{ request('filter') == 'my_posts' ? 'bg-white text-[#36B3C9] shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">My Posts</a>
                    
                    @if($hasWishlistFeature)
                        <a href="{{ request()->fullUrlWithQuery(['filter' => 'wishlist']) }}"
                            class="filter-tab px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest {{ request('filter') == 'wishlist' || $normalizedType == 'wishlist' ? 'bg-white text-yellow-500 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                            <i class="fas fa-bookmark mr-1"></i> Saved
                        </a>
                    @endif

                    @if(in_array($normalizedType, ['buy-sell', 'borrow', 'services']))
                        <a href="{{ request()->fullUrlWithQuery(['filter' => 'my_borrows']) }}"
                            class="filter-tab px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest {{ request('filter') == 'my_borrows' ? 'bg-white text-[#36B3C9] shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                            <i class="fas {{ $normalizedType === 'borrow' ? 'fa-hand-holding' : 'fa-shopping-bag' }} mr-1"></i>
                            {{ $normalizedType === 'borrow' ? 'My Borrows' : 'My Inquiries' }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['filter' => 'history']) }}"
                            class="filter-tab px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest {{ request('filter') == 'history' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                            <i class="fas fa-archive mr-1"></i> History
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- BUBBLE TAGS (Scrollable horizontally) --}}
        @if(!empty($availableTags))
            <div class="flex overflow-x-auto gap-2 pb-4 mb-6 scrollbar-hide items-center w-full">
                <a href="{{ request()->fullUrlWithQuery(['tag' => null]) }}"
                   class="filter-tab flex-shrink-0 px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border-2 {{ !request('tag') ? 'bg-[#36B3C9] text-white border-[#36B3C9] shadow-sm' : 'bg-transparent text-slate-400 border-slate-200 hover:border-[#36B3C9] hover:text-[#36B3C9]' }}">
                    All
                </a>
                @foreach($availableTags as $tag)
                    <a href="{{ request()->fullUrlWithQuery(['tag' => $tag]) }}"
                       class="filter-tab flex-shrink-0 px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border-2 {{ request('tag') == $tag ? 'bg-[#36B3C9] text-white border-[#36B3C9] shadow-sm' : 'bg-transparent text-slate-400 border-slate-200 hover:border-[#36B3C9] hover:text-[#36B3C9]' }}">
                        {{ $tag }}
                    </a>
                @endforeach
            </div>
        @endif
        @endif

        {{-- CALENDAR --}}
        @if($isEvent)
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden mb-8 p-3">
                <div id="calendar"></div>
            </div>
        @endif

        {{-- PLACES MAP --}}
        @if($isPlaces)
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden mb-8 p-5">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter flex items-center gap-2">
                        <i class="fas fa-map-marked-alt text-[#36B3C9]"></i> Baybay Polong Map
                    </h2>
                    <p class="text-slate-400 font-bold text-[10px] uppercase tracking-widest hidden md:block">Click any pin for details</p>
                </div>
                <div id="placesMap" class="w-full h-[58vh] rounded-[1.5rem] z-0 bg-slate-900"></div>
            </div>
        @endif

        {{-- REQUEST TILES (Protected by Verification Logic) --}}
        @if($isRequest)
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Files Available to Request</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
                <div onclick="{{ $canPost ? "openRequestModal('Certificate of Residency')" : "toggleModal('unverifiedModal')" }}"
                     class="bg-white p-5 rounded-[1.5rem] border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition cursor-pointer group flex items-center gap-4">
                    <div class="bg-blue-50 text-blue-500 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0 group-hover:scale-110 transition">
                        <i class="fas fa-home"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-slate-800 leading-tight group-hover:text-blue-500 transition">Certificate of Residency</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Request Document <i class="fas fa-arrow-right ml-1"></i></p>
                    </div>
                </div>
                <div onclick="{{ $canPost ? "openRequestModal('Certificate of Indigency')" : "toggleModal('unverifiedModal')" }}"
                     class="bg-white p-5 rounded-[1.5rem] border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition cursor-pointer group flex items-center gap-4">
                    <div class="bg-green-50 text-green-500 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0 group-hover:scale-110 transition">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-slate-800 leading-tight group-hover:text-green-500 transition">Certificate of Indigency</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Request Document <i class="fas fa-arrow-right ml-1"></i></p>
                    </div>
                </div>
                <div onclick="{{ $canPost ? "openRequestModal('Barangay Clearance')" : "toggleModal('unverifiedModal')" }}"
                     class="bg-white p-5 rounded-[1.5rem] border border-slate-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition cursor-pointer group flex items-center gap-4">
                    <div class="bg-purple-50 text-purple-500 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0 group-hover:scale-110 transition">
                        <i class="fas fa-stamp"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-slate-800 leading-tight group-hover:text-purple-500 transition">Barangay Clearance</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Request Document <i class="fas fa-arrow-right ml-1"></i></p>
                    </div>
                </div>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Your Recent Requests</p>
        @endif

        {{-- POSTS CARDS --}}
        @if(!$isPlaces)
        <div class="{{ $useGrid ? 'grid grid-cols-2 md:grid-cols-4 gap-4' : 'flex flex-col gap-3' }}" id="postsContainer">
            @forelse($posts ?? [] as $post)
                @php 
                    $pfp = $post->user && $post->user->profile_picture ? asset('uploads/profiles/' . basename($post->user->profile_picture)) : null; 
                    $isWishlisted = Auth::check() && \App\Models\Wishlist::where('user_id', Auth::id())->where('post_id', $post->id)->exists();
                    
                    // Logic to grey out reserved/sold items
                    $isUnavailable = in_array($post->transaction_status ?? 'available', ['reserved', 'sold']);
                    $cardOpacityClass = $isUnavailable ? 'opacity-60 grayscale hover:opacity-100 hover:grayscale-0' : '';
                @endphp

                <div onclick="openDetail({{ $post->id }})"
                     class="post-item cursor-pointer bg-white rounded-[1.8rem] border border-slate-100 shadow-sm hover:shadow-lg group relative {{ $cardOpacityClass }} {{ !$useGrid ? 'flex items-center p-4 gap-4' : 'flex flex-col p-3' }}">

                    {{-- LIST VIEW --}}
                    @if(!$useGrid)
                        {{-- Icon --}}
                        <div class="bg-slate-50 text-[#36B3C9] h-16 w-16 rounded-2xl flex items-center justify-center transition group-hover:bg-[#36B3C9] group-hover:text-white group-hover:rotate-6 flex-shrink-0">
                            <i class="fas {{ str_contains($normalizedType, 'complaint') ? 'fa-exclamation-triangle' : (str_contains($normalizedType, 'request') ? 'fa-file-signature' : (str_contains($normalizedType, 'event') ? 'fa-calendar-check' : 'fa-bullhorn')) }} text-xl"></i>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start gap-3">
                                <p class="font-black text-lg text-slate-800 leading-tight group-hover:text-[#36B3C9] transition truncate">{{ $post->title }}</p>
                                @if($hasWishlistFeature)
                                    <button onclick="toggleWishlist(event, {{ $post->id }}, this)" class="text-xl transition-colors flex-shrink-0 {{ $isWishlisted ? 'text-yellow-400' : 'text-slate-200 hover:text-yellow-400' }}">
                                        <i class="{{ $isWishlisted ? 'fas' : 'far' }} fa-bookmark"></i>
                                    </button>
                                @endif
                            </div>

                            {{-- Status badges --}}
                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5 mb-2">
                                @if($isRequest || $isComplaint)
                                    <span class="inline-block text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg {{ ($post->status ?? 'pending') === 'approved' ? 'bg-green-100 text-green-600' : (($post->status ?? 'pending') === 'completed' ? 'bg-blue-100 text-blue-600' : (($post->status ?? 'pending') === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600')) }}">{{ $post->status ?? 'Pending' }}</span>
                                @endif
                                
                                @if($isUnavailable && in_array(str_replace('_', '-', $post->category), ['buy-sell', 'borrow', 'services']))
                                    @php 
                                        $sText = $post->transaction_status;
                                        if($post->category === 'borrow' && $post->transaction_status === 'sold') $sText = 'borrowed out';
                                        elseif($post->category === 'services' && $post->transaction_status === 'sold') $sText = 'unavailable';
                                    @endphp
                                    <span class="inline-block text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg bg-slate-800 text-white">{{ $sText }}</span>
                                @endif

                                @php $decodedTags = is_string($post->tags) ? json_decode($post->tags, true) : $post->tags; @endphp
                                @if(is_array($decodedTags))
                                    @foreach($decodedTags as $t)
                                        <span class="text-[9px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-lg font-bold uppercase tracking-widest">{{ $t }}</span>
                                    @endforeach
                                @endif
                            </div>

                            {{-- Author --}}
                            <div class="flex items-center gap-1.5">
                                @if($pfp)
                                    <img src="{{ $pfp }}" class="w-5 h-5 rounded-full object-cover border border-slate-100 flex-shrink-0">
                                @else
                                    <div class="w-5 h-5 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center flex-shrink-0"><i class="fas fa-user text-[8px]"></i></div>
                                @endif
                                <span class="text-[10px] font-bold text-[#36B3C9] truncate">{{ $post->user ? $post->user->official_name : 'Neighbor' }}</span>
                            </div>

                            {{-- Meta row --}}
                            <div class="flex items-center gap-3 mt-2 pt-2 border-t border-slate-50">
                                <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest"><i class="far fa-clock mr-1"></i>{{ $post->created_at->diffForHumans() }}</span>
                                @if($showLocation && $post->location)
                                    <span class="text-[10px] font-bold text-red-400 flex items-center gap-1 truncate"><i class="fas fa-map-marker-alt"></i> {{ $post->location }}</span>
                                @endif
                                @if($isEvent && $post->event_date)
                                    <span class="text-[10px] font-bold text-orange-400 flex items-center gap-1"><i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($post->event_date)->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </div>

                    {{-- GRID VIEW --}}
                    @else
                        {{-- Image --}}
                        <div class="aspect-square bg-slate-100 flex items-center justify-center overflow-hidden mb-3 rounded-[1.3rem] relative w-full">
                            @php $imgs = is_array($post->image) ? $post->image : json_decode($post->image, true); @endphp
                            @if($imgs && count($imgs) > 0)
                                <img src="{{ asset('uploads/' . $imgs[0]) }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                                @if(count($imgs) > 1)
                                    <div class="absolute bottom-2 right-2 bg-black/50 backdrop-blur-md text-white text-[9px] px-2 py-0.5 rounded-lg font-black">+{{ count($imgs) - 1 }}</div>
                                @endif
                            @else
                                <i class="fas fa-camera text-slate-300 text-3xl group-hover:text-[#36B3C9] transition"></i>
                            @endif
                            
                            @if($hasWishlistFeature)
                                <button onclick="toggleWishlist(event, {{ $post->id }}, this)"
                                    class="absolute top-2.5 right-2.5 bg-white/80 backdrop-blur-md w-8 h-8 rounded-full flex items-center justify-center shadow-sm transition-colors z-10 text-sm {{ $isWishlisted ? 'text-yellow-400' : 'text-slate-300 hover:text-yellow-400' }}">
                                    <i class="{{ $isWishlisted ? 'fas' : 'far' }} fa-bookmark"></i>
                                </button>
                            @endif

                            @if($isUnavailable && in_array(str_replace('_', '-', $post->category), ['buy-sell', 'borrow', 'services']))
                                @php 
                                    $sText = $post->transaction_status;
                                    if($post->category === 'borrow' && $post->transaction_status === 'sold') $sText = 'borrowed out';
                                    elseif($post->category === 'services' && $post->transaction_status === 'sold') $sText = 'unavailable';
                                @endphp
                                <div class="absolute top-2.5 left-2.5 bg-slate-900/80 backdrop-blur-md text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-lg z-10">{{ $sText }}</div>
                            @endif

                            {{-- Only show Likes if it's NOT a request or complaint --}}
                            @if(!in_array(str_replace('_', '-', $post->category), ['requests', 'complaints']))
                            <div class="absolute bottom-2 left-2 bg-white/80 backdrop-blur-md text-red-500 text-[9px] px-2 py-0.5 rounded-lg font-black flex items-center gap-1">
                                <i class="fas fa-heart"></i> {{ $post->likes->count() }}
                            </div>
                            @endif
                            
                            @if($post->tags)
                                @php $displayTags = is_string($post->tags) ? json_decode($post->tags, true) : $post->tags; @endphp
                                @if(is_array($displayTags) && !$isUnavailable)
                                    <div class="absolute top-2.5 left-2.5 flex flex-wrap gap-1 max-w-[80%]">
                                        @foreach(array_slice($displayTags, 0, 2) as $t)
                                            <div class="bg-black/60 backdrop-blur-sm text-white text-[8px] font-bold px-2 py-0.5 rounded-lg uppercase tracking-wider">{{ $t }}</div>
                                        @endforeach
                                        @if(count($displayTags) > 2)
                                            <div class="bg-black/60 backdrop-blur-sm text-white text-[8px] font-bold px-2 py-0.5 rounded-lg">+{{ count($displayTags) - 2 }}</div>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>
                        
                        {{-- Grid card body --}}
                        <div class="px-0.5 pb-1 flex flex-col flex-1">
                            <p class="font-black text-slate-800 text-base leading-tight truncate mb-2">{{ $post->title }}</p>
                            
                            <div class="flex items-center gap-1.5 mb-3">
                                @if($pfp)
                                    <img src="{{ $pfp }}" class="w-5 h-5 rounded-full object-cover border border-slate-100 flex-shrink-0">
                                @else
                                    <div class="w-5 h-5 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center flex-shrink-0"><i class="fas fa-user text-[8px]"></i></div>
                                @endif
                                <p class="text-[10px] font-bold text-slate-400 truncate">{{ $post->user ? $post->user->official_name : 'User' }}</p>
                            </div>
                            
                            <div class="flex items-end justify-between mt-auto pt-2 border-t border-slate-50">
                                <div class="flex flex-col gap-1 w-full min-w-0 pr-2">
                                    <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest"><i class="far fa-clock mr-0.5"></i>{{ $post->created_at->diffForHumans(null, true, true) }}</span>
                                    @if($showLocation && $post->location)
                                        <p class="text-[9px] font-bold text-red-400 flex items-center gap-1 truncate"><i class="fas fa-map-marker-alt"></i> {{ $post->location }}</p>
                                    @endif
                                </div>
                                @if($isBuySell)
                                    <p class="text-lg font-black text-[#36B3C9] flex-shrink-0">
                                        @if($post->price) ₱{{ number_format($post->price, 0) }} @else <span class="text-slate-400 text-sm">Free</span> @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($isAdmin || $isModerator || Auth::id() === $post->user_id)
                        <button onclick="event.stopPropagation(); triggerDelete({{ $post->id }})"
                            class="absolute top-3 {{ $useGrid ? 'left-3' : 'right-3 top-1/2 -translate-y-1/2' }} text-slate-200 hover:text-red-500 p-1.5 transition opacity-0 group-hover:opacity-100 bg-white/80 rounded-full hover:bg-white shadow-sm backdrop-blur-sm z-10">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-24 text-center">
                    <div class="bg-white inline-flex items-center justify-center w-20 h-20 rounded-[2rem] shadow-sm mb-5 text-slate-200">
                        <i class="fas fa-folder-open text-4xl"></i>
                    </div>
                    <p class="text-slate-800 font-black text-2xl uppercase tracking-tighter">No Records Found</p>
                    <p class="text-slate-400 font-bold text-sm mt-2">Nothing posted here yet.</p>
                </div>
            @endforelse
        </div>
        @endif
    </div>

    {{-- ADD/EDIT MODAL --}}
    <div id="addModal" class="hidden fixed inset-0 z-[120] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md transition-all">
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl overflow-y-auto max-h-[90vh] animate-pop relative">
            <button onclick="toggleModal('addModal')" class="absolute top-6 right-6 text-slate-300 hover:text-slate-800 transition p-1">
                <i class="fas fa-times text-lg"></i>
            </button>
            
            @if($isComplaint)
                <h2 id="formModalTitle" class="text-2xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">File a Complaint</h2>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-7">This report is strictly confidential to admins.</p>
                <form action="{{ route('post.store') }}" method="POST" id="postForm" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <input type="hidden" name="category" value="{{ $type }}">
                    <input type="text" name="title" id="formTitle" placeholder="Nature of Complaint (e.g. Noise Disturbance)"
                        class="w-full p-4 bg-slate-50 rounded-2xl border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-black text-sm text-slate-800 placeholder:text-slate-300" required>
                    
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">
                            <i class="fas fa-map-pin text-red-400 mr-1"></i> Pinpoint Location in Baybay Polong
                        </label>
                        <div id="formMap" class="w-full h-[180px] rounded-xl z-0 mb-3 bg-slate-900"></div>
                        <input type="hidden" name="latitude" id="inputLat">
                        <input type="hidden" name="longitude" id="inputLng">
                        <input type="text" name="location" id="formLocation" placeholder="Location Name (e.g. Purok 4, near Plaza)"
                            class="w-full p-3 bg-white rounded-xl border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-sm text-slate-800 placeholder:text-slate-300" required>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Date of Incident</label>
                        <input type="date" name="event_date" id="formEventDate" class="w-full bg-transparent border-none p-0 focus:ring-0 font-black text-sm text-slate-800" required>
                    </div>

                    @if(!empty($availableTags))
                        <div>
                            <label class="text-[10px] font-black text-slate-300 uppercase tracking-widest block mb-2">Complaint Category <span class="normal-case font-normal text-slate-400 ml-1">(Optional)</span></label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($availableTags as $tag)
                                    <div class="relative">
                                        <input type="checkbox" name="tags[]" value="{{ $tag }}" id="tag_{{ $tag }}" class="hidden tag-checkbox formTagCheckbox">
                                        <label for="tag_{{ $tag }}" class="block text-center py-2.5 px-4 rounded-xl bg-slate-50 text-slate-500 text-xs font-bold cursor-pointer transition border border-transparent hover:bg-slate-100">{{ $tag }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <textarea name="description" id="formDesc" placeholder="Provide full details: Who was involved? Where? What occurred?"
                        class="w-full p-4 bg-slate-50 rounded-2xl border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-sm text-slate-800 placeholder:text-slate-300 min-h-[130px]" rows="4" required></textarea>

            @elseif($isRequest)
                <h2 id="requestModalTitle" class="text-2xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">Request a File</h2>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-7">Admin will schedule your pickup date once approved.</p>
                <form action="{{ route('post.store') }}" method="POST" id="postForm" onsubmit="prepareRequestDescription()" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <input type="hidden" name="category" value="{{ $type }}">
                    <input type="hidden" name="title" id="requestDocType">
                    <input type="hidden" name="description" id="requestActualDesc">
                    <input type="text" id="reqName" placeholder="Full Legal Name"
                        class="w-full p-4 bg-slate-50 rounded-2xl border-none font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20" required>
                    <input type="text" id="reqAddress" placeholder="Complete Home Address"
                        class="w-full p-4 bg-slate-50 rounded-2xl border-none font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20" required>
                    <textarea id="reqPurpose" placeholder="Reason for request"
                        class="w-full p-4 bg-slate-50 rounded-2xl border-none font-bold text-sm text-slate-800 placeholder:text-slate-300 min-h-[110px] focus:ring-2 focus:ring-[#36B3C9]/20" required></textarea>

            @else
                <h2 id="formModalTitle" class="text-2xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">
                    {{ $isPlaces ? 'Add Landmark' : 'New Listing' }}
                </h2>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-7">Category: {{ str_replace(['_', '-'], ' ', $type) }}</p>
                <form action="{{ route('post.store') }}" method="POST" id="postForm" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    <input type="hidden" name="category" value="{{ $type }}">
                    <input type="text" name="title" id="formTitle" placeholder="{{ $isPlaces ? 'Landmark Name (e.g. Town Plaza)' : 'Item Name / Title' }}"
                        class="w-full p-4 bg-slate-50 rounded-2xl border-none font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20" required>
                    
                    @if($showLocation)
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">
                            <i class="fas fa-map-pin text-red-400 mr-1"></i> Pinpoint Location
                            <span class="normal-case font-normal text-slate-400 ml-1">(Optional)</span>
                        </label>
                        <div id="formMap" class="w-full h-[180px] rounded-xl z-0 mb-3 bg-slate-900"></div>
                        <input type="hidden" name="latitude" id="inputLat">
                        <input type="hidden" name="longitude" id="inputLng">
                        <input type="text" name="location" id="formLocation" placeholder="Location Name (e.g. Zone 1, near Plaza) - Optional"
                            class="w-full p-3 bg-white rounded-xl border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-sm text-slate-800 placeholder:text-slate-300">
                    </div>
                    @endif

                    @if($isBuySell) 
                        <div class="relative mt-2">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 font-black">₱</span>
                            <input type="number" name="price" id="formPrice" step="0.01" placeholder="Price (Optional)" class="w-full p-4 pl-9 bg-slate-50 rounded-2xl border-none font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20">
                        </div>
                        <select name="condition" id="formCondition" class="w-full p-4 bg-slate-50 rounded-2xl border-none font-bold text-sm text-slate-500 cursor-pointer focus:ring-2 focus:ring-[#36B3C9]/20" required>
                            <option value="" disabled selected>Select Condition</option>
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    @elseif($isPlaces)
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">
                                <i class="fas fa-icons text-[#36B3C9] mr-1"></i> Landmark Icon
                            </label>
                            <select name="condition" id="formCondition" class="w-full p-3 bg-white rounded-xl border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-sm text-slate-700 cursor-pointer" required>
                                <option value="" disabled selected>Select the type of landmark...</option>
                                <option value="fa-map-pin">Default Pin</option>
                                <option value="fa-store">Store / Sari-Sari / Shop</option>
                                <option value="fa-utensils">Eatery / Food / Restaurant</option>
                                <option value="fa-building">Barangay Hall / Plaza</option>
                                <option value="fa-school">School / Daycare</option>
                                <option value="fa-church">Church / Chapel</option>
                                <option value="fa-plus-square">Clinic / Health Center</option>
                            </select>
                        </div>
                    @endif
                    
                    @if(!empty($availableTags))
                        <div>
                            <label class="text-[10px] font-black text-slate-300 uppercase tracking-widest block mb-2">Tags (Select Multiple) <span class="normal-case font-normal text-slate-400 ml-1">(Optional)</span></label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($availableTags as $tag)
                                    <div class="relative">
                                        <input type="checkbox" name="tags[]" value="{{ $tag }}" id="tag_{{ $tag }}" class="hidden tag-checkbox formTagCheckbox">
                                        <label for="tag_{{ $tag }}" class="block text-center py-2.5 px-4 rounded-xl bg-slate-50 text-slate-500 text-xs font-bold cursor-pointer transition border border-transparent hover:bg-slate-100">{{ $tag }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <textarea name="description" id="formDesc" placeholder="{{ $isPlaces ? 'Description of the landmark (Optional)...' : 'Description & Details (Optional)...' }}"
                        class="w-full p-4 bg-slate-50 rounded-2xl border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-sm text-slate-800 placeholder:text-slate-300 min-h-[130px]" rows="4"></textarea>

            @endif

                <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center group hover:border-[#36B3C9] hover:bg-slate-50/50 cursor-pointer relative mt-2 transition">
                    <input type="file" name="images[]" id="imagesInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" multiple accept="image/*">
                    <i class="fas fa-camera text-2xl text-slate-200 group-hover:text-[#36B3C9] mb-2 block transition"></i>
                    <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Add Photos <span class="normal-case font-normal text-slate-400 ml-1">(Optional)</span></span>
                </div>
                <div id="imagePreviewContainer" class="grid grid-cols-4 gap-2 mt-1"></div>
                <button type="submit" id="formSubmitBtn" class="w-full bg-[#36B3C9] text-white font-black py-4 rounded-2xl shadow-lg shadow-cyan-100 mt-3 transition hover:brightness-110 active:scale-95 uppercase tracking-widest text-xs">
                    Submit Post
                </button>
            </form>
        </div>
    </div>

    {{-- DETAIL MODAL (Single Column Layout) --}}
    <div id="detailModal" class="hidden fixed inset-0 z-[110] bg-slate-900/80 flex items-center justify-center p-4 backdrop-blur-lg">
        <div class="bg-white text-slate-900 w-full max-w-3xl rounded-[3rem] overflow-hidden shadow-2xl relative animate-pop max-h-[92vh] overflow-y-auto flex flex-col">
            
            <button onclick="toggleModal('detailModal')" class="absolute top-4 right-4 z-[130] bg-black/40 text-white hover:text-white w-10 h-10 rounded-full flex items-center justify-center transition hover:bg-black/60">
                <i class="fas fa-times text-lg"></i>
            </button>

            {{-- 1. Image Gallery at the top --}}
            <div id="detailImageSection" class="relative hidden bg-slate-900 w-full">
                <button id="prevBtn" onclick="moveGallery(-1)" class="absolute left-4 top-1/2 -translate-y-1/2 z-[120] bg-white/20 backdrop-blur-md text-white p-3 w-10 h-10 rounded-full shadow-lg transition hover:scale-110 flex items-center justify-center">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="nextBtn" onclick="moveGallery(1)" class="absolute right-4 top-1/2 -translate-y-1/2 z-[120] bg-white/20 backdrop-blur-md text-white p-3 w-10 h-10 rounded-full shadow-lg transition hover:scale-110 flex items-center justify-center">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <div id="detImg" class="w-full h-[350px] md:h-[450px] flex overflow-x-hidden snap-x snap-mandatory"></div>
            </div>

            <div class="p-8">
                {{-- 2. Author row with Timestamp --}}
                <div class="flex items-center justify-between mb-6 border-b border-slate-50 pb-4">
                    <div class="flex items-center gap-3">
                        <div id="detUserPfpContainer" class="w-12 h-12 rounded-2xl bg-[#36B3C9]/10 flex items-center justify-center text-[#36B3C9] overflow-hidden shadow-sm flex-shrink-0">
                            <i class="fas fa-user-circle text-xl"></i>
                        </div>
                        <div>
                            <p id="detUser" class="text-base font-black text-slate-800 leading-none mb-1"></p>
                            <p id="detLocation" class="text-[10px] font-black text-red-400 uppercase tracking-widest hidden mt-1"><i class="fas fa-map-marker-alt mr-0.5"></i> <span></span></p>
                        </div>
                    </div>
                    <p id="detDate" class="text-[10px] font-black text-slate-300 uppercase tracking-widest text-right"></p>
                </div>

                {{-- 3. Status Banner --}}
                <div id="detStatusBanner" class="hidden mb-4"></div>
                
                {{-- 4. Title & Price --}}
                <div id="detTitleContainer" class="mb-2"></div>
                <div id="detPrice" class="mb-5"></div>

                {{-- 5. Condition & Tags --}}
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    <div id="detConditionContainer"></div>
                    <div id="detTagsContainer" class="flex flex-wrap gap-2"></div>
                </div>
                
                {{-- 6. Description box --}}
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Description</p>
                <div id="detDesc" class="bg-slate-50 p-6 rounded-2xl text-slate-600 text-sm font-medium whitespace-pre-wrap mb-8 border border-slate-100 hidden leading-relaxed"></div>

                {{-- 7. Map Container --}}
                <div id="detailMapContainer" class="hidden mb-8 bg-slate-50 p-3 rounded-3xl shadow-sm border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 px-2 pt-1 flex items-center gap-2">
                        <i class="fas fa-map-marked-alt text-[#36B3C9]"></i> Location
                    </p>
                    <div id="detailMap" class="w-full h-[220px] rounded-2xl z-0 border border-slate-100 bg-slate-100"></div>
                </div>

                {{-- 8. Admin / Seller Controls --}}
                <div id="sellerStatusContainer"></div>
                <div id="adminAppointmentControls"></div>
                
                {{-- 9. Actions Container (Bottom Stacked) --}}
                <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <div id="likeButtonContainer" class="flex-1"></div>
                        <div id="wishlistButtonContainer" class="flex-1"></div>
                        <div id="detReportContainer" class="flex-1"></div>
                    </div>
                    <div id="contactButtonContainer"></div>
                    <div class="flex items-center gap-2">
                        <div id="detEditContainer" class="flex-1"></div>
                        <div id="detDeleteContainer" class="flex-1"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- UNVERIFIED MODAL (NEW BEAUTIFUL POPUP) --}}
    <div id="unverifiedModal" class="hidden fixed inset-0 z-[300] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white text-slate-900 w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl text-center animate-pop relative">
            <button onclick="toggleModal('unverifiedModal')" class="absolute top-4 right-4 bg-slate-100 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full flex items-center justify-center transition hover:bg-slate-200">
                <i class="fas fa-times text-sm"></i>
            </button>
            <div class="bg-amber-50 text-amber-500 w-20 h-20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-3xl"><i class="fas fa-shield-alt"></i></div>
            <h3 class="text-2xl font-black mb-2 tracking-tighter uppercase text-slate-800">Not Verified Yet</h3>
            <p class="text-slate-500 text-[10px] font-bold mb-7 uppercase tracking-widest leading-relaxed">Please verify your account by uploading a Valid ID or Brgy. Certificate to access this feature.</p>
            <div class="flex flex-col gap-3">
                <a href="{{ route('profile.edit', ['onboarding' => 1]) }}" class="w-full block bg-[#36B3C9] text-white font-black py-4 rounded-2xl hover:brightness-110 transition active:scale-95 shadow-lg shadow-cyan-200 uppercase tracking-widest text-[10px]">Verify Now</a>
            </div>
        </div>
    </div>

    {{-- REPORT MODAL --}}
    <div id="reportModal" class="hidden fixed inset-0 z-[150] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl relative animate-pop">
            <button onclick="closeReportModal()" class="absolute top-6 right-6 text-slate-300 hover:text-slate-800 transition p-1">
                <i class="fas fa-times text-lg"></i>
            </button>
            <div class="w-14 h-14 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center text-2xl mb-5"><i class="fas fa-flag"></i></div>
            <h2 class="text-2xl font-black mb-1 uppercase tracking-tighter text-slate-800">Report Post</h2>
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-7">Help us keep the community safe.</p>
            <form id="reportForm" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Reason</label>
                    <select name="reason" required class="w-full p-4 bg-slate-50 rounded-2xl border-none font-bold text-sm text-slate-700 cursor-pointer focus:ring-2 focus:ring-red-200">
                        <option value="" disabled selected>Select a reason...</option>
                        <option value="spam">Spam or Misleading</option>
                        <option value="harassment">Harassment or Abusive</option>
                        <option value="inappropriate">Inappropriate Content</option>
                        <option value="scam">Scam or Fraud</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-red-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-red-100 transition hover:bg-red-600 active:scale-95 uppercase tracking-widest text-xs">Submit Report</button>
            </form>
        </div>
    </div>

    {{-- CUSTOM CHAT DELETE CONFIRM MODAL --}}
    <div id="chatDeleteConfirmModal" class="hidden fixed inset-0 z-[300] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white text-slate-900 w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl text-center animate-pop">
            <div class="bg-red-50 text-red-500 w-20 h-20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-3xl"><i class="fas fa-comment-slash"></i></div>
            <h3 class="text-2xl font-black mb-1 tracking-tighter uppercase">Delete Chat?</h3>
            <p class="text-slate-400 text-sm font-medium mb-7">This will permanently remove the conversation for you.</p>
            <div class="flex flex-col gap-3">
                <button onclick="executeDeleteConversation()" class="w-full bg-red-500 text-white font-black py-4 rounded-2xl hover:bg-red-600 transition active:scale-95 shadow-lg shadow-red-100 uppercase tracking-widest text-[10px]">Delete Forever</button>
                <button onclick="toggleModal('chatDeleteConfirmModal')" class="w-full text-slate-300 font-black py-3 uppercase tracking-widest text-[10px] hover:text-slate-800 transition">Cancel</button>
            </div>
        </div>
    </div>

    {{-- DELETE CONFIRM MODAL --}}
    <div id="deleteConfirmModal" class="hidden fixed inset-0 z-[150] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white text-slate-900 w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl text-center animate-pop">
            <div class="bg-red-50 text-red-500 w-20 h-20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-3xl"><i class="fas fa-trash-alt"></i></div>
            <h3 class="text-2xl font-black mb-1 tracking-tighter uppercase">Delete?</h3>
            <p class="text-slate-400 text-sm font-medium mb-7">This action cannot be undone.</p>
            <form id="deleteForm" method="POST" class="space-y-3">
                @csrf @method('DELETE')
                <button type="submit" class="w-full bg-red-500 text-white font-black py-4 rounded-2xl hover:bg-red-600 transition active:scale-95 shadow-lg shadow-red-100 uppercase tracking-widest text-[10px]">Delete Forever</button>
            </form>
            <button onclick="toggleModal('deleteConfirmModal')" class="w-full text-slate-300 font-black py-3 uppercase tracking-widest text-[10px] hover:text-slate-800 transition mt-1">Go Back</button>
        </div>
    </div>

    {{-- INBOX PANEL + BUTTON --}}
    @if($isBuySell || $isBorrow || $normalizedType === 'services')
        <div id="inboxPanel" class="fixed inset-y-0 right-0 z-[250] w-80 sm:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col border-l border-slate-100">
            <div class="bg-[#36B3C9] px-5 py-4 text-white flex justify-between items-center">
                <h2 class="font-black uppercase tracking-widest text-sm flex items-center gap-2"><i class="fas fa-inbox"></i> Messages</h2>
                <button onclick="toggleInboxPanel()" class="text-white/80 hover:text-white text-lg transition"><i class="fas fa-times"></i></button>
            </div>
            <div id="inboxContent" class="flex-1 overflow-y-auto p-3 bg-slate-50 space-y-2">
                <div class="text-center text-xs font-bold text-slate-400 mt-10">Loading messages...</div>
            </div>
        </div>

        <button onclick="toggleInboxPanel()" class="fixed bottom-8 right-8 z-[90] bg-[#36B3C9] text-white w-16 h-16 rounded-2xl flex items-center justify-center shadow-xl shadow-cyan-300/40 hover:-translate-y-1 hover:brightness-110 active:scale-95 transition-all duration-200 group">
            <i class="fas fa-comment-dots text-2xl"></i>
            <div id="unreadBadge" class="{{ $unreadCount > 0 ? '' : 'hidden' }} absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-md border-2 border-white min-w-[22px] text-center">{{ $unreadCount }}</div>
            <span class="absolute right-20 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none shadow-lg">Messages</span>
        </button>
    @endif

    <script>
        const isUserVerified = {{ (Auth::check() && Auth::user()->is_verified) ? 'true' : 'false' }};
        let currentIdx = 0; let totalImgs = 0; let selectedFiles = [];
        let currentDetailPostId = null; let currentDetailPostData = null;
        let conversationToDelete = null;

        const baybayPolongCenter = [16.0300, 120.2588]; 
        const baybayPolongBounds = L.latLngBounds([16.0230, 120.2450], [16.0380, 120.2720]);
        let formMap = null; let formMarker = null;
        let detailMapObj = null; let detailMarker = null;

        // Ensure missing request modal logic exists
        function openRequestModal(docType) {
            resetUploadForm();
            const reqDocType = document.getElementById('requestDocType');
            if (reqDocType) reqDocType.value = docType;
            
            const reqTitle = document.getElementById('requestModalTitle');
            if (reqTitle) reqTitle.innerText = 'Request ' + docType;
            
            const formTitle = document.getElementById('formTitle');
            if (formTitle) formTitle.value = docType; // fallback

            toggleModal('addModal');
        }

        function prepareRequestDescription() {
            const type = document.getElementById('requestDocType').value;
            const name = document.getElementById('reqName').value;
            const addr = document.getElementById('reqAddress').value;
            const purp = document.getElementById('reqPurpose').value;
            
            const actualDesc = document.getElementById('requestActualDesc');
            if (actualDesc) {
                actualDesc.value = `Requester Name: ${name}\nHome Address: ${addr}\nPurpose for Request:\n${purp}`;
            }
            
            const formTitle = document.getElementById('formTitle');
            if (!formTitle) {
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'title'; input.value = type;
                document.getElementById('postForm').appendChild(input);
            } else {
                formTitle.value = type;
            }
        }

        function toggleWishlist(event, postId, btnElement) {
            event.stopPropagation();
            fetch(`/post/${postId}/wishlist`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() } })
            .then(r => r.json())
            .then(data => {
                const icon = btnElement.querySelector('i');
                if(data.saved) {
                    icon.classList.replace('far', 'fas');
                    btnElement.classList.replace('text-slate-400', 'text-yellow-500');
                    btnElement.classList.replace('text-slate-300', 'text-yellow-400');
                    if(btnElement.id === 'wishlistBtnDetail') { btnElement.classList.replace('bg-white', 'bg-yellow-50'); btnElement.classList.replace('border-slate-200', 'border-yellow-100'); }
                } else {
                    icon.classList.replace('fas', 'far');
                    btnElement.classList.replace('text-yellow-500', 'text-slate-400');
                    btnElement.classList.replace('text-yellow-400', 'text-slate-300');
                    if(btnElement.id === 'wishlistBtnDetail') { btnElement.classList.replace('bg-yellow-50', 'bg-white'); btnElement.classList.replace('border-yellow-100', 'border-slate-200'); }
                }
            }).catch(e => console.error(e));
        }

        function updateItemStatus(postId, status) {
            fetch(`/api/post/${postId}/transaction-status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                body: JSON.stringify({ transaction_status: status })
            }).then(() => window.location.reload());
        }

        function confirmDeleteConversation(postId, event) {
            event.stopPropagation();
            const dropdown = document.getElementById(`chatOptions-${postId}`);
            if (dropdown) dropdown.classList.add('hidden');
            
            conversationToDelete = postId;
            toggleModal('chatDeleteConfirmModal');
        }

        function executeDeleteConversation() {
            if(!conversationToDelete) return;
            const postId = conversationToDelete;
            
            fetch(`/api/chat/${postId}`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() }
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    toggleModal('chatDeleteConfirmModal');
                    const box = document.getElementById('chatBox-' + postId); 
                    if(box) { box.classList.add('hidden'); box.classList.remove('flex'); }
                    loadInbox();
                }
            }).catch(err => console.error(err));
        }

        function toggleChatOptions(postId, event) {
            event.stopPropagation();
            const dropdown = document.getElementById(`chatOptions-${postId}`);
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        function openReportModal(postId) {
            document.getElementById('reportForm').action = `/post/${postId}/report`;
            toggleModal('detailModal'); 
            document.getElementById('reportModal').classList.remove('hidden'); 
        }
        function closeReportModal() { document.getElementById('reportModal').classList.add('hidden'); }

        function openEditModal(postId) {
            if(!currentDetailPostData) return;
            toggleModal('detailModal');
            
            document.getElementById('formMethod').value = "PUT";
            document.getElementById('postForm').action = `/post/${postId}`;
            
            const fTitleMod = document.getElementById('formModalTitle');
            if(fTitleMod) fTitleMod.innerText = "Edit Listing";
            
            const rTitleMod = document.getElementById('requestModalTitle');
            if(rTitleMod) rTitleMod.innerText = "Edit Request";
            
            document.getElementById('formSubmitBtn').innerText = "Save Changes";
            
            if (currentDetailPostData.category === 'requests') {
                const rdType = document.getElementById('requestDocType'); if(rdType) rdType.value = currentDetailPostData.title || '';
                const lines = (currentDetailPostData.description || '').split('\n'); 
                let reqName = '', homeAddr = '', purpose = ''; let isPurpose = false;
                lines.forEach(line => {
                    if (line.startsWith('Requester Name:')) reqName = line.replace('Requester Name:', '').trim();
                    else if (line.startsWith('Home Address:')) homeAddr = line.replace('Home Address:', '').trim();
                    else if (line.startsWith('Purpose for Request:')) isPurpose = true;
                    else if (isPurpose) purpose += line + '\n';
                });
                const eName = document.getElementById('reqName'); if(eName) eName.value = reqName;
                const eAddr = document.getElementById('reqAddress'); if(eAddr) eAddr.value = homeAddr;
                const ePurp = document.getElementById('reqPurpose'); if(ePurp) ePurp.value = purpose.trim();
            } else {
                const fTitle = document.getElementById('formTitle'); if(fTitle) fTitle.value = currentDetailPostData.title || '';
                const fDesc = document.getElementById('formDesc'); if(fDesc) fDesc.value = currentDetailPostData.description || '';
            }
            
            const priceInput = document.getElementById('formPrice');
            if(priceInput && currentDetailPostData.price) priceInput.value = currentDetailPostData.price;
            
            const locInput = document.getElementById('formLocation');
            if(locInput && currentDetailPostData.location) locInput.value = currentDetailPostData.location;
            
            const conditionInput = document.getElementById('formCondition');
            if(conditionInput && currentDetailPostData.condition) conditionInput.value = currentDetailPostData.condition;
            
            const eventDateInput = document.getElementById('formEventDate');
            if(eventDateInput && currentDetailPostData.event_date) {
                eventDateInput.value = currentDetailPostData.event_date.split('T')[0];
            }

            let postTags = [];
            try { postTags = JSON.parse(currentDetailPostData.tags) || []; } catch(e) { postTags = [currentDetailPostData.tags]; }
            document.querySelectorAll('.formTagCheckbox').forEach(checkbox => {
                checkbox.checked = postTags.includes(checkbox.value);
            });

            if(currentDetailPostData.latitude && currentDetailPostData.longitude) {
                document.getElementById('inputLat').value = currentDetailPostData.latitude;
                document.getElementById('inputLng').value = currentDetailPostData.longitude;
                if(formMarker) formMarker.setLatLng([currentDetailPostData.latitude, currentDetailPostData.longitude]);
                if(formMap) formMap.setView([currentDetailPostData.latitude, currentDetailPostData.longitude], 16);
            }
            
            toggleModal('addModal');
        }

        function resetUploadForm() {
            selectedFiles = [];
            document.getElementById('imagePreviewContainer').innerHTML = '';
            if(fileInput) fileInput.value = '';
            document.getElementById('postForm').reset();
            
            document.getElementById('formMethod').value = "POST";
            document.getElementById('postForm').action = "{{ route('post.store') }}";
            
            const reqTitle = document.getElementById('requestModalTitle');
            if (reqTitle) {
                reqTitle.innerText = "Request a File";
            }
            const fTitleMod = document.getElementById('formModalTitle');
            if (fTitleMod) {
                fTitleMod.innerText = "{{ $isPlaces ? 'Add Landmark' : 'New Listing' }}";
            }
            
            document.getElementById('formSubmitBtn').innerText = "Submit Post";
            document.querySelectorAll('.formTagCheckbox').forEach(checkbox => checkbox.checked = false);
        }

        function previewChatImage(inputElement, postId) {
            const file = inputElement.files[0];
            const previewContainer = document.getElementById(`chatImagePreview-${postId}`);
            const previewImg = document.getElementById(`chatImagePreviewImg-${postId}`);
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { previewImg.src = e.target.result; previewContainer.classList.remove('hidden'); }
                reader.readAsDataURL(file);
            }
        }
        function clearChatImage(postId) {
            const el = document.getElementById(`chatImage-${postId}`); if(el) el.value = "";
            const prev = document.getElementById(`chatImagePreview-${postId}`); if(prev) prev.classList.add('hidden');
        }

        function getCustomIcon(iconCode, category) {
            let icon = 'fa-map-pin'; let color = 'bg-[#36B3C9]'; 
            if (category === 'places' || category === '') {
                icon = iconCode || 'fa-map-pin'; 
                if(icon === 'fa-store') color = 'bg-blue-500'; else if(icon === 'fa-utensils') color = 'bg-orange-500'; else if(icon === 'fa-building') color = 'bg-red-600'; else if(icon === 'fa-school') color = 'bg-green-500'; else if(icon === 'fa-church') color = 'bg-purple-500'; else if(icon === 'fa-plus-square') color = 'bg-rose-500'; else color = 'bg-red-500'; 
            } else {
                if (category === 'buy-sell' || category === 'buy_sell') { icon = 'fa-shopping-bag'; color = 'bg-blue-500'; } else if (category === 'borrow') { icon = 'fa-hands-helping'; color = 'bg-green-500'; } else if (category === 'services') { icon = 'fa-tools'; color = 'bg-orange-500'; } else if (category === 'events' || category === 'event') { icon = 'fa-calendar-alt'; color = 'bg-purple-500'; } else if (category === 'complaints') { icon = 'fa-exclamation-triangle'; color = 'bg-red-600'; } else if (category === 'requests') { icon = 'fa-file-signature'; color = 'bg-teal-500'; } else if (category === 'announcements' || category === 'announce') { icon = 'fa-bullhorn'; color = 'bg-yellow-500'; }
            }
            return L.divIcon({ className: 'custom-marker', html: `<div class="${color} text-white w-10 h-10 flex items-center justify-center rounded-full shadow-[0_4px_10px_rgba(0,0,0,0.5)] border-2 border-white text-lg"><i class="fas ${icon}"></i></div>`, iconSize: [40, 40], iconAnchor: [20, 40] });
        }

        function addCustomRoadLabel(mapInstance) {
            var signControl = L.control({position: 'topright'});
            signControl.onAdd = function () {
                var div = L.DomUtil.create('div', 'custom-road-label-container');
                div.innerHTML = `<div class="bg-red-600/90 backdrop-blur-sm text-white border-2 border-white px-4 py-2 rounded-full font-black text-[12px] uppercase tracking-widest whitespace-nowrap shadow-[0_4px_15px_rgba(0,0,0,0.4)] flex items-center justify-center gap-2 mt-2 mr-2 pointer-events-none"><i class="fas fa-star text-yellow-300"></i> Baybay Polong</div>`;
                return div;
            };
            signControl.addTo(mapInstance);
        }

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden');
            if(id === 'addModal' && !modal.classList.contains('hidden')) { setTimeout(() => { initFormMap(); }, 300); }
            if(id === 'addModal' && modal.classList.contains('hidden')) { resetUploadForm(); }
        }

        function initFormMap() {
            if(!document.getElementById('formMap')) return;
            if(!formMap) {
                formMap = L.map('formMap', { center: baybayPolongCenter, zoom: 16, minZoom: 15, maxBounds: baybayPolongBounds, maxBoundsViscosity: 1.0 });
                L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: '© Esri' }).addTo(formMap);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png', { maxZoom: 19, attribution: '© CartoDB' }).addTo(formMap);
                addCustomRoadLabel(formMap);
                formMarker = L.marker(baybayPolongCenter).addTo(formMap);
                document.getElementById('inputLat').value = baybayPolongCenter[0];
                document.getElementById('inputLng').value = baybayPolongCenter[1];
                formMap.on('click', function(e) {
                    formMarker.setLatLng(e.latlng);
                    document.getElementById('inputLat').value = e.latlng.lat;
                    document.getElementById('inputLng').value = e.latlng.lng;
                });
            }
            formMap.invalidateSize(); 
        }

        function renderDetailMap(lat, lng, conditionStr = "", category = "", title = "") {
            const mapContainer = document.getElementById('detailMapContainer');
            if(!lat || !lng) { mapContainer.classList.add('hidden'); return; }
            mapContainer.classList.remove('hidden');
            if(!detailMapObj) {
                detailMapObj = L.map('detailMap', { center: [lat, lng], zoom: 16, minZoom: 15, maxBounds: baybayPolongBounds, maxBoundsViscosity: 1.0 });
                L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 }).addTo(detailMapObj);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(detailMapObj);
                addCustomRoadLabel(detailMapObj);
                detailMarker = L.marker([lat, lng], { icon: getCustomIcon(conditionStr, category) }).addTo(detailMapObj);
                if (category === 'places') { detailMarker.bindTooltip(title ? String(title) : "", { permanent: true, direction: 'top', offset: [0, -40], className: 'landmark-label' }); }
            } else {
                detailMapObj.setView([lat, lng], 16);
                detailMarker.setLatLng([lat, lng]);
                detailMarker.setIcon(getCustomIcon(conditionStr, category));
                detailMarker.unbindTooltip();
                if (category === 'places') { detailMarker.bindTooltip(title ? String(title) : "", { permanent: true, direction: 'top', offset: [0, -40], className: 'landmark-label' }); }
            }
            setTimeout(() => { detailMapObj.invalidateSize(); }, 300);
        }

        const fileInput = document.getElementById('imagesInput');
        if(fileInput) { 
            fileInput.addEventListener('change', function(e) { 
                selectedFiles = [...selectedFiles, ...Array.from(e.target.files)]; 
                updateImagePreviews(); syncFileInput(); 
            }); 
        }

        function updateImagePreviews() {
            const c = document.getElementById('imagePreviewContainer'); c.innerHTML = '';
            selectedFiles.forEach((f, i) => {
                const r = new FileReader();
                r.onload = function(e) {
                    const d = document.createElement('div'); d.className = "relative aspect-square rounded-2xl overflow-hidden border shadow-sm animate-pop group";
                    d.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover"><button type="button" onclick="removeImage(${i})" class="absolute top-1 right-1 bg-red-500 text-white w-6 h-6 rounded-full text-[10px] flex items-center justify-center shadow-lg transition"><i class="fas fa-times"></i></button>`;
                    c.appendChild(d);
                }; r.readAsDataURL(f);
            });
        }
        function removeImage(i) { selectedFiles.splice(i, 1); updateImagePreviews(); syncFileInput(); }
        function syncFileInput() { const dt = new DataTransfer(); selectedFiles.forEach(f => dt.items.add(f)); fileInput.files = dt.files; }

        function openDetail(id) {
            currentDetailPostId = id; 
            fetch(`/api/post/${id}`)
                .then(r => r.json())
                .then(d => {
                    currentDetailPostData = d;
                    let displayDateTime = null; let inputDateTime = '';

                    if (d.event_date) {
                        let cleanStr = d.event_date.replace('T', ' ').split('.')[0].replace('Z', '');
                        let parts = cleanStr.split(/[- :]/);
                        if (parts.length >= 5) {
                            let year = parts[0], month = parts[1], day = parts[2], hour = parts[3], min = parts[4];
                            let localDate = new Date(year, month - 1, day, hour, min);
                            displayDateTime = localDate.toLocaleString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                            inputDateTime = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${min.padStart(2, '0')}`;
                        } else if (parts.length >= 3) {
                            let year = parts[0], month = parts[1], day = parts[2];
                            displayDateTime = new Date(year, month - 1, day).toLocaleDateString();
                            inputDateTime = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T00:00`;
                        }
                    }

                    const descEl = document.getElementById('detDesc');
                    
                    if (d.description) {
                        if (d.category === 'requests' && d.description.includes('Requester Name:')) {
                            const lines = d.description.split('\n'); let reqName = '', homeAddr = '', purpose = ''; let isPurpose = false;
                            lines.forEach(line => {
                                if (line.startsWith('Requester Name:')) reqName = line.replace('Requester Name:', '').trim();
                                else if (line.startsWith('Home Address:')) homeAddr = line.replace('Home Address:', '').trim();
                                else if (line.startsWith('Purpose for Request:')) isPurpose = true;
                                else if (isPurpose) purpose += line + '\n';
                            });
                            const reqHtml = `<h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-5 flex items-center gap-2"><i class="fas fa-file-invoice text-[#36B3C9]"></i> Document Request Details</h4><div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"><div class="bg-white p-4 rounded-xl shadow-sm border border-slate-50"><span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Requester Name</span><span class="text-sm font-bold text-slate-800">${reqName}</span></div><div class="bg-white p-4 rounded-xl shadow-sm border border-slate-50"><span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Home Address</span><span class="text-sm font-bold text-slate-800">${homeAddr}</span></div></div><div class="bg-white p-4 rounded-xl shadow-sm border border-slate-50"><span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Purpose for Request</span><p class="text-sm font-medium text-slate-600 whitespace-pre-wrap mt-1">${purpose.trim()}</p></div>`;
                            descEl.innerHTML = reqHtml;
                        } else { 
                            descEl.innerText = d.description; 
                        }
                        descEl.classList.remove('hidden');
                    } else { 
                        descEl.classList.add('hidden'); 
                    }
                    
                    const pfpContainer = document.getElementById('detUserPfpContainer');
                    if(d.user && d.user.profile_picture) {
                        let filename = d.user.profile_picture.split('/').pop().split('\\').pop();
                        pfpContainer.innerHTML = `<img src="/uploads/profiles/${filename}" class="w-full h-full object-cover">`;
                    } else { pfpContainer.innerHTML = `<i class="fas fa-user text-xl"></i>`; }

                    document.getElementById('detUser').innerText = d.user ? (d.user.official_name || d.user.name) : 'Neighbor';

                    const locEl = document.getElementById('detLocation');
                    if (d.location) { locEl.classList.remove('hidden'); document.querySelector('#detLocation span').innerText = d.location; } 
                    else { locEl.classList.add('hidden'); }

                    renderDetailMap(d.latitude, d.longitude, d.condition, d.category, d.title);

                    document.getElementById('detDate').innerText = "Posted " + new Date(d.created_at).toLocaleDateString();
                    
                    const titleContainer = document.getElementById('detTitleContainer');
                    const priceEl = document.getElementById('detPrice');
                    const isBuySell = (d.category.includes('buy') && d.category.includes('sell'));
                    const adminControls = document.getElementById('adminAppointmentControls');
                    const userRole = "{{ Auth::user()->role ?? '' }}";
                    const currentUserId = {{ Auth::id() ?? 'null' }};

                    const likeContainer = document.getElementById('likeButtonContainer');
                    if (['requests', 'complaints'].includes(d.category.replace('_', '-'))) {
                        likeContainer.innerHTML = '';
                    } else {
                        if(currentUserId) {
                             const likeBtnClass = d.is_liked_by_user ? 'text-red-500 bg-red-50 border-red-100' : 'text-slate-400 bg-white border-slate-200 hover:text-red-400 hover:bg-red-50 hover:border-red-100';
                             likeContainer.innerHTML = `<button onclick="toggleLike(${d.id})" id="likeBtn-${d.id}" class="w-full flex items-center justify-center gap-1.5 px-3 py-3 rounded-[1rem] border transition ${likeBtnClass} font-black text-[10px] uppercase tracking-widest shadow-sm"><i class="fas fa-heart text-base"></i><span id="likeCount-${d.id}">${d.likes_count}</span> Like</button>`;
                        } else {
                             likeContainer.innerHTML = `<div class="w-full flex items-center justify-center gap-1.5 px-3 py-3 rounded-[1rem] border text-slate-300 bg-slate-50 border-slate-100 font-black text-[10px] uppercase tracking-widest"><i class="fas fa-heart text-base"></i><span id="likeCount-${d.id}">${d.likes_count}</span> Like</div>`;
                        }
                    }

                    const wishlistContainer = document.getElementById('wishlistButtonContainer');
                    if (['buy-sell', 'borrow', 'services'].includes(d.category.replace('_', '-'))) {
                        const wClass = d.is_wishlisted_by_user ? 'fas text-yellow-500' : 'far text-slate-400';
                        const wBg = d.is_wishlisted_by_user ? 'bg-yellow-50 border-yellow-100' : 'bg-white border-slate-200 hover:bg-slate-50';
                        wishlistContainer.innerHTML = `<button onclick="toggleWishlist(event, ${d.id}, this)" id="wishlistBtnDetail" class="w-full flex items-center justify-center gap-1.5 px-3 py-3 rounded-[1rem] border ${wBg} transition shadow-sm font-black text-[10px] uppercase tracking-widest hover:text-yellow-500 hover:border-yellow-200 ${d.is_wishlisted_by_user ? 'text-yellow-500' : 'text-slate-400'}"><i class="${wClass} fa-bookmark text-base"></i> Save</button>`;
                    } else { wishlistContainer.innerHTML = ''; }

                    const statusBanner = document.getElementById('detStatusBanner');
                    if (['buy-sell', 'borrow', 'services'].includes(d.category.replace('_', '-')) && d.transaction_status && d.transaction_status !== 'available') {
                        let statusMsg = ''; let sColor = '';
                        if (d.transaction_status === 'reserved') { statusMsg = 'Item is Pending / Reserved'; sColor = 'bg-yellow-50 text-yellow-700 border-yellow-200'; } 
                        else { statusMsg = d.category === 'borrow' ? 'Currently Borrowed Out' : 'Sold / Unavailable'; sColor = 'bg-slate-100 text-slate-700 border-slate-200'; }
                        statusBanner.innerHTML = `<div class="p-3 rounded-xl mb-4 font-black uppercase tracking-widest text-[9px] flex items-center gap-2 border shadow-sm ${sColor}"><i class="fas fa-info-circle text-base"></i> ${statusMsg}</div>`;
                        statusBanner.classList.remove('hidden');
                    } else { statusBanner.innerHTML = ''; statusBanner.classList.add('hidden'); }

                    const conditionContainer = document.getElementById('detConditionContainer');
                    if(d.condition && d.category !== 'places') {
                        conditionContainer.innerHTML = `<div class="bg-slate-800 text-white px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-sm">${d.condition}</div>`;
                    } else { conditionContainer.innerHTML = ''; }

                    const tagContainer = document.getElementById('detTagsContainer');
                    tagContainer.innerHTML = '';
                    if(d.tags) {
                        let tagsArray = [];
                        try { tagsArray = typeof d.tags === 'string' ? JSON.parse(d.tags) : d.tags; } catch(e) { tagsArray = [d.tags]; }
                        if(Array.isArray(tagsArray)) {
                            tagsArray.forEach(t => {
                                const badge = document.createElement('div');
                                badge.className = "bg-slate-50 text-slate-500 px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-200";
                                badge.innerText = t; tagContainer.appendChild(badge);
                            });
                        }
                    }

                    if (d.category === 'requests' || d.category === 'complaints') {
                        const statusColors = { 'pending': 'bg-yellow-100 text-yellow-700 border-yellow-200', 'approved': 'bg-green-100 text-green-700 border-green-200', 'completed': 'bg-blue-100 text-blue-700 border-blue-200', 'rejected': 'bg-red-100 text-red-700 border-red-200' };
                        const sColor = statusColors[d.status || 'pending'] || 'bg-slate-100 text-slate-700 border-slate-200';
                        titleContainer.innerHTML = `<h2 class="text-3xl font-black tracking-tighter leading-tight text-slate-800 uppercase mb-3">${d.title}</h2><span class="inline-flex px-3 py-1 rounded-lg text-[9px] font-black tracking-widest uppercase border shadow-sm items-center gap-1.5 ${sColor}">STATUS: ${(d.status || 'pending')}</span>`;
                        if(displayDateTime) { priceEl.innerHTML = `<div class="bg-[#36B3C9]/10 border border-[#36B3C9]/20 p-4 rounded-2xl w-full mt-2"><span class="text-[10px] font-black text-[#36B3C9] uppercase tracking-widest block mb-1"><i class="fas fa-calendar-alt mr-1"></i> Scheduled For</span><span class="text-slate-800 text-lg font-black tracking-tight">${displayDateTime}</span></div>`; } 
                        else { priceEl.innerHTML = `<div class="bg-yellow-50 border border-yellow-100 p-4 rounded-2xl w-full mt-2"><span class="text-[10px] font-black text-yellow-600 uppercase tracking-widest flex items-center gap-2"><i class="fas fa-clock text-base"></i> Waiting for Admin Schedule</span></div>`; }
                        
                        if (userRole === 'admin') {
                            adminControls.innerHTML = `<form action="/post/${d.id}/status" method="POST" class="mt-5 p-5 bg-slate-50 rounded-2xl border border-slate-100 animate-pop"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><h4 class="font-black uppercase text-[9px] mb-4 text-[#36B3C9] flex items-center gap-2 tracking-widest"><i class="fas fa-calendar-check"></i> Admin Appointment</h4><div class="flex flex-col gap-3"><div class="bg-white p-3 rounded-xl shadow-sm"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Update Status</label><select name="status" id="adminStatusSelect" class="w-full p-0 border-none bg-transparent font-bold text-sm text-slate-700 focus:ring-0 cursor-pointer"><option value="pending" ${d.status === 'pending' ? 'selected' : ''}>Pending Review</option><option value="approved" ${d.status === 'approved' ? 'selected' : ''}>Approved / Scheduled</option><option value="completed" ${d.status === 'completed' ? 'selected' : ''}>Completed</option><option value="rejected" ${d.status === 'rejected' ? 'selected' : ''}>Rejected</option></select></div><div class="bg-white p-3 rounded-xl shadow-sm"><label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Set Date & Time</label><input type="datetime-local" name="event_date" value="${inputDateTime}" class="w-full p-0 border-none bg-transparent font-bold text-sm text-slate-700 focus:ring-0 cursor-pointer"></div></div><button type="submit" class="mt-3 w-full bg-slate-800 text-white font-black py-3 rounded-xl uppercase tracking-widest text-[9px] shadow-lg hover:bg-slate-700 active:scale-95 transition">Save</button></form>`;
                        } else { adminControls.innerHTML = ''; }
                    } else {
                        adminControls.innerHTML = '';
                        titleContainer.innerHTML = `<h2 class="text-3xl lg:text-4xl font-black tracking-tighter leading-none text-slate-800 uppercase">${d.title}</h2>`;
                        if(d.category === 'events' && d.event_date) { priceEl.innerHTML = `<div class="mb-2 mt-2"><span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Happening On</span><span class="text-2xl font-black text-orange-400">${displayDateTime || new Date(d.event_date).toLocaleDateString()}</span></div>`; } 
                        else if(isBuySell) { priceEl.innerHTML = `<div class="mb-2 mt-2"><span class="text-4xl font-black text-[#36B3C9] tracking-tighter">${d.price ? '₱' + parseFloat(d.price).toLocaleString() : 'Free'}</span></div>`; } 
                        else { priceEl.innerHTML = ''; }
                    }

                    const statusContainer = document.getElementById('sellerStatusContainer');
                    if (currentUserId === d.user_id && ['buy-sell', 'services', 'borrow'].includes(d.category.replace('_', '-'))) {
                        let soldText = d.category === 'borrow' ? 'Borrowed Out' : (d.category === 'services' ? 'Unavailable' : 'Sold');
                        statusContainer.innerHTML = `<div class="mt-2 mb-6 bg-slate-50 p-4 rounded-2xl border border-slate-100"><label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Update Item Status</label><select onchange="updateItemStatus(${d.id}, this.value)" class="w-full mt-2 rounded-xl border-none bg-white font-bold text-sm text-slate-700 cursor-pointer shadow-sm focus:ring-2 focus:ring-[#36B3C9]/20 p-2"><option value="available" ${d.transaction_status === 'available' ? 'selected' : ''}>Available</option><option value="reserved" ${d.transaction_status === 'reserved' ? 'selected' : ''}>Reserved / Pending</option><option value="sold" ${d.transaction_status === 'sold' ? 'selected' : ''}>${soldText}</option></select></div>`;
                    } else { statusContainer.innerHTML = ''; }

                    const contactArea = document.getElementById('contactButtonContainer');
                    contactArea.innerHTML = ''; 
                    if(isBuySell || d.category === 'borrow' || d.category === 'services') {
                        if (currentUserId !== null && d.user_id !== currentUserId) {
                            const safeTitle = d.title.replace(/['"]/g, '');
                            const ownerName = d.user ? (d.user.official_name || d.user.name).replace(/['"]/g, '') : 'Owner';
                            
                            // Extract just the first name for the chat popup button
                            const firstName = ownerName ? ownerName.split(' ')[0] : 'User';

                            // 👇 CHANGED: Triggers the new Unverified Popup instead of an alert 👇
                            if (!isUserVerified) {
                                contactArea.innerHTML = `<button onclick="toggleModal('unverifiedModal')" class="w-full bg-slate-200 text-slate-400 font-black py-3.5 rounded-xl flex items-center justify-center gap-2 uppercase tracking-widest text-[10px] cursor-pointer hover:bg-slate-300 hover:text-slate-500 transition mb-2"><i class="fas fa-lock"></i> Verify to Message</button>`;
                            } else {
                                if(isBuySell) {
                                    contactArea.innerHTML = `<div class="flex flex-col gap-2.5 w-full mb-2"><div class="flex rounded-xl overflow-hidden border border-[#36B3C9]/20 shadow-md shadow-cyan-100/50 bg-slate-50"><span class="flex items-center pl-4 text-slate-400 font-black text-sm">₱</span><input type="number" id="offerInput" placeholder="Amount" class="flex-1 border-none bg-slate-50 px-2 font-bold text-sm text-slate-800 focus:ring-0 py-3 min-w-0"><button onclick="sendOffer('${ownerName}', '${safeTitle}')" class="bg-[#36B3C9] text-white px-5 font-black uppercase text-[10px] tracking-widest hover:brightness-110 transition">Offer</button></div><button onclick="openChatBox(${d.id}, '${firstName}', '${safeTitle}')" class="w-full bg-slate-800 text-white font-black py-3.5 rounded-xl shadow-lg transition hover:bg-slate-700 active:scale-95 uppercase tracking-widest text-[10px] flex items-center justify-center gap-2"><i class="fas fa-comment-alt"></i> Send Message</button></div>`;
                                } else {
                                    contactArea.innerHTML = `<button onclick="openChatBox(${d.id}, '${firstName}', '${safeTitle}')" class="w-full bg-[#36B3C9] text-white font-black py-4 rounded-xl shadow-lg shadow-cyan-100 flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition uppercase tracking-widest text-[10px] mb-2"><i class="fas fa-comment-alt"></i> Message User</button>`;
                                }
                            }
                        }
                    }
                    
                    const editArea = document.getElementById('detEditContainer');
                    const deleteArea = document.getElementById('detDeleteContainer');
                    if(userRole === 'admin' || userRole === 'moderator' || currentUserId === d.user_id) {
                        editArea.innerHTML = `<button onclick="openEditModal(${d.id})" class="w-full bg-white text-[#36B3C9] font-black py-3.5 rounded-xl hover:bg-cyan-50 transition flex items-center justify-center gap-2 uppercase tracking-widest text-[10px] border border-cyan-100 shadow-sm"><i class="fas fa-edit"></i> Edit</button>`;
                        deleteArea.innerHTML = `<button onclick="triggerDelete(${d.id})" class="w-full bg-white text-red-500 font-black py-3.5 rounded-xl hover:bg-red-50 transition flex items-center justify-center gap-2 uppercase tracking-widest text-[10px] border border-red-100 shadow-sm"><i class="fas fa-trash-alt"></i> Remove</button>`;
                    } else { deleteArea.innerHTML = ''; editArea.innerHTML = ''; }

                    const reportArea = document.getElementById('detReportContainer');
                    if (currentUserId !== null && currentUserId !== d.user_id && userRole !== 'admin' && userRole !== 'moderator') {
                        reportArea.innerHTML = `<button onclick="openReportModal(${d.id})" class="w-full flex items-center justify-center gap-1.5 px-3 py-3 rounded-[1rem] border text-slate-400 bg-white border-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-red-50 hover:text-red-500 hover:border-red-100 transition shadow-sm"><i class="fas fa-flag text-base"></i> Report</button>`;
                    } else { reportArea.innerHTML = ''; }

                    const imgSection = document.getElementById('detailImageSection'); 
                    const imgContainer = document.getElementById('detImg');
                    let images = []; 
                    try { images = Array.isArray(d.image) ? d.image : (d.image ? JSON.parse(d.image) : []); } catch(e) { images = []; } 
                    if (images.length > 0) { 
                        imgSection.classList.remove('hidden'); 
                        imgContainer.innerHTML = images.map(img => `<div class="w-full h-full flex-shrink-0 snap-center flex items-center justify-center bg-black"><img src="/uploads/${img}" class="max-w-full max-h-full object-contain"></div>`).join(''); 
                        totalImgs = images.length; currentIdx = 0; 
                        if(totalImgs <= 1) { document.getElementById('prevBtn').classList.add('hidden'); document.getElementById('nextBtn').classList.add('hidden'); } 
                        else { document.getElementById('prevBtn').classList.remove('hidden'); document.getElementById('nextBtn').classList.remove('hidden'); }
                    } else { imgSection.classList.add('hidden'); }
                    
                    if (document.getElementById('detailModal').classList.contains('hidden')) { toggleModal('detailModal'); }
                });
        }
        
        function triggerDelete(id) { 
            document.getElementById('deleteForm').action = `/post/${id}`; 
            if(!document.getElementById('detailModal').classList.contains('hidden')) { toggleModal('detailModal'); }
            toggleModal('deleteConfirmModal'); 
        }

        function moveGallery(dir) { 
            const c = document.getElementById('detImg'); 
            currentIdx += dir; 
            if (currentIdx < 0) currentIdx = totalImgs - 1; 
            if (currentIdx >= totalImgs) currentIdx = 0; 
            c.scrollTo({ left: c.clientWidth * currentIdx, behavior: 'smooth' }); 
        }

        // Click outside to close chat options
        document.addEventListener('click', function(event) {
            document.querySelectorAll('[id^="chatOptions-"]').forEach(dropdown => {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        });

        function getCsrfToken() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }

        function toggleInboxPanel() {
            const panel = document.getElementById('inboxPanel');
            if (panel) {
                panel.classList.toggle('translate-x-full');
                if (!panel.classList.contains('translate-x-full')) { loadInbox(); }
            }
        }

        function loadInbox() {
            const inboxContent = document.getElementById('inboxContent');
            inboxContent.innerHTML = '<div class="text-center text-xs font-bold text-slate-400 mt-10">Loading messages...</div>';
            fetch(`/api/inbox?category={{ $type ?? '' }}`)
                .then(r => r.json())
                .then(conversations => {
                    inboxContent.innerHTML = '';
                    let unreadTotal = 0;
                    if(conversations.length === 0) {
                        inboxContent.innerHTML = `<div class="text-center mt-12 text-slate-400"><i class="fas fa-box-open text-4xl mb-4 text-slate-200 block"></i><p class="text-xs font-black uppercase tracking-widest">No Messages Here</p></div>`;
                    } else {
                        conversations.forEach(conv => {
                            const currentUserId = {{ Auth::id() ?? 'null' }};
                            const otherUser = conv.sender_id === currentUserId ? conv.receiver : conv.sender;
                            const lastMsg = conv.messages.length > 0 ? conv.messages[0] : null;
                            const isUnread = lastMsg && lastMsg.user_id !== currentUserId && !lastMsg.is_read;
                            if (isUnread) unreadTotal++;
                            let msgText = lastMsg ? lastMsg.body : 'No messages yet.';
                            if(msgText && msgText.startsWith('[OFFER-')) msgText = '<i class="fas fa-hand-holding-usd text-[#36B3C9]"></i> Sent an offer.';
                            else if(msgText && msgText.startsWith('[ACCEPT-')) msgText = '<i class="fas fa-check-circle text-green-500"></i> Accepted the offer!';
                            else if(msgText && msgText.startsWith('[DECLINE-')) msgText = '<i class="fas fa-times-circle text-red-500"></i> Declined the offer.';
                            else if(lastMsg && lastMsg.image) msgText = '<i class="fas fa-image text-slate-400"></i> Sent an image.';
                            let dateText = lastMsg ? new Date(lastMsg.created_at).toLocaleDateString() : '';
                            let unreadClass = isUnread ? 'bg-white border-l-4 border-[#36B3C9] shadow-sm' : 'bg-white border border-slate-100 hover:shadow-sm';
                            let titleClass = isUnread ? 'text-[#36B3C9] font-black' : 'text-slate-700 font-bold';
                            
                            const safeTitle = conv.post ? conv.post.title.replace(/['"]/g, '') : 'Post';
                            
                            const otherUserName = otherUser ? (otherUser.official_name || otherUser.name).replace(/['"]/g, '') : 'Deleted User';
                            
                            const html = `<div onclick="openChatFromInbox(${conv.post_id}, '${otherUserName}', '${safeTitle}')" class="p-3.5 rounded-xl cursor-pointer transition hover:shadow-md ${unreadClass}"><div class="flex justify-between items-start mb-1"><span class="text-xs ${titleClass} truncate">${otherUserName} <span class="font-normal text-slate-400 mx-0.5">–</span> ${safeTitle}</span><span class="text-[9px] font-bold text-slate-300 ml-2 whitespace-nowrap flex-shrink-0">${dateText}</span></div><p class="text-xs text-slate-500 truncate mt-1 ${isUnread ? 'font-bold text-slate-700' : ''}">${lastMsg && lastMsg.user_id === currentUserId ? '<span class="text-slate-300">You: </span>' : ''}${msgText}</p></div>`;
                            inboxContent.insertAdjacentHTML('beforeend', html);
                        });
                    }
                    const badge = document.getElementById('unreadBadge');
                    if (badge) { 
                        if (unreadTotal > 0) { badge.innerText = unreadTotal; badge.classList.remove('hidden'); } 
                        else { badge.classList.add('hidden'); }
                    }
                }).catch(err => console.error(err));
        }

        function openChatFromInbox(postId, postOwnerName, postTitle) { 
            toggleInboxPanel(); 
            const firstName = postOwnerName ? postOwnerName.split(' ')[0] : 'User';
            openChatBox(postId, firstName, postTitle); 
        }

        function openChatBox(postId, postOwnerName, postTitle = 'Chat', initialMessage = null) {
            const detailModal = document.getElementById('detailModal');
            if(detailModal && !detailModal.classList.contains('hidden')) { toggleModal('detailModal'); }
            document.querySelectorAll('[id^="chatBox-"]').forEach(box => { 
                if (!box.classList.contains('hidden') && box.id !== 'chatBox-' + postId) { box.classList.add('hidden'); box.classList.remove('flex'); } 
            });
            let box = document.getElementById('chatBox-' + postId);
            if(!box) {
                let chatInputForm = '';
                if (isUserVerified) {
                    chatInputForm = `
                        <form onsubmit="sendChatMessage(event, ${postId})" class="flex items-center w-full bg-slate-100 rounded-[1rem] p-1 pr-1.5 gap-1 shadow-inner relative">
                            <label class="cursor-pointer text-slate-400 hover:text-[#36B3C9] w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white transition-colors shrink-0">
                                <i class="fas fa-image text-sm"></i>
                                <input type="file" id="chatImage-${postId}" class="hidden" accept="image/*" onchange="previewChatImage(this, ${postId})">
                            </label>
                            <input type="text" id="chatInput-${postId}" class="flex-1 bg-transparent border-none px-2 py-2 text-sm font-bold text-slate-700 focus:ring-0 placeholder:text-slate-400 min-w-0 h-10" placeholder="Message..." autocomplete="off">
                            <button type="submit" class="bg-[#36B3C9] text-white w-9 h-9 flex items-center justify-center rounded-xl font-bold hover:brightness-110 active:scale-95 transition shadow-sm shrink-0">
                                <i class="fas fa-paper-plane text-[10px] pl-0.5"></i>
                            </button>
                        </form>
                        <div id="chatImagePreview-${postId}" class="absolute bottom-16 left-4 hidden bg-white p-2 rounded-2xl shadow-xl border border-slate-100 z-10 animate-pop">
                            <div class="relative">
                                <img id="chatImagePreviewImg-${postId}" class="w-16 h-16 object-cover rounded-lg border border-slate-100">
                                <button type="button" onclick="clearChatImage(${postId})" class="absolute -top-2 -right-2 bg-red-500 text-white w-5 h-5 rounded-full text-[8px] flex items-center justify-center shadow-lg hover:bg-red-600 transition"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    `;
                } else {
                    chatInputForm = `<div class="bg-slate-100 text-slate-400 font-black uppercase tracking-widest text-[10px] p-3.5 rounded-2xl text-center flex items-center justify-center gap-2"><i class="fas fa-lock"></i> Verify Profile to Reply</div>`;
                }
                
                const boxHtml = `
                    <div id="chatBox-${postId}" class="hidden fixed bottom-0 right-24 sm:right-32 w-72 bg-white border border-slate-200 shadow-2xl rounded-t-[1.5rem] flex-col z-[200]">
                        <div class="bg-[#36B3C9] text-white px-4 py-3.5 rounded-t-[1.5rem] flex justify-between items-center shadow-sm relative">
                            <div class="flex-1 min-w-0 cursor-pointer" onclick="toggleChatBody(${postId})">
                                <span class="font-black uppercase tracking-widest text-[10px] truncate flex items-center gap-2 pr-2"><i class="fas fa-comment-dots"></i> ${postOwnerName}</span>
                            </div>
                            
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button onclick="toggleChatOptions(${postId}, event)" class="text-white/70 hover:text-white transition p-1 w-6 h-6 rounded-md hover:bg-white/20 flex items-center justify-center">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <button onclick="closeChatBox(${postId}, event)" class="text-white/70 hover:text-white transition p-1 w-6 h-6 rounded-md hover:bg-white/20 flex items-center justify-center text-lg leading-none">
                                    &times;
                                </button>
                            </div>
                            
                            <div id="chatOptions-${postId}" class="hidden absolute top-12 right-2 bg-white border border-slate-100 shadow-xl rounded-[1.5rem] w-56 overflow-hidden z-[210] animate-pop">
                                <div class="p-1">
                                    <button onclick="openDetail(${postId}); document.getElementById('chatOptions-${postId}').classList.add('hidden');" class="w-full text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50 rounded-xl transition flex items-center gap-3">
                                        <i class="fas fa-external-link-alt text-[#36B3C9] w-4"></i> View Listing
                                    </button>
                                    <button onclick="openReportModal(${postId}); document.getElementById('chatOptions-${postId}').classList.add('hidden');" class="w-full text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-50 rounded-xl transition flex items-center gap-3">
                                        <i class="fas fa-flag text-orange-400 w-4"></i> Report Post
                                    </button>
                                    <div class="h-px bg-slate-100 my-1 mx-2"></div>
                                    <button onclick="confirmDeleteConversation(${postId}, event)" class="w-full text-left px-4 py-3 text-[10px] font-black uppercase tracking-widest text-red-500 hover:bg-red-50 rounded-xl transition flex items-center gap-3">
                                        <i class="fas fa-trash-alt w-4"></i> Delete Chat
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="chatBody-${postId}" class="flex flex-col">
                            <div id="chatMessages-${postId}" class="h-60 overflow-y-auto p-3 bg-slate-50 flex flex-col gap-2 scrollbar-hide">
                                <div class="text-center text-[10px] font-black uppercase tracking-widest text-slate-300 mt-10">Loading messages...</div>
                            </div>
                            <div class="p-2.5 border-t border-slate-100 bg-white relative">
                                ${chatInputForm}
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', boxHtml);
                box = document.getElementById('chatBox-' + postId);
            }
            box.classList.remove('hidden'); box.classList.add('flex'); fetchMessages(postId, initialMessage);
        }

        function closeChatBox(postId, event) { 
            event.stopPropagation(); const box = document.getElementById('chatBox-' + postId); 
            if(box) { box.classList.add('hidden'); box.classList.remove('flex'); } 
        }

        function toggleChatBody(postId) { 
            const body = document.getElementById('chatBody-' + postId); 
            if(body) body.classList.toggle('hidden'); 
        }

        function fetchMessages(postId, initialMessage = null) {
            fetch(`/api/chat/${postId}`)
                .then(response => response.json())
                .then(data => {
                    const messagesContainer = document.getElementById('chatMessages-' + postId);
                    if(!messagesContainer) return;
                    messagesContainer.innerHTML = '';
                    if(data.messages.length === 0) { messagesContainer.innerHTML = '<div class="text-center text-[10px] font-black uppercase tracking-widest text-slate-300 mt-10">Start the conversation!</div>'; }
                    data.messages.forEach(msg => { appendMessageToDOM(postId, msg, data.current_user_id, data.post_owner_id); });
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    if(initialMessage) { 
                        document.getElementById('chatInput-' + postId).value = initialMessage; 
                        document.querySelector(`#chatBox-${postId} form`).dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })); 
                    }
                }).catch(err => console.error(err));
        }

        function sendChatMessage(event, postId) {
            event.preventDefault(); 
            const input = document.getElementById('chatInput-' + postId); 
            const fileInput = document.getElementById('chatImage-' + postId);
            const body = input ? input.value : ''; 
            const file = fileInput ? fileInput.files[0] : null;
            if (!body && !file) return; 
            if(input) input.value = '';
            clearChatImage(postId);
            const formData = new FormData();
            if(body) formData.append('body', body);
            if(file) formData.append('image', file);
            fetch(`/api/chat/${postId}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': getCsrfToken() }, body: formData })
            .then(response => response.json())
            .then(payload => {
                let msg = payload.message || payload; 
                let ownerId = payload.post_owner_id || currentDetailPostId;
                appendMessageToDOM(postId, msg, msg.user_id, ownerId);
                const messagesContainer = document.getElementById('chatMessages-' + postId);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }).catch(err => console.error(err));
        }

        function appendMessageToDOM(postId, msg, currentUserId, postOwnerId) {
            const messagesContainer = document.getElementById('chatMessages-' + postId);
            if(!messagesContainer) return;
            if(messagesContainer.innerHTML.includes('Start the conversation')) { messagesContainer.innerHTML = ''; }
            const isMe = msg.user_id === currentUserId;
            const alignClass = isMe ? 'justify-end' : 'justify-start';
            let bgClass = isMe ? 'bg-[#36B3C9] text-white rounded-l-2xl rounded-tr-2xl' : 'bg-white border border-slate-200 text-slate-800 rounded-r-2xl rounded-tl-2xl';
            let displayBody = msg.body ? msg.body : ''; 
            let extraHtml = '';
            if (msg.image) { extraHtml += `<a href="/uploads/messages/${msg.image}" target="_blank"><img src="/uploads/messages/${msg.image}" class="w-full rounded-xl mt-1.5 shadow-sm border border-black/10"></a>`; }
            if (msg.body) {
                const offerMatch = msg.body.match(/^\[OFFER-(.+)\]$/);
                const acceptMatch = msg.body.match(/^\[ACCEPT-(.+)\]$/);
                const declineMatch = msg.body.match(/^\[DECLINE-(.+)\]$/);
                if (offerMatch) {
                    const amount = offerMatch[1];
                    displayBody = `<span class="opacity-80 text-[10px] uppercase tracking-widest font-black block mb-1">Offer Made</span><strong class="text-xl font-black ${isMe ? 'text-white' : 'text-[#36B3C9]'} tracking-tight block my-1">₱${amount}</strong>`;
                    if (currentUserId === postOwnerId && !isMe) {
                        extraHtml += `<div class="mt-3 flex gap-2 border-t border-slate-200/50 pt-3"><button type="button" onclick="respondToOffer(${postId}, '${amount}', 'accept')" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-xl text-[10px] uppercase font-black tracking-widest transition active:scale-95 shadow-sm">Accept</button><button type="button" onclick="respondToOffer(${postId}, '${amount}', 'decline')" class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-xl text-[10px] uppercase font-black tracking-widest transition active:scale-95 shadow-sm">Decline</button></div>`;
                    }
                } else if (acceptMatch) {
                    displayBody = `<i class="fas fa-check-circle text-base mb-1 block"></i><span class="opacity-80 text-[10px] uppercase tracking-widest font-black block mb-1">Offer Accepted</span><strong class="text-lg font-black tracking-tight block my-1">₱${acceptMatch[1]}</strong>`;
                    bgClass = isMe ? 'bg-green-500 text-white rounded-l-2xl rounded-tr-2xl' : 'bg-green-50 border border-green-200 text-green-800 rounded-r-2xl rounded-tl-2xl';
                } else if (declineMatch) {
                    displayBody = `<i class="fas fa-times-circle text-base mb-1 block"></i><span class="opacity-80 text-[10px] uppercase tracking-widest font-black block mb-1">Offer Declined</span><strong class="text-lg font-black tracking-tight block my-1">₱${declineMatch[1]}</strong>`;
                    bgClass = isMe ? 'bg-red-500 text-white rounded-l-2xl rounded-tr-2xl' : 'bg-red-50 border border-red-200 text-red-800 rounded-r-2xl rounded-tl-2xl';
                }
            }
            const messageHtml = `<div class="flex ${alignClass} w-full my-1.5"><div class="max-w-[85%] ${bgClass} p-3.5 shadow-sm text-sm font-medium leading-snug">${displayBody}${extraHtml}</div></div>`;
            messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
        }

        function toggleLike(postId) {
            fetch(`/post/${postId}/like`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() } })
            .then(r => r.json())
            .then(data => {
                const likeBtn = document.getElementById(`likeBtn-${postId}`); 
                const likeCount = document.getElementById(`likeCount-${postId}`);
                if(likeBtn) {
                    if (data.liked) { 
                        likeBtn.className = `w-full flex items-center justify-center gap-1.5 px-3 py-3 rounded-[1rem] border transition text-red-500 bg-red-50 border-red-100 font-black text-[10px] uppercase tracking-widest shadow-sm`;
                    } else { 
                        likeBtn.className = `w-full flex items-center justify-center gap-1.5 px-3 py-3 rounded-[1rem] border transition text-slate-400 bg-white border-slate-200 hover:text-red-400 hover:bg-red-50 hover:border-red-100 font-black text-[10px] uppercase tracking-widest shadow-sm`;
                    }
                }
                if(likeCount) likeCount.innerText = data.count;
            }).catch(e => console.error("Error toggling like:", e));
        }

        function sendOffer(postOwnerName, postTitle) {
            const amount = document.getElementById('offerInput').value;
            if (!amount || !currentDetailPostId) return alert("Please enter an offer amount.");
            const formattedAmount = new Intl.NumberFormat().format(amount);
            openChatBox(currentDetailPostId, postOwnerName, postTitle, `[OFFER-${formattedAmount}]`);
        }

        function respondToOffer(postId, amount, action) {
            document.getElementById('chatInput-' + postId).value = action === 'accept' ? `[ACCEPT-${amount}]` : `[DECLINE-${amount}]`;
            document.querySelector(`#chatBox-${postId} form`).dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }

        window.togglePlacesFullscreen = function(e) {
            if (e) e.preventDefault();
            const mapContainer = document.getElementById('placesMap');
            const icon = document.getElementById('fs-icon');
            if (!mapContainer.classList.contains('fixed')) {
                mapContainer.classList.remove('relative', 'h-[60vh]', 'rounded-[2rem]', 'z-0');
                mapContainer.classList.add('fixed', 'inset-0', 'w-screen', 'h-screen', 'z-[9999]', 'rounded-none');
                icon.classList.replace('fa-expand', 'fa-compress');
            } else {
                mapContainer.classList.remove('fixed', 'inset-0', 'w-screen', 'h-screen', 'z-[9999]', 'rounded-none');
                mapContainer.classList.add('relative', 'w-full', 'h-[60vh]', 'rounded-[2rem]', 'z-0');
                icon.classList.replace('fa-compress', 'fa-expand');
            }
            setTimeout(() => { if(window.placesMapInstance) window.placesMapInstance.invalidateSize(); }, 300);
        };

        @if($normalizedType === 'places')
        document.addEventListener('DOMContentLoaded', function() {
            var map = L.map('placesMap', { center: baybayPolongCenter, zoom: 16, minZoom: 15, maxBounds: baybayPolongBounds, maxBoundsViscosity: 1.0 });
            window.placesMapInstance = map; 
            var fullscreenBtn = L.control({position: 'topleft'});
            fullscreenBtn.onAdd = function() {
                var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                div.innerHTML = `<button onclick="togglePlacesFullscreen(event)" class="bg-white text-slate-800 w-8 h-8 rounded shadow flex items-center justify-center hover:text-[#36B3C9] transition focus:outline-none"><i id="fs-icon" class="fas fa-expand"></i></button>`;
                return div;
            };
            fullscreenBtn.addTo(map);
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: '© Esri' }).addTo(map);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_only_labels/{z}/{x}/{y}{r}.png', { maxZoom: 19, attribution: '© CartoDB' }).addTo(map);
            addCustomRoadLabel(map);
            @foreach($posts as $post)
                @if($post->latitude && $post->longitude)
                    @php $canDelete = ($isAdmin || $isModerator || Auth::id() === $post->user_id); @endphp
                    let popupHtml_{{ $post->id }} = `<div class='text-center p-1'><b class="text-sm text-slate-800">{{ addslashes($post->title) }}</b><br><span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ str_replace(['_', '-'], ' ', $post->category) }}</span><br><span class="text-xs text-slate-500">{{ addslashes($post->location ?? 'Baybay Polong') }}</span><br><button onclick='openDetail({{ $post->id }})' class='text-[10px] font-black text-[#36B3C9] mt-3 uppercase tracking-widest bg-[#36B3C9]/10 px-3 py-1.5 rounded-lg hover:bg-[#36B3C9] hover:text-white transition inline-block'>View Details</button>@if($canDelete)<div class="mt-2 pt-2 border-t border-slate-100"><button onclick='triggerDelete({{ $post->id }})' class='text-[9px] font-black text-red-500 uppercase tracking-widest hover:text-red-700 transition'><i class="fas fa-trash"></i> Remove</button></div>@endif</div>`;
                    L.marker([{{ $post->latitude }}, {{ $post->longitude }}], { icon: getCustomIcon("{{ $post->condition }}", "{{ $post->category }}") })
                        .addTo(map)
                        .bindTooltip("{{ addslashes($post->title) }}", { permanent: true, direction: 'top', offset: [0, -40], className: 'landmark-label' })
                        .bindPopup(popupHtml_{{ $post->id }});
                @endif
            @endforeach
        });
        @endif

        @if($isEvent)
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            if(calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
                    height: 550,
                    events: @json($calendarEvents),
                    eventClick: function(info) { openDetail(info.event.id); }
                });
                calendar.render();
            }
        });
        @endif

        document.addEventListener('DOMContentLoaded', function() {
            const flashMsg = document.getElementById('flash-message');
            if (flashMsg) {
                setTimeout(() => {
                    flashMsg.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    flashMsg.style.opacity = '0';
                    flashMsg.style.transform = 'translate(-50%, -20px)';
                    setTimeout(() => flashMsg.remove(), 500);
                }, 5000);
            }

            const urlParams = new URLSearchParams(window.location.search);
            const postIdToOpen = urlParams.get('post');
            const chatIdToOpen = urlParams.get('chat');
            if (postIdToOpen) { openDetail(postIdToOpen); window.history.replaceState({}, document.title, window.location.pathname); }
            if (chatIdToOpen) { 
                fetch(`/api/post/${chatIdToOpen}`).then(r => r.json()).then(d => { openChatBox(d.id, (d.user ? (d.user.official_name || d.user.name) : 'Owner'), d.title.replace(/['"]/g, '')); }); 
                window.history.replaceState({}, document.title, window.location.pathname); 
            }
        });
    </script>
</body>
</html>