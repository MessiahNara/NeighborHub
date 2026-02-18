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
    </style>
</head>

@php
    $normalizedType = str_replace('_', '-', $type); 
    
    // Category Logic
    $isBuySell = (str_contains($normalizedType, 'buy') && str_contains($normalizedType, 'sell'));
    $isBorrow  = ($normalizedType === 'borrow');
    $isEvent   = in_array($normalizedType, ['event', 'events']);
    $useGrid   = ($isBuySell || $isBorrow || in_array($normalizedType, ['service', 'places']));
    
    // --- 1. DEFINE TAGS HERE ---
    $availableTags = [];
    if($isBuySell) {
        $availableTags = ['Electronics', 'Furniture', 'Clothing', 'Vehicles', 'Books', 'Tools', 'Appliances', 'Other'];
    } elseif($isBorrow) {
        $availableTags = ['Tools', 'Garden', 'Kitchen', 'Party Supplies', 'Books', 'Camping', 'Tech', 'Other'];
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
            
            <div class="flex-1 relative flex items-center">
                <i class="fas fa-search absolute left-5 text-slate-300 z-10"></i>
                <input type="text" id="searchInput" placeholder="Search..." 
                       class="w-full bg-slate-50 border-none rounded-2xl py-3.5 pl-14 pr-10 focus:ring-2 focus:ring-[#36B3C9]/20 transition font-bold text-sm placeholder:text-slate-300 shadow-sm">
                <button id="clearSearch" class="hidden absolute right-4 text-slate-300 hover:text-slate-500"><i class="fas fa-times-circle"></i></button>
            </div>
            
            <button onclick="toggleModal('addModal')" class="bg-[#36B3C9] text-white h-12 px-6 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-cyan-100 transition hover:brightness-110 active:scale-95 flex items-center gap-2">
                <i class="fas fa-plus"></i> <span class="hidden md:inline">Post Item</span>
            </button>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6 pb-24">
        <div class="relative z-10 flex justify-between items-end mb-10">
            <div>
                <h1 class="text-5xl font-black uppercase tracking-tighter text-slate-800 leading-none">{{ str_replace(['_', '-'], ' ', $type) }}</h1>
                <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-2 ml-1">{{ count($posts) }} Active Posts</p>
            </div>
        </div>

        @if($isEvent)
            <div class="relative z-10 bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden mb-12 p-3">
                <div id="calendar"></div>
            </div>
        @endif

        <div class="relative z-10 {{ $useGrid ? 'grid grid-cols-2 md:grid-cols-4 gap-6' : 'space-y-4' }}" id="postsContainer">
            @forelse($posts as $post)
                <div onclick="openDetail({{ $post->id }})" data-title="{{ strtolower($post->title) }}" 
                     class="post-item cursor-pointer bg-white p-5 rounded-[2.5rem] border border-slate-50 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all duration-500 group relative {{ !$useGrid ? 'flex justify-between items-center' : '' }}">
                    
                    @if(!$useGrid)
                        <div class="flex items-center gap-6">
                            <div class="bg-slate-50 text-[#36B3C9] h-20 w-20 rounded-[1.8rem] flex items-center justify-center transition group-hover:bg-[#36B3C9] group-hover:text-white group-hover:rotate-6">
                                <i class="fas {{ str_contains($normalizedType, 'complaint') ? 'fa-exclamation-triangle' : (str_contains($normalizedType, 'request') ? 'fa-file-signature' : (str_contains($normalizedType, 'event') ? 'fa-calendar-check' : 'fa-bullhorn')) }} text-2xl"></i>
                            </div>
                            <div>
                                <p class="font-black text-2xl text-slate-800 leading-tight mb-1 group-hover:text-[#36B3C9] transition">{{ $post->title }}</p>
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
                                    <div class="absolute bottom-3 right-3 bg-white/90 backdrop-blur-md text-slate-800 text-[9px] px-2 py-1 rounded-lg font-black shadow-sm">
                                        +{{ count($imgs) - 1 }}
                                    </div>
                                @endif
                            @else
                                <i class="fas fa-camera text-slate-200 text-4xl group-hover:text-[#36B3C9] transition"></i>
                            @endif

                            @if($post->tags)
                                <div class="absolute top-3 left-3 bg-black/60 backdrop-blur-sm text-white text-[9px] font-bold px-2 py-1 rounded-lg uppercase tracking-wider">
                                    {{ $post->tags }}
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

                    @if(Auth::user()->role === 'admin' || Auth::id() === $post->user_id)
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
                    <p class="text-slate-800 font-black text-2xl uppercase tracking-tighter">No Posts</p>
                    <p class="text-slate-400 text-sm font-bold">Be the first to list an item!</p>
                </div>
            @endforelse
        </div>
        <div id="noResults" class="hidden text-center py-20 opacity-40 italic font-bold">No results found.</div>
    </div>

    <div id="addModal" class="hidden fixed inset-0 z-[100] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md transition-all">
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl overflow-y-auto max-h-[90vh] animate-pop relative">
            <button onclick="toggleModal('addModal')" class="absolute top-8 right-8 text-slate-300 hover:text-slate-800 transition"><i class="fas fa-times text-xl"></i></button>
            <h2 class="text-3xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">New Listing</h2>
            <p class="text-slate-300 text-[10px] font-black uppercase tracking-widest mb-8">Category: {{ str_replace(['_', '-'], ' ', $type) }}</p>
            
            <form action="{{ route('post.store') }}" method="POST" id="postForm" enctype="multipart/form-data" class="space-y-5">
                @csrf 
                <input type="hidden" name="category" value="{{ $type }}">
                <input type="text" name="title" placeholder="Item Name / Title" class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-black text-slate-800 placeholder:text-slate-300" required>

                @if(!empty($availableTags))
                    <div class="relative">
                        <select name="tags" class="w-full p-5 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-800 appearance-none cursor-pointer">
                            <option value="" disabled selected>Select a Category Tag</option>
                            @foreach($availableTags as $tag)
                                <option value="{{ $tag }}">{{ $tag }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-5 flex items-center px-2 text-slate-400">
                            <i class="fas fa-chevron-down text-xs"></i>
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
                
                <div class="border-2 border-dashed border-slate-100 rounded-[2rem] p-8 text-center group hover:border-[#36B3C9] hover:bg-slate-50 transition cursor-pointer relative">
                    <input type="file" name="images[]" id="imagesInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" multiple accept="image/*">
                    <i class="fas fa-camera text-3xl text-slate-200 group-hover:text-[#36B3C9] mb-3 transition"></i>
                    <span class="block text-[10px] font-black text-slate-300 uppercase tracking-widest group-hover:text-slate-500">Add Photos</span>
                </div>
                
                <div id="imagePreviewContainer" class="grid grid-cols-4 gap-3"></div>
                <button type="submit" class="w-full bg-[#36B3C9] text-white font-black py-5 rounded-[1.8rem] shadow-xl shadow-cyan-100 mt-4 transition hover:brightness-110 active:scale-95 uppercase tracking-widest text-xs">Publish</button>
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
                    <div class="w-14 h-14 rounded-2xl bg-[#36B3C9]/10 flex items-center justify-center text-[#36B3C9] font-bold text-2xl">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div>
                        <p id="detUser" class="text-lg font-black text-slate-800 leading-none mb-1"></p>
                        <p id="detDate" class="text-[10px] font-black text-slate-300 uppercase tracking-widest"></p>
                    </div>
                </div>

                <h2 id="detTitle" class="text-5xl font-black tracking-tighter leading-none text-slate-800 mb-4 uppercase"></h2>
                
                <div class="flex items-center gap-3 mb-6">
                    <div id="detPrice" class="text-3xl font-black text-[#36B3C9]"></div>
                    <div id="detTag" class="hidden bg-slate-100 text-slate-500 px-3 py-1 rounded-lg text-xs font-black uppercase tracking-widest"></div>
                </div>

                <p id="detDesc" class="text-slate-500 text-lg leading-relaxed mb-12 whitespace-pre-wrap font-medium"></p>

                <div class="grid grid-cols-2 gap-4">
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
            <p class="text-slate-400 font-bold mb-10 leading-snug">This cannot be undone.</p>
            <form id="deleteForm" method="POST" class="space-y-3">
                @csrf @method('DELETE')
                <button type="submit" class="w-full bg-red-500 text-white font-black py-5 rounded-2xl hover:bg-red-600 transition active:scale-95 shadow-lg shadow-red-100 uppercase tracking-widest text-[10px]">Delete Forever</button>
            </form>
            <button onclick="toggleModal('deleteConfirmModal')" class="w-full text-slate-300 font-black py-4 uppercase tracking-widest text-[10px] hover:text-slate-800 transition">Go Back</button>
        </div>
    </div>

    <script>
        let currentIdx = 0;
        let totalImgs = 0;
        let selectedFiles = [];

        function toggleModal(id) { 
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden'); 
            if(id === 'addModal' && modal.classList.contains('hidden')) resetUploadForm();
        }

        // --- SEARCH ---
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');
        const noResults = document.getElementById('noResults');

        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            const posts = document.querySelectorAll('.post-item');
            let hasVisiblePosts = false;
            clearBtn.classList.toggle('hidden', term === '');
            posts.forEach(post => {
                const title = post.getAttribute('data-title');
                if (title.includes(term)) { post.classList.remove('hidden'); hasVisiblePosts = true; } 
                else { post.classList.add('hidden'); }
            });
            noResults.classList.toggle('hidden', hasVisiblePosts);
        });
        clearBtn.addEventListener('click', () => { searchInput.value = ''; searchInput.dispatchEvent(new Event('input')); });

        // --- IMAGE UPLOAD ---
        const fileInput = document.getElementById('imagesInput');
        if(fileInput) {
            fileInput.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                selectedFiles = [...selectedFiles, ...files];
                updateImagePreviews();
                syncFileInput();
            });
        }
        function updateImagePreviews() {
            const container = document.getElementById('imagePreviewContainer');
            container.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = "relative aspect-square rounded-2xl overflow-hidden border shadow-sm animate-pop group";
                    div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover"><button type="button" onclick="removeImage(${index})" class="absolute top-1 right-1 bg-red-500 text-white w-6 h-6 rounded-full text-[10px] flex items-center justify-center shadow-lg transition"><i class="fas fa-times"></i></button>`;
                    container.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }
        function removeImage(index) { selectedFiles.splice(index, 1); updateImagePreviews(); syncFileInput(); }
        function syncFileInput() { const dt = new DataTransfer(); selectedFiles.forEach(file => dt.items.add(file)); fileInput.files = dt.files; }
        function resetUploadForm() { selectedFiles = []; document.getElementById('imagePreviewContainer').innerHTML = ''; if(fileInput) fileInput.value = ''; document.getElementById('postForm').reset(); }

        // --- DETAIL & GALLERY ---
        function openDetail(id) {
            fetch(`/api/post/${id}`)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('detTitle').innerText = d.title;
                    document.getElementById('detDesc').innerText = d.description || 'No description provided.';
                    const userName = d.user ? d.user.name : 'Neighbor';
                    document.getElementById('detUser').innerText = userName;
                    document.getElementById('detDate').innerText = new Date(d.created_at).toLocaleDateString();
                    
                    const priceEl = document.getElementById('detPrice');
                    const isBuySell = (d.category.includes('buy') && d.category.includes('sell')); 

                    if(d.category === 'event' && d.event_date) {
                        const eventDate = new Date(d.event_date).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                        priceEl.innerHTML = `<span class="text-xs font-black text-slate-300 uppercase tracking-widest block mb-1">Happening On</span>${eventDate}`;
                    } else if(isBuySell) {
                        priceEl.innerText = d.price ? '₱' + parseFloat(d.price).toLocaleString() : 'Free / Offer';
                    } else { priceEl.innerText = ''; }

                    // --- SHOW TAG IN MODAL ---
                    const tagEl = document.getElementById('detTag');
                    if(d.tags) {
                        tagEl.innerText = d.tags;
                        tagEl.classList.remove('hidden');
                    } else {
                        tagEl.classList.add('hidden');
                    }

                    const contactArea = document.getElementById('contactButtonContainer');
                    if(isBuySell || d.category === 'borrow' || d.category === 'service') {
                        contactArea.innerHTML = `<a href="#" class="w-full bg-[#36B3C9] text-white font-black py-5 rounded-2xl shadow-xl shadow-cyan-100 flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition uppercase tracking-widest text-[10px]"><i class="fas fa-comment-alt"></i> Message</a>`;
                    } else { contactArea.innerHTML = ''; }

                    const deleteArea = document.getElementById('detDeleteContainer');
                    const currentUserId = {{ Auth::id() }};
                    const userRole = "{{ Auth::user()->role }}";
                    if(userRole === 'admin' || currentUserId === d.user_id) {
                        deleteArea.innerHTML = `<button onclick="triggerDelete(${d.id})" class="w-full bg-slate-50 text-red-500 font-black py-4 rounded-2xl hover:bg-red-50 transition flex items-center justify-center gap-2 uppercase tracking-widest text-[10px]"><i class="fas fa-trash-alt"></i> Remove Post</button>`;
                    } else { deleteArea.innerHTML = ''; }

                    // Images
                    const imgSection = document.getElementById('detailImageSection');
                    const imgContainer = document.getElementById('detImg');
                    let images = [];
                    try { images = Array.isArray(d.image) ? d.image : (d.image ? JSON.parse(d.image) : []); } catch(e) { images = []; }
                    
                    if (images.length > 0) {
                        imgSection.classList.remove('hidden');
                        imgContainer.innerHTML = images.map(img => `<div class="w-full h-full flex-shrink-0 snap-center flex items-center justify-center bg-black"><img src="/uploads/${img}" class="max-w-full max-h-full object-contain"></div>`).join('');
                        totalImgs = images.length;
                        currentIdx = 0;
                        if(totalImgs <= 1) { document.getElementById('prevBtn').classList.add('hidden'); document.getElementById('nextBtn').classList.add('hidden'); }
                        else { document.getElementById('prevBtn').classList.remove('hidden'); document.getElementById('nextBtn').classList.remove('hidden'); }
                    } else { imgSection.classList.add('hidden'); }

                    toggleModal('detailModal');
                });
        }
        function triggerDelete(id) {
            const form = document.getElementById('deleteForm');
            form.action = `/post/${id}`;
            if(!document.getElementById('detailModal').classList.contains('hidden')) toggleModal('detailModal');
            toggleModal('deleteConfirmModal');
        }
        function moveGallery(dir) {
            const container = document.getElementById('detImg');
            currentIdx += dir;
            if (currentIdx < 0) currentIdx = totalImgs - 1;
            if (currentIdx >= totalImgs) currentIdx = 0;
            container.scrollTo({ left: container.clientWidth * currentIdx, behavior: 'smooth' });
        }
        
        @if($isEvent)
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            // DEBUG: Check console to see if events are being passed correctly
            console.log("Calendar Events Loaded:", @json($calendarEvents));

            if(calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
                    height: 550,
                    events: @json($calendarEvents),
                    eventClick: function(info) { 
                        // Trigger the detail popup when an event is clicked
                        openDetail(info.event.id); 
                    }
                });
                calendar.render();
            }
        });
        @endif
    </script>
</body>
</html>