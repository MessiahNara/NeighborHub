<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <title>NeighborHub | {{ str_replace(['_', '-'], ' ', $type) }}</title>
    <style> 
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        #detImg { scroll-behavior: smooth; -webkit-overflow-scrolling: touch; }
        .animate-pop { animation: pop 0.4s cubic-bezier(0.26, 0.53, 0.74, 1.48); }
        @keyframes pop { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        /* Calendar Customization */
        .fc { background: white; border-radius: 2.5rem; padding: 2rem; border: none !important; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05); }
        .fc-toolbar-title { font-weight: 900 !important; text-transform: uppercase; color: #1e293b; font-size: 1.5rem !important; }
        .fc-button-primary { background-color: #36B3C9 !important; border: none !important; border-radius: 1rem !important; font-weight: bold !important; }
        .fc-daygrid-event { background-color: #36B3C9; border: none; border-radius: 6px; padding: 2px 6px; cursor: pointer; }
        .fc-day-today { background-color: #f1fbfd !important; border-radius: 1.5rem; }

        /* Custom Multiple Select Styling */
        .tag-checkbox:checked + label { background-color: #36B3C9; color: white; border-color: #36B3C9; }
    </style>
</head>

@php
    $normalizedType = str_replace('_', '-', $type); 
    
    $isBuySell   = (str_contains($normalizedType, 'buy') && str_contains($normalizedType, 'sell'));
    $isBorrow    = ($normalizedType === 'borrow');
    $isEvent     = in_array($normalizedType, ['event', 'events']);
    $isComplaint = ($normalizedType === 'complaints');
    $isRequest   = ($normalizedType === 'requests');
    
    // Check if the current category is strictly for admins
    $adminOnlyCategories = ['events', 'places', 'announcements', 'announce', 'event'];
    $isAdminOnly = in_array($normalizedType, $adminOnlyCategories);
    $isAdmin     = (Auth::check() && Auth::user()->role === 'admin');

    $useGrid = ($isBuySell || $isBorrow || in_array($normalizedType, ['services', 'places']));
    
    // --- 1. DEFINE TAGS HERE ---
    $availableTags = [];
    if($isBuySell) {
        $availableTags = ['Electronics', 'Furniture', 'Clothing', 'Vehicles', 'Books', 'Tools', 'Appliances', 'Other'];
    } elseif($isBorrow) {
        $availableTags = ['Tools', 'Garden', 'Kitchen', 'Party Supplies', 'Books', 'Camping', 'Tech', 'Other'];
    } elseif($normalizedType === 'services') {
        $availableTags = ['Cleaning', 'Repairs', 'Tutoring', 'Delivery', 'Pet Care', 'Other'];
    } elseif($isComplaint) {
        $availableTags = ['Noise', 'Waste Disposal', 'Security', 'Dispute', 'Vandalism', 'Other'];
    }

    $calendarEvents = [];
    if($isEvent) {
        foreach($posts as $p) {
            if($p->event_date) {
                $calendarEvents[] = [
                    'id' => $p->id,
                    'title' => $p->title,
                    'start' => \Carbon\Carbon::parse($p->event_date)->format('Y-m-d'),
                ];
            }
        }
    }
@endphp

<body class="bg-slate-50 font-sans antialiased min-h-screen text-slate-900">

    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-100 px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="h-12 w-12 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center transition hover:bg-[#36B3C9] hover:text-white active:scale-90 shadow-sm">
                <i class="fas fa-arrow-left"></i>
            </a>
            
            <form action="{{ url()->current() }}" method="GET" class="flex-1 flex gap-3">
                <div class="relative flex-1 group">
                    <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-300 z-10"></i>
                    <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="Search in {{ str_replace(['_', '-'], ' ', $type) }}..." 
                           class="w-full bg-slate-50 border-none rounded-2xl py-3.5 pl-14 pr-10 focus:ring-2 focus:ring-[#36B3C9]/20 transition font-bold text-sm placeholder:text-slate-300 shadow-sm">
                </div>

                @if(!empty($availableTags))
                <select name="tag" onchange="this.form.submit()" class="bg-slate-50 border-none rounded-2xl px-6 font-bold text-sm text-slate-500 shadow-sm focus:ring-2 focus:ring-[#36B3C9]/20 cursor-pointer">
                    <option value="">All Tags</option>
                    @foreach($availableTags as $tag)
                        <option value="{{ $tag }}" {{ request('tag') == $tag ? 'selected' : '' }}>{{ $tag }}</option>
                    @endforeach
                </select>
                @endif
                
                <button type="submit" class="hidden md:block bg-slate-100 text-slate-400 px-6 rounded-2xl font-bold text-xs hover:bg-[#36B3C9] hover:text-white transition">Search</button>
            </form>
            
            @if(!$isRequest && (!$isAdminOnly || $isAdmin))
                <button onclick="toggleModal('addModal')" class="bg-[#36B3C9] text-white h-12 px-6 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-cyan-100 transition hover:brightness-110 active:scale-95 flex items-center gap-2">
                    @if($isComplaint)
                        <i class="fas fa-exclamation-triangle"></i> <span class="hidden md:inline">File a Complaint</span>
                    @else
                        <i class="fas fa-plus"></i> <span class="hidden md:inline">Post</span>
                    @endif
                </button>
            @endif
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6 pb-24">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-4">
            <div>
                <h1 class="text-5xl font-black uppercase tracking-tighter text-slate-800 leading-none">{{ str_replace(['_', '-'], ' ', $type) }}</h1>
                @if($isAdmin || !in_array($normalizedType, ['places', 'complaints', 'requests']))
                    <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-2 ml-1">{{ count($posts) }} Active Records</p>
                @endif
            </div>

            @if(in_array($normalizedType, ['buy-sell', 'borrow', 'services']))
                <div class="flex bg-slate-200/50 p-1 rounded-2xl">
                    <a href="{{ request()->fullUrlWithQuery(['my_posts' => null]) }}" 
                       class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ !request('my_posts') ? 'bg-white text-[#36B3C9] shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                        All
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['my_posts' => '1']) }}" 
                       class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request('my_posts') == '1' ? 'bg-white text-[#36B3C9] shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">
                        My Posts
                    </a>
                </div>
            @endif
        </div>

        @if($isEvent)
            <div class="relative z-10 bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden mb-12 p-3">
                <div id="calendar"></div>
            </div>
        @endif

        @if($isRequest)
            <h2 class="text-2xl font-black text-slate-800 mb-4 uppercase tracking-tighter">Files Available to Request</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div onclick="openRequestModal('Certificate of Residency')" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition cursor-pointer group flex items-center gap-4">
                    <div class="bg-blue-50 text-blue-500 w-16 h-16 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition"><i class="fas fa-home"></i></div>
                    <div>
                        <h3 class="font-black text-lg text-slate-800 leading-tight group-hover:text-blue-500 transition">Certificate of Residency</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Request Document <i class="fas fa-arrow-right ml-1"></i></p>
                    </div>
                </div>
                <div onclick="openRequestModal('Certificate of Indigency')" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition cursor-pointer group flex items-center gap-4">
                    <div class="bg-green-50 text-green-500 w-16 h-16 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition"><i class="fas fa-hands-helping"></i></div>
                    <div>
                        <h3 class="font-black text-lg text-slate-800 leading-tight group-hover:text-green-500 transition">Certificate of Indigency</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Request Document <i class="fas fa-arrow-right ml-1"></i></p>
                    </div>
                </div>
                <div onclick="openRequestModal('Barangay Clearance')" class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition cursor-pointer group flex items-center gap-4">
                    <div class="bg-purple-50 text-purple-500 w-16 h-16 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition"><i class="fas fa-stamp"></i></div>
                    <div>
                        <h3 class="font-black text-lg text-slate-800 leading-tight group-hover:text-purple-500 transition">Barangay Clearance</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Request Document <i class="fas fa-arrow-right ml-1"></i></p>
                    </div>
                </div>
            </div>
            <h2 class="text-2xl font-black text-slate-800 mb-4 uppercase tracking-tighter">Your Recent Requests</h2>
        @endif

        <div class="relative z-10 {{ $useGrid ? 'grid grid-cols-2 md:grid-cols-4 gap-6' : 'space-y-4' }}" id="postsContainer">
            @forelse($posts as $post)
                <div onclick="openDetail({{ $post->id }})" class="post-item cursor-pointer bg-white p-5 rounded-[2.5rem] border border-slate-50 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-500 group relative {{ !$useGrid ? 'flex justify-between items-center' : '' }}">
                    
                    @if(!$useGrid)
                        <div class="flex items-center gap-6">
                            <div class="bg-slate-50 text-[#36B3C9] h-20 w-20 rounded-[1.8rem] flex items-center justify-center transition group-hover:bg-[#36B3C9] group-hover:text-white group-hover:rotate-6">
                                <i class="fas {{ str_contains($normalizedType, 'complaint') ? 'fa-exclamation-triangle' : (str_contains($normalizedType, 'request') ? 'fa-file-signature' : (str_contains($normalizedType, 'event') ? 'fa-calendar-check' : 'fa-bullhorn')) }} text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-black text-2xl text-slate-800 leading-tight mb-1 group-hover:text-[#36B3C9] transition">{{ $post->title }}</p>
                                
                                @if($isRequest)
                                    <span class="inline-block text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-md mb-2
                                        {{ ($post->status ?? 'pending') === 'approved' ? 'bg-green-100 text-green-600' : 
                                           (($post->status ?? 'pending') === 'completed' ? 'bg-blue-100 text-blue-600' : 
                                           (($post->status ?? 'pending') === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600')) }}">
                                        {{ $post->status ?? 'Pending' }}
                                    </span>
                                @endif

                                <div class="flex flex-wrap gap-1 mb-2">
                                    @php $decodedTags = is_string($post->tags) ? json_decode($post->tags, true) : $post->tags; @endphp
                                    @if(is_array($decodedTags))
                                        @foreach($decodedTags as $t)
                                            <span class="text-[8px] bg-slate-100 text-slate-400 px-2 py-0.5 rounded font-bold uppercase tracking-widest">{{ $t }}</span>
                                        @endforeach
                                    @endif
                                </div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                    <span class="text-[#36B3C9]">{{ $post->user->name ?? 'Neighbor' }}</span>
                                    <span class="h-1 w-1 bg-slate-200 rounded-full"></span>
                                    @if($isEvent && $post->event_date)
                                        <span class="text-orange-400">{{ \Carbon\Carbon::parse($post->event_date)->format('M d, Y') }}</span>
                                    @else
                                        {{ $post->created_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="aspect-square bg-slate-50 flex items-center justify-center overflow-hidden mb-5 rounded-[2rem] relative">
                            @php $imgs = is_array($post->image) ? $post->image : json_decode($post->image, true); @endphp
                            @if($imgs && count($imgs) > 0)
                                <img src="{{ asset('uploads/' . $imgs[0]) }}" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                                @if(count($imgs) > 1)
                                    <div class="absolute bottom-3 right-3 bg-white/90 backdrop-blur-md text-slate-800 text-[9px] px-2 py-1 rounded-lg font-black shadow-sm">+{{ count($imgs) - 1 }}</div>
                                @endif
                            @else
                                <i class="fas fa-camera text-slate-200 text-4xl group-hover:text-[#36B3C9] transition"></i>
                            @endif

                            @if($post->tags)
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1 max-w-[80%]">
                                    @php $displayTags = is_string($post->tags) ? json_decode($post->tags, true) : $post->tags; @endphp
                                    @if(is_array($displayTags))
                                        @foreach(array_slice($displayTags, 0, 2) as $t)
                                            <div class="bg-black/60 backdrop-blur-sm text-white text-[8px] font-bold px-2 py-1 rounded-lg uppercase tracking-wider">
                                                {{ $t }}
                                            </div>
                                        @endforeach
                                        @if(count($displayTags) > 2)
                                            <div class="bg-black/60 backdrop-blur-sm text-white text-[8px] font-bold px-2 py-1 rounded-lg">+{{ count($displayTags) - 2 }}</div>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="px-1">
                            <p class="font-black text-slate-800 text-lg leading-tight truncate">{{ $post->title }}</p>
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-[10px] font-bold text-slate-300">{{ $post->user->name ?? 'User' }}</p>
                                @if($isBuySell)
                                    <p class="text-sm font-black text-[#36B3C9]">
                                        @if($post->price) ₱{{ number_format($post->price, 0) }} @else <span class="text-slate-400">Free</span> @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($isAdmin || Auth::id() === $post->user_id)
                        <button onclick="event.stopPropagation(); triggerDelete({{ $post->id }})" class="absolute top-4 right-4 text-slate-200 hover:text-red-500 p-2 transition opacity-0 group-hover:opacity-100 bg-white/80 rounded-full hover:bg-white shadow-sm backdrop-blur-sm z-10">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-32 text-center">
                    <div class="bg-white inline-flex items-center justify-center w-24 h-24 rounded-[2.5rem] shadow-sm mb-6 text-slate-200">
                        <i class="fas fa-folder-open text-4xl"></i>
                    </div>
                    <p class="text-slate-800 font-black text-2xl uppercase tracking-tighter">No Records Found</p>
                </div>
            @endforelse
        </div>
    </div>

    <div id="addModal" class="hidden fixed inset-0 z-[100] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md transition-all">
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl overflow-y-auto max-h-[90vh] animate-pop relative">
            <button onclick="toggleModal('addModal')" class="absolute top-8 right-8 text-slate-300 hover:text-slate-800 transition"><i class="fas fa-times text-xl"></i></button>
            
            @if($isComplaint)
                <h2 class="text-3xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">File a Complaint</h2>
                <p class="text-slate-300 text-[10px] font-black uppercase tracking-widest mb-8">This report is strictly confidential to admins.</p>
                
                <form action="{{ route('post.store') }}" method="POST" id="postForm" enctype="multipart/form-data" class="space-y-5">
                    @csrf 
                    <input type="hidden" name="category" value="{{ $type }}">
                    
                    <input type="text" name="title" placeholder="Nature of Complaint (e.g. Noise Disturbance)" class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-black text-slate-800 placeholder:text-slate-300" required>
                    
                    <div class="bg-slate-50 p-5 rounded-[1.5rem]">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Date of Incident</label>
                        <input type="date" name="event_date" class="w-full bg-transparent border-none p-0 focus:ring-0 font-black text-slate-800" required>
                    </div>

                    @if(!empty($availableTags))
                        <div>
                            <label class="text-[10px] font-black text-slate-300 uppercase tracking-widest block mb-3 ml-2">Complaint Category</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($availableTags as $tag)
                                    <div class="relative">
                                        <input type="checkbox" name="tags[]" value="{{ $tag }}" id="tag_{{ $tag }}" class="hidden tag-checkbox">
                                        <label for="tag_{{ $tag }}" class="block text-center py-3 px-4 rounded-xl bg-slate-50 text-slate-500 text-xs font-bold cursor-pointer transition border border-transparent hover:bg-slate-100">
                                            {{ $tag }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <textarea name="description" placeholder="Provide full details: Who was involved? Where did it happen? What exactly occurred?" class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-800 placeholder:text-slate-300 min-h-[140px]" rows="4" required></textarea>

            @elseif($isRequest)
                <h2 id="requestModalTitle" class="text-3xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">Request a File</h2>
                <p class="text-slate-300 text-[10px] font-black uppercase tracking-widest mb-8">Admin will schedule your pickup date once approved.</p>
                <form action="{{ route('post.store') }}" method="POST" id="postForm" onsubmit="prepareRequestDescription()" enctype="multipart/form-data" class="space-y-5">
                    @csrf 
                    <input type="hidden" name="category" value="{{ $type }}">
                    <input type="hidden" name="title" id="requestDocType">
                    <input type="hidden" name="description" id="requestActualDesc">
                    
                    <input type="text" id="reqName" placeholder="Full Legal Name" class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-black text-slate-800 placeholder:text-slate-300" required>
                    <input type="text" id="reqAddress" placeholder="Complete Home Address" class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-black text-slate-800 placeholder:text-slate-300" required>
                    <textarea id="reqPurpose" placeholder="Reason / Purpose for requesting this document" class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-800 placeholder:text-slate-300 min-h-[120px]" rows="3" required></textarea>

            @else
                <h2 class="text-3xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">New Listing</h2>
                <p class="text-slate-300 text-[10px] font-black uppercase tracking-widest mb-8">Category: {{ str_replace(['_', '-'], ' ', $type) }}</p>
                
                <form action="{{ route('post.store') }}" method="POST" id="postForm" enctype="multipart/form-data" class="space-y-5">
                    @csrf 
                    <input type="hidden" name="category" value="{{ $type }}">
                    <input type="text" name="title" placeholder="Item Name / Title" class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-black text-slate-800 placeholder:text-slate-300" required>

                    @if(!empty($availableTags))
                        <div>
                            <label class="text-[10px] font-black text-slate-300 uppercase tracking-widest block mb-3 ml-2">Tags (Select Multiple)</label>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($availableTags as $tag)
                                    <div class="relative">
                                        <input type="checkbox" name="tags[]" value="{{ $tag }}" id="tag_{{ $tag }}" class="hidden tag-checkbox">
                                        <label for="tag_{{ $tag }}" class="block text-center py-3 px-4 rounded-xl bg-slate-50 text-slate-500 text-xs font-bold cursor-pointer transition border border-transparent hover:bg-slate-100">
                                            {{ $tag }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($isEvent)
                        <div class="bg-slate-50 p-5 rounded-[1.5rem]">
                            <label class="text-[10px] font-black text-slate-300 uppercase tracking-widest block mb-2">Event Date</label>
                            <input type="date" name="event_date" class="w-full bg-transparent border-none p-0 focus:ring-0 font-black text-slate-800" required>
                        </div>
                    @endif

                    @if($isBuySell) 
                        <div class="relative">
                            <span class="absolute left-5 top-5 text-slate-300 font-black">₱</span>
                            <input type="number" name="price" step="0.01" placeholder="Price (Leave blank for Free)" class="w-full p-5 pl-10 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-black text-slate-800 placeholder:text-slate-300">
                        </div>
                    @endif

                    <textarea name="description" placeholder="Description & Details..." class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-800 placeholder:text-slate-300 min-h-[140px]" rows="3"></textarea>
            @endif

                <div class="border-2 border-dashed border-slate-100 rounded-[2rem] p-8 text-center group hover:border-[#36B3C9] hover:bg-slate-50 transition cursor-pointer relative mt-4">
                    <input type="file" name="images[]" id="imagesInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" multiple accept="image/*">
                    <i class="fas fa-camera text-3xl text-slate-200 group-hover:text-[#36B3C9] mb-3 transition"></i>
                    <span class="block text-[10px] font-black text-slate-300 uppercase tracking-widest group-hover:text-slate-500">
                        @if($isComplaint) Attach Evidence Photos @else Add Photos @endif
                    </span>
                </div>
                
                <div id="imagePreviewContainer" class="grid grid-cols-4 gap-3"></div>
                <button type="submit" class="w-full bg-[#36B3C9] text-white font-black py-5 rounded-[1.8rem] shadow-xl shadow-cyan-100 mt-4 transition hover:brightness-110 active:scale-95 uppercase tracking-widest text-xs">Submit</button>
            </form>
        </div>
    </div>

    <div id="detailModal" class="hidden fixed inset-0 z-[110] bg-slate-900/80 flex items-center justify-center p-4 backdrop-blur-lg">
        <div class="bg-white text-slate-900 w-full max-w-3xl rounded-[4rem] overflow-hidden shadow-2xl relative animate-pop max-h-[92vh] overflow-y-auto">
            <button onclick="toggleModal('detailModal')" class="absolute top-6 right-6 z-[130] bg-white shadow-xl text-slate-800 p-3 rounded-2xl transition hover:bg-[#36B3C9] hover:text-white"><i class="fas fa-times"></i></button>

            <div id="detailImageSection" class="relative hidden bg-slate-50">
                <button id="prevBtn" onclick="moveGallery(-1)" class="absolute left-6 top-1/2 -translate-y-1/2 z-[120] bg-white/90 backdrop-blur-md p-4 rounded-2xl shadow-xl transition hover:scale-110"><i class="fas fa-chevron-left text-[#36B3C9]"></i></button>
                <button id="nextBtn" onclick="moveGallery(1)" class="absolute right-6 top-1/2 -translate-y-1/2 z-[120] bg-white/90 backdrop-blur-md p-4 rounded-2xl shadow-xl transition hover:scale-110"><i class="fas fa-chevron-right text-[#36B3C9]"></i></button>
                <div id="detImg" class="w-full h-[450px] flex overflow-x-hidden snap-x snap-mandatory"></div>
            </div>

            <div class="p-12">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-14 h-14 rounded-2xl bg-[#36B3C9]/10 flex items-center justify-center text-[#36B3C9] font-bold text-2xl"><i class="fas fa-user-circle"></i></div>
                    <div>
                        <p id="detUser" class="text-lg font-black text-slate-800 leading-none mb-1"></p>
                        <p id="detDate" class="text-[10px] font-black text-slate-300 uppercase tracking-widest mt-1"></p>
                    </div>
                </div>

                <div id="detTitleContainer" class="mb-6"></div>
                
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <div id="detPrice" class="w-full"></div>
                    <div id="detTagsContainer" class="flex gap-2"></div> 
                </div>

                <div id="detDesc" class="bg-slate-50 p-6 rounded-[2rem] text-slate-600 text-sm font-medium whitespace-pre-wrap leading-relaxed mb-8 border border-slate-100 hidden"></div>

                <div id="adminAppointmentControls"></div>

                <div class="grid grid-cols-2 gap-4 mt-8">
                    <div id="contactButtonContainer"></div>
                    <div id="detDeleteContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteConfirmModal" class="hidden fixed inset-0 z-[150] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white text-slate-900 w-full max-w-sm rounded-[3rem] p-10 shadow-2xl text-center animate-pop">
            <div class="bg-red-50 text-red-500 w-24 h-24 rounded-[2rem] flex items-center justify-center mx-auto mb-8 text-4xl"><i class="fas fa-trash-alt"></i></div>
            <h3 class="text-3xl font-black mb-2 tracking-tighter uppercase">Delete?</h3>
            <form id="deleteForm" method="POST" class="space-y-3">
                @csrf @method('DELETE')
                <button type="submit" class="w-full bg-red-500 text-white font-black py-5 rounded-2xl hover:bg-red-600 transition active:scale-95 shadow-lg shadow-red-100 uppercase tracking-widest text-[10px]">Delete Forever</button>
            </form>
            <button onclick="toggleModal('deleteConfirmModal')" class="w-full text-slate-300 font-black py-4 uppercase tracking-widest text-[10px] hover:text-slate-800 transition">Go Back</button>
        </div>
    </div>

    <script>
        let currentIdx = 0; let totalImgs = 0; let selectedFiles = [];

        function toggleModal(id) { 
            const modal = document.getElementById(id); modal.classList.toggle('hidden'); 
            if(id === 'addModal' && modal.classList.contains('hidden')) resetUploadForm();
        }

        function openRequestModal(docType) {
            document.getElementById('requestModalTitle').innerText = 'Request: ' + docType;
            document.getElementById('requestDocType').value = docType;
            toggleModal('addModal'); 
        }

        function prepareRequestDescription() {
            const name = document.getElementById('reqName').value;
            const addr = document.getElementById('reqAddress').value;
            const purpose = document.getElementById('reqPurpose').value;
            document.getElementById('requestActualDesc').value = `Requester Name: ${name}\nHome Address: ${addr}\n\nPurpose for Request:\n${purpose}`;
        }

        const fileInput = document.getElementById('imagesInput');
        if(fileInput) { fileInput.addEventListener('change', function(e) { selectedFiles = [...selectedFiles, ...Array.from(e.target.files)]; updateImagePreviews(); syncFileInput(); }); }
        function updateImagePreviews() { const c = document.getElementById('imagePreviewContainer'); c.innerHTML = ''; selectedFiles.forEach((f, i) => { const r = new FileReader(); r.onload = function(e) { const d = document.createElement('div'); d.className = "relative aspect-square rounded-2xl overflow-hidden border shadow-sm animate-pop group"; d.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover"><button type="button" onclick="removeImage(${i})" class="absolute top-1 right-1 bg-red-500 text-white w-6 h-6 rounded-full text-[10px] flex items-center justify-center shadow-lg transition"><i class="fas fa-times"></i></button>`; c.appendChild(d); }; r.readAsDataURL(f); }); }
        function removeImage(i) { selectedFiles.splice(i, 1); updateImagePreviews(); syncFileInput(); }
        function syncFileInput() { const dt = new DataTransfer(); selectedFiles.forEach(f => dt.items.add(f)); fileInput.files = dt.files; }
        function resetUploadForm() { selectedFiles = []; document.getElementById('imagePreviewContainer').innerHTML = ''; if(fileInput) fileInput.value = ''; document.getElementById('postForm').reset(); }

        function openDetail(id) {
            fetch(`/api/post/${id}`)
                .then(r => r.json())
                .then(d => {
                    // --- 1. THE FIX: BEAUTIFUL DESCRIPTION PARSING ---
                    const descEl = document.getElementById('detDesc');
                    if (d.description) {
                        if (d.category === 'requests' && d.description.includes('Requester Name:')) {
                            // Extract fields safely
                            const lines = d.description.split('\n');
                            let reqName = '', homeAddr = '', purpose = '';
                            let isPurpose = false;
                            lines.forEach(line => {
                                if (line.startsWith('Requester Name:')) reqName = line.replace('Requester Name:', '').trim();
                                else if (line.startsWith('Home Address:')) homeAddr = line.replace('Home Address:', '').trim();
                                else if (line.startsWith('Purpose for Request:')) isPurpose = true;
                                else if (isPurpose) purpose += line + '\n';
                            });
                            
                            // Render as Modern Cards
                            descEl.className = "mb-8";
                            descEl.innerHTML = `
                                <div class="bg-slate-50 border border-slate-100 rounded-[2rem] p-8">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                                        <i class="fas fa-file-invoice text-[#36B3C9] text-lg"></i> Document Request Details
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-50">
                                            <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Requester Name</span>
                                            <span class="text-sm font-bold text-slate-800">${reqName}</span>
                                        </div>
                                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-50">
                                            <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Home Address</span>
                                            <span class="text-sm font-bold text-slate-800">${homeAddr}</span>
                                        </div>
                                    </div>
                                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-50">
                                        <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Purpose for Request</span>
                                        <p class="text-sm font-medium text-slate-600 whitespace-pre-wrap mt-1">${purpose.trim()}</p>
                                    </div>
                                </div>
                            `;
                        } else {
                            // Standard description layout
                            descEl.className = "bg-slate-50 p-6 rounded-[2rem] text-slate-600 text-sm font-medium whitespace-pre-wrap leading-relaxed mb-8 border border-slate-100";
                            descEl.innerText = d.description;
                        }
                        descEl.classList.remove('hidden');
                    } else {
                        descEl.classList.add('hidden');
                    }
                    
                    document.getElementById('detUser').innerText = d.user ? d.user.name : 'Neighbor';
                    document.getElementById('detDate').innerText = new Date(d.created_at).toLocaleDateString();
                    
                    const titleContainer = document.getElementById('detTitleContainer');
                    const priceEl = document.getElementById('detPrice');
                    const isBuySell = (d.category.includes('buy') && d.category.includes('sell')); 
                    const adminControls = document.getElementById('adminAppointmentControls');
                    const userRole = "{{ Auth::user()->role }}";

                    // Tags Logic
                    const tagContainer = document.getElementById('detTagsContainer');
                    tagContainer.innerHTML = '';
                    if(d.tags) {
                        let tagsArray = [];
                        try { tagsArray = typeof d.tags === 'string' ? JSON.parse(d.tags) : d.tags; } catch(e) { tagsArray = [d.tags]; }
                        if(Array.isArray(tagsArray)) {
                            tagsArray.forEach(t => {
                                const badge = document.createElement('div');
                                badge.className = "bg-slate-100 text-slate-500 px-3 py-1 rounded-lg text-xs font-black uppercase tracking-widest";
                                badge.innerText = t;
                                tagContainer.appendChild(badge);
                            });
                        }
                    }

                    if (d.category === 'requests' || d.category === 'complaints') {
                        // --- 2. THE FIX: BEAUTIFUL SPACED-OUT BADGES ---
                        const statusColors = { 
                            'pending': 'bg-yellow-100 text-yellow-700 border-yellow-200', 
                            'approved': 'bg-green-100 text-green-700 border-green-200', 
                            'completed': 'bg-blue-100 text-blue-700 border-blue-200', 
                            'rejected': 'bg-red-100 text-red-700 border-red-200' 
                        };
                        const statusIcons = {
                            'pending': 'fa-hourglass-half',
                            'approved': 'fa-check-circle',
                            'completed': 'fa-flag-checkered',
                            'rejected': 'fa-times-circle'
                        };
                        
                        const sColor = statusColors[d.status || 'pending'] || 'bg-slate-100 text-slate-700 border-slate-200';
                        const sIcon = statusIcons[d.status || 'pending'] || 'fa-info-circle';
                        
                        titleContainer.innerHTML = `
                            <div class="flex flex-col items-start gap-4">
                                <h2 class="text-4xl md:text-5xl font-black tracking-tighter leading-none text-slate-800 uppercase">${d.title}</h2>
                                <span class="px-5 py-2 rounded-full text-[11px] font-black tracking-widest uppercase border shadow-sm flex items-center gap-2 ${sColor}">
                                    <i class="fas ${sIcon}"></i> STATUS: ${(d.status || 'pending')}
                                </span>
                            </div>
                        `;
                        
                        if(d.event_date) {
                            // Let's use a nice display "Wednesday, February 25, 2026 at 08:00 AM"
                            const eventDateObj = new Date(d.event_date.replace(' ', 'T'));
                            const eventDate = isNaN(eventDateObj) ? d.event_date : eventDateObj.toLocaleString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute:'2-digit' });
                            const dateLabel = d.status === 'rejected' ? 'Actioned On' : 'Scheduled For';
                            priceEl.innerHTML = `
                                <div class="bg-[#36B3C9]/10 border border-[#36B3C9]/20 p-5 rounded-[1.5rem] w-full mt-2">
                                    <span class="text-[10px] font-black text-[#36B3C9] uppercase tracking-widest block mb-1"><i class="fas fa-calendar-alt mr-1"></i> ${dateLabel}</span>
                                    <span class="text-slate-800 text-lg font-black tracking-tight">${eventDate}</span>
                                </div>
                            `;
                        } else {
                            priceEl.innerHTML = `
                                <div class="bg-yellow-50 border border-yellow-100 p-5 rounded-[1.5rem] w-full mt-2">
                                    <span class="text-xs font-black text-yellow-600 uppercase tracking-widest flex items-center gap-2">
                                        <i class="fas fa-clock text-lg"></i> Waiting for Admin to set schedule
                                    </span>
                                </div>
                            `;
                        }

                        if (userRole === 'admin') {
                            // --- 3. THE FIX: RELIABLE DATE PARSER FOR ADMIN INPUT ---
                            let rawDate = '';
                            if (d.event_date) {
                                // This precisely converts "YYYY-MM-DD HH:MM:SS" from database to "YYYY-MM-DDThh:mm" for the HTML picker
                                rawDate = d.event_date.replace(' ', 'T').substring(0, 16);
                            }
                            
                            adminControls.innerHTML = `
                                <form action="/post/${d.id}/status" method="POST" class="mt-6 p-8 bg-white shadow-xl shadow-slate-200/50 rounded-[2.5rem] border border-slate-100 animate-pop">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <h4 class="font-black uppercase text-sm mb-6 text-[#36B3C9] flex items-center gap-2"><i class="fas fa-calendar-check text-lg"></i> Admin: Manage Appointment</h4>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="bg-slate-50 p-4 rounded-[1.5rem]">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-3">Update Status</label>
                                            <select name="status" class="w-full p-0 border-none bg-transparent font-bold text-sm text-slate-700 focus:ring-0 cursor-pointer">
                                                <option value="pending" ${d.status === 'pending' ? 'selected' : ''}>Pending Review</option>
                                                <option value="approved" ${d.status === 'approved' ? 'selected' : ''}>Approved / Scheduled</option>
                                                <option value="completed" ${d.status === 'completed' ? 'selected' : ''}>Completed</option>
                                                <option value="rejected" ${d.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="bg-slate-50 p-4 rounded-[1.5rem]">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-3">Set Date & Time</label>
                                            <input type="datetime-local" name="event_date" value="${rawDate}" class="w-full p-0 border-none bg-transparent font-bold text-sm text-slate-700 focus:ring-0 cursor-pointer">
                                        </div>
                                    </div>
                                    <button type="submit" class="mt-6 w-full bg-slate-800 text-white font-black py-4 rounded-[1.5rem] uppercase tracking-widest text-[10px] shadow-lg hover:bg-slate-700 transition active:scale-95">Update Appointment</button>
                                </form>
                            `;
                        } else { adminControls.innerHTML = ''; }
                    } else {
                        // Regular Post Display (Buy/Sell, Events, Places)
                        adminControls.innerHTML = '';
                        titleContainer.innerHTML = `<h2 class="text-5xl font-black tracking-tighter leading-none text-slate-800 uppercase">${d.title}</h2>`;
                        
                        if(d.category === 'events' && d.event_date) {
                            priceEl.innerHTML = `<span class="text-xs font-black text-slate-300 uppercase tracking-widest block mb-1">Happening On</span><span class="text-3xl font-black text-orange-400">${new Date(d.event_date).toLocaleDateString()}</span>`;
                        } else if(isBuySell) {
                            priceEl.innerHTML = `<span class="text-3xl font-black text-[#36B3C9]">${d.price ? '₱' + parseFloat(d.price).toLocaleString() : 'Free / Offer'}</span>`;
                        } else { priceEl.innerHTML = ''; }
                    }

                    // Setup Contact & Delete buttons
                    const contactArea = document.getElementById('contactButtonContainer');
                    if(isBuySell || d.category === 'borrow' || d.category === 'services') { contactArea.innerHTML = `<a href="#" class="w-full bg-[#36B3C9] text-white font-black py-4 rounded-2xl shadow-xl shadow-cyan-100 flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition uppercase tracking-widest text-[10px]"><i class="fas fa-comment-alt"></i> Message</a>`; } else { contactArea.innerHTML = ''; }
                    const deleteArea = document.getElementById('detDeleteContainer');
                    if(userRole === 'admin' || {{ Auth::id() }} === d.user_id) { deleteArea.innerHTML = `<button onclick="triggerDelete(${d.id})" class="w-full bg-slate-50 text-red-500 font-black py-4 rounded-2xl hover:bg-red-50 transition flex items-center justify-center gap-2 uppercase tracking-widest text-[10px]"><i class="fas fa-trash-alt"></i> Remove</button>`; } else { deleteArea.innerHTML = ''; }

                    // Images
                    const imgSection = document.getElementById('detailImageSection'); const imgContainer = document.getElementById('detImg');
                    let images = []; try { images = Array.isArray(d.image) ? d.image : (d.image ? JSON.parse(d.image) : []); } catch(e) { images = []; }
                    if (images.length > 0) { imgSection.classList.remove('hidden'); imgContainer.innerHTML = images.map(img => `<div class="w-full h-full flex-shrink-0 snap-center flex items-center justify-center bg-black"><img src="/uploads/${img}" class="max-w-full max-h-full object-contain"></div>`).join(''); totalImgs = images.length; currentIdx = 0; if(totalImgs <= 1) { document.getElementById('prevBtn').classList.add('hidden'); document.getElementById('nextBtn').classList.add('hidden'); } else { document.getElementById('prevBtn').classList.remove('hidden'); document.getElementById('nextBtn').classList.remove('hidden'); } } else { imgSection.classList.add('hidden'); }
                    
                    if (document.getElementById('detailModal').classList.contains('hidden')) toggleModal('detailModal');
                });
        }
        
        function triggerDelete(id) { document.getElementById('deleteForm').action = `/post/${id}`; if(!document.getElementById('detailModal').classList.contains('hidden')) toggleModal('detailModal'); toggleModal('deleteConfirmModal'); }
        function moveGallery(dir) { const c = document.getElementById('detImg'); currentIdx += dir; if (currentIdx < 0) currentIdx = totalImgs - 1; if (currentIdx >= totalImgs) currentIdx = 0; c.scrollTo({ left: c.clientWidth * currentIdx, behavior: 'smooth' }); }
        
        // --- CALENDAR INITIALIZATION ---
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

        // --- URL PARAMETER CHECKER TO AUTO-OPEN POSTS ---
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const postIdToOpen = urlParams.get('post');
            
            if (postIdToOpen) {
                openDetail(postIdToOpen);
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

    </script>
</body>
</html>