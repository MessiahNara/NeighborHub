<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <title>NeighborHub | {{ str_replace('_', ' ', $type) }}</title>
    <style> 
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        #detImg { scroll-behavior: smooth; -webkit-overflow-scrolling: touch; }
        
        /* Calendar Customization */
        .fc { background: white; border-radius: 2rem; padding: 2rem; border: none !important; }
        .fc-toolbar-title { font-weight: 900 !important; text-transform: uppercase; letter-spacing: -0.05em; color: #1e293b; font-size: 1.5rem !important; }
        .fc-button-primary { background-color: #36B3C9 !important; border: none !important; border-radius: 12px !important; font-weight: bold !important; padding: 0.5rem 1rem !important; }
        .fc-button-primary:hover { filter: brightness(0.9); }
        .fc-daygrid-event { background-color: #36B3C9; border: none; border-radius: 6px; padding: 2px 5px; cursor: pointer; }
        .fc-day-today { background-color: #f1fbfd !important; border-radius: 1rem; }

        .animate-pop { animation: pop 0.3s cubic-bezier(0.26, 0.53, 0.74, 1.48); }
        @keyframes pop { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>

@php
    $useGrid = in_array($type, ['buy_sell', 'borrow', 'service']);
    $hasPrice = ($type === 'buy_sell'); 
    $bgClass = 'bg-slate-50 text-slate-900';
    
    // Prepare events for calendar if needed
    $calendarEvents = [];
    if($type == 'event') {
        foreach($posts as $p) {
            $calendarEvents[] = [
                'id' => $p->id,
                'title' => $p->title,
                'start' => $p->event_date,
                'className' => 'font-bold text-xs'
            ];
        }
    }
@endphp

<body class="{{ $bgClass }} font-sans antialiased min-h-screen">

    <nav class="sticky top-0 z-50 bg-white border-b p-4 shadow-sm">
        <div class="max-w-5xl mx-auto flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="h-10 w-10 bg-slate-100 rounded-full flex items-center justify-center transition active:scale-90 hover:bg-slate-200">
                <i class="fas fa-arrow-left text-slate-600"></i>
            </a>
            
            <div class="flex-1 relative flex items-center">
                <i class="fas fa-search absolute left-4 text-slate-400 z-10"></i>
                <input type="text" id="searchInput" placeholder="Search {{ str_replace('_', ' ', $type) }}..." 
                       class="w-full bg-slate-100 text-slate-900 rounded-full py-3 pl-12 pr-10 focus:outline-none focus:ring-2 focus:ring-[#36B3C9] transition font-bold text-sm">
                <button id="clearSearch" class="hidden absolute right-4 text-slate-400 hover:text-slate-600"><i class="fas fa-times-circle"></i></button>
            </div>
            
            <button onclick="toggleModal('addModal')" class="bg-[#36B3C9] text-white h-10 px-6 rounded-xl font-bold transition hover:shadow-lg hover:-translate-y-0.5 active:scale-95 flex items-center gap-2">
                <i class="fas fa-plus"></i> <span class="hidden md:inline">Post</span>
            </button>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-6 pb-24">
        <div class="flex justify-between items-end mb-8">
            <h1 class="text-4xl font-black uppercase tracking-tighter text-slate-800">{{ str_replace('_', ' ', $type) }}</h1>
            <span class="text-slate-400 font-bold text-sm">{{ count($posts) }} Posts</span>
        </div>

        @if($type == 'event')
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden mb-12 p-2">
                <div id="calendar"></div>
            </div>
        @endif

        <div class="{{ $useGrid ? 'grid grid-cols-2 md:grid-cols-4 gap-4' : 'space-y-4' }} transition-all" id="postsContainer">
            @forelse($posts as $post)
                <div onclick="openDetail({{ $post->id }})" data-title="{{ strtolower($post->title) }}" 
                     class="post-item cursor-pointer bg-white p-6 rounded-[2rem] border shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group relative {{ !$useGrid ? 'flex justify-between items-center' : '' }}">
                    
                    @if(!$useGrid)
                        <div class="flex items-center gap-6">
                            <div class="bg-[#36B3C9]/10 text-[#36B3C9] h-16 w-16 rounded-2xl flex items-center justify-center transition group-hover:scale-110 group-hover:rotate-3">
                                @if($type == 'complaint') <i class="fas fa-exclamation-circle text-2xl"></i>
                                @elseif($type == 'request') <i class="fas fa-file-invoice text-2xl"></i>
                                @elseif($type == 'announcement') <i class="fas fa-bullhorn text-2xl"></i>
                                @else <i class="fas fa-calendar-day text-2xl"></i> @endif
                            </div>
                            <div>
                                <p class="font-black text-xl text-slate-800 leading-tight mb-1 post-title-text group-hover:text-[#36B3C9] transition">{{ $post->title }}</p>
                                
                                <p class="text-xs font-bold text-slate-500 flex items-center gap-2">
                                    <i class="fas fa-user-circle text-slate-300"></i> {{ $post->user->name ?? 'Neighbor' }}
                                    <span class="text-slate-300">•</span>
                                    {{ $type == 'event' ? \Carbon\Carbon::parse($post->event_date)->format('M d, Y') : $post->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="aspect-square bg-slate-100 flex items-center justify-center overflow-hidden mb-4 rounded-2xl relative">
                            @php $imgs = is_array($post->image) ? $post->image : json_decode($post->image, true); @endphp
                            @if($imgs && count($imgs) > 0)
                                <img src="{{ asset('uploads/' . $imgs[0]) }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                                @if(count($imgs) > 1)
                                    <div class="absolute bottom-2 right-2 bg-black/60 backdrop-blur-md text-white text-[10px] px-2 py-1 rounded-lg font-bold shadow-sm">
                                        <i class="fas fa-images mr-1"></i> {{ count($imgs) }}
                                    </div>
                                @endif
                            @else
                                <i class="fas fa-image text-slate-300 text-4xl"></i>
                            @endif
                        </div>
                        <div class="space-y-1">
                            <p class="font-bold text-slate-800 line-clamp-1 post-title-text leading-tight">{{ $post->title }}</p>
                            
                            <p class="text-[10px] font-bold text-slate-400 uppercase truncate">
                                by {{ $post->user->name ?? 'Unknown' }}
                            </p>

                            {{-- FIXED: Only show price logic if it is the Buy & Sell category --}}
                            @if($hasPrice)
                                @if($post->price)
                                    <p class="text-sm font-black text-[#36B3C9]">₱{{ number_format($post->price, 2) }}</p>
                                @else
                                    <p class="text-xs font-bold text-slate-400 uppercase">Free / Negotiable</p>
                                @endif
                            @endif
                        </div>
                    @endif

                    @if(Auth::user()->role === 'admin' || Auth::id() === $post->user_id)
                        <button onclick="event.stopPropagation(); triggerDelete({{ $post->id }})" class="absolute top-4 right-4 text-slate-300 hover:text-red-500 p-2 transition bg-white/80 rounded-full hover:bg-white shadow-sm backdrop-blur-sm z-10">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @endif
                </div>
            @empty
                <div class="col-span-full py-20 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-100 mb-4 text-slate-300">
                        <i class="fas fa-box-open text-3xl"></i>
                    </div>
                    <p class="text-slate-400 font-bold text-lg">No posts yet.</p>
                    <p class="text-slate-300 text-sm">Be the first to post in {{ str_replace('_', ' ', $type) }}!</p>
                </div>
            @endforelse
        </div>
        <div id="noResults" class="hidden text-center py-20 opacity-40 italic font-bold">No posts match your search.</div>
    </div>

    <div id="addModal" class="hidden fixed inset-0 z-[100] bg-black/80 flex items-center justify-center p-4 backdrop-blur-md transition-opacity">
        <div class="bg-white text-slate-900 w-full max-w-lg rounded-[2rem] p-8 shadow-2xl overflow-y-auto max-h-[90vh] animate-pop relative">
            <button onclick="toggleModal('addModal')" class="absolute top-6 right-6 text-slate-300 hover:text-slate-600 transition"><i class="fas fa-times text-xl"></i></button>
            <h2 class="text-2xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">New Post</h2>
            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-6">Category: {{ str_replace('_', ' ', $type) }}</p>
            
            <form action="{{ route('post.store') }}" method="POST" id="postForm" enctype="multipart/form-data" class="space-y-4">
                @csrf 
                <input type="hidden" name="category" value="{{ $type }}">
                <div>
                    <input type="text" name="title" placeholder="What is the title?" class="w-full p-4 bg-slate-50 rounded-2xl border-none focus:ring-2 focus:ring-[#36B3C9] font-bold text-slate-700 placeholder:text-slate-400 transition" required>
                </div>
                @if($type == 'event')
                    <div>
                        <label class="ml-2 text-xs font-bold text-slate-400 uppercase">Event Date</label>
                        <input type="date" name="event_date" class="w-full p-4 bg-slate-50 rounded-2xl border-none focus:ring-2 focus:ring-[#36B3C9] font-bold text-slate-700" required>
                    </div>
                @endif
                @if($hasPrice) 
                    <div>
                        <div class="relative">
                            <span class="absolute left-4 top-4 text-slate-400 font-bold">₱</span>
                            <input type="number" name="price" step="0.01" placeholder="0.00" class="w-full p-4 pl-8 bg-slate-50 rounded-2xl border-none focus:ring-2 focus:ring-[#36B3C9] font-bold text-slate-700">
                        </div>
                    </div>
                @endif
                <textarea name="description" placeholder="Describe the details..." class="w-full p-4 bg-slate-50 rounded-2xl border-none focus:ring-2 focus:ring-[#36B3C9] font-medium text-slate-700 min-h-[120px]" rows="3"></textarea>
                <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center group hover:border-[#36B3C9] hover:bg-[#36B3C9]/5 transition cursor-pointer relative">
                    <input type="file" name="images[]" id="imagesInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" multiple accept="image/*">
                    <div class="pointer-events-none">
                        <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 group-hover:text-[#36B3C9] mb-2 transition"></i>
                        <span class="block text-[10px] font-black text-slate-400 uppercase group-hover:text-[#36B3C9]">Click to Upload Photos</span>
                    </div>
                </div>
                <div id="imagePreviewContainer" class="grid grid-cols-3 gap-3"></div>
                <button type="submit" class="w-full bg-[#36B3C9] text-white font-bold py-4 rounded-2xl shadow-lg shadow-[#36B3C9]/30 mt-2 transition hover:brightness-110 active:scale-95 uppercase tracking-widest text-sm">Publish Post</button>
            </form>
        </div>
    </div>

    <div id="detailModal" class="hidden fixed inset-0 z-[110] bg-black/90 flex items-center justify-center p-4 backdrop-blur-md">
        <div class="bg-white text-slate-900 w-full max-w-2xl rounded-[2.5rem] overflow-hidden shadow-2xl relative animate-pop max-h-[90vh] overflow-y-auto">
            <button onclick="toggleModal('detailModal')" class="absolute top-4 right-4 z-[130] bg-black/20 hover:bg-black/50 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center backdrop-blur-md transition"><i class="fas fa-times"></i></button>

            <div id="detailImageSection" class="relative hidden bg-slate-900">
                <button id="prevBtn" onclick="moveGallery(-1)" class="absolute left-4 top-1/2 -translate-y-1/2 z-[120] bg-white/20 hover:bg-white text-white hover:text-black p-3 h-12 w-12 rounded-full backdrop-blur-md flex items-center justify-center transition"><i class="fas fa-chevron-left"></i></button>
                <button id="nextBtn" onclick="moveGallery(1)" class="absolute right-4 top-1/2 -translate-y-1/2 z-[120] bg-white/20 hover:bg-white text-white hover:text-black p-3 h-12 w-12 rounded-full backdrop-blur-md flex items-center justify-center transition"><i class="fas fa-chevron-right"></i></button>
                <div id="detImg" class="w-full h-80 flex overflow-x-hidden snap-x snap-mandatory"></div>
            </div>

            <div class="p-8 md:p-10">
                <div class="mb-6 border-b pb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-bold text-xl">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <p id="detUser" class="text-sm font-black text-slate-800"></p>
                            <p id="detDate" class="text-xs font-bold text-slate-400 uppercase"></p>
                        </div>
                    </div>
                    
                    <h2 id="detTitle" class="text-3xl md:text-4xl font-black tracking-tighter leading-tight text-slate-800"></h2>
                    <div id="detPrice" class="text-2xl font-bold text-[#36B3C9] mt-2"></div>
                </div>

                <p id="detDesc" class="text-slate-600 text-lg leading-relaxed mb-8 whitespace-pre-wrap"></p>

                <div class="flex flex-col gap-3">
                    <div id="contactButtonContainer"></div>
                    <div id="detDeleteContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteConfirmModal" class="hidden fixed inset-0 z-[150] bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white text-slate-900 w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl text-center animate-pop">
            <div class="bg-red-50 text-red-500 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl"><i class="fas fa-trash-alt"></i></div>
            <h3 class="text-2xl font-black mb-2 tracking-tighter">Delete Post?</h3>
            <p class="text-slate-500 mb-6 leading-tight">This action cannot be undone. Are you sure you want to remove this?</p>
            <form id="deleteForm" method="POST">
                @csrf @method('DELETE')
                <button type="submit" class="w-full bg-red-500 text-white font-bold py-4 rounded-2xl hover:bg-red-600 transition mb-3 active:scale-95 shadow-lg shadow-red-500/30">Yes, Delete It</button>
            </form>
            <button onclick="toggleModal('deleteConfirmModal')" class="w-full bg-slate-100 text-slate-500 font-bold py-4 rounded-2xl hover:bg-slate-200 transition">Cancel</button>
        </div>
    </div>

    <script>
        // --- GLOBAL VARIABLES ---
        let currentIdx = 0;
        let totalImgs = 0;
        let selectedFiles = [];

        // --- CALENDAR INIT (ONLY FOR EVENTS) ---
        @if($type == 'event')
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

        // --- MODAL LOGIC ---
        function toggleModal(id) { 
            const modal = document.getElementById(id);
            modal.classList.toggle('hidden'); 
            if(id === 'addModal' && modal.classList.contains('hidden')) resetUploadForm();
        }

        // --- SEARCH LOGIC ---
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

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        });

        // --- IMAGE UPLOAD LOGIC ---
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
                    div.className = "relative aspect-square rounded-xl overflow-hidden border shadow-sm animate-pop group";
                    div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover"><button type="button" onclick="removeImage(${index})" class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white w-6 h-6 rounded-full text-[10px] flex items-center justify-center shadow-lg transition"><i class="fas fa-times"></i></button>`;
                    container.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        function removeImage(index) { selectedFiles.splice(index, 1); updateImagePreviews(); syncFileInput(); }
        function syncFileInput() { const dt = new DataTransfer(); selectedFiles.forEach(file => dt.items.add(file)); fileInput.files = dt.files; }
        function resetUploadForm() { selectedFiles = []; document.getElementById('imagePreviewContainer').innerHTML = ''; if(fileInput) fileInput.value = ''; document.getElementById('postForm').reset(); }

        // --- DETAIL POPUP LOGIC ---
        function openDetail(id) {
            fetch(`/api/post/${id}`)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('detTitle').innerText = d.title;
                    document.getElementById('detDesc').innerText = d.description || 'No description provided.';
                    
                    // --- SHOW USER NAME IN POPUP ---
                    const userName = d.user ? d.user.name : 'Neighbor';
                    document.getElementById('detUser').innerText = userName;

                    // Date & Price
                    document.getElementById('detDate').innerText = new Date(d.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
                    const priceEl = document.getElementById('detPrice');
                    if(d.category === 'event' && d.event_date) {
                        const eventDate = new Date(d.event_date).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                        priceEl.innerHTML = `<span class="text-sm font-bold text-slate-400 uppercase tracking-widest block text-xs mb-1">Happening On</span>${eventDate}`;
                    } else if(d.category === 'buy_sell' && d.price) {
                        priceEl.innerText = '₱' + parseFloat(d.price).toLocaleString();
                    } else {
                        priceEl.innerText = '';
                    }
                    
                    // Contact Button - Dynamic Name
                    const contactArea = document.getElementById('contactButtonContainer');
                    if(['buy_sell', 'borrow', 'service'].includes(d.category)) {
                        contactArea.innerHTML = `<a href="#" class="w-full bg-[#36B3C9] text-white font-bold py-4 rounded-2xl shadow-lg shadow-[#36B3C9]/30 flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition"><i class="fas fa-comment-alt"></i> <span>Message ${userName.split(' ')[0]}</span></a>`;
                    } else { contactArea.innerHTML = ''; }

                    // Delete Button
                    const deleteArea = document.getElementById('detDeleteContainer');
                    const currentUserId = {{ Auth::id() }};
                    const userRole = "{{ Auth::user()->role }}";
                    if(userRole === 'admin' || currentUserId === d.user_id) {
                        deleteArea.innerHTML = `<button onclick="triggerDelete(${d.id})" class="w-full bg-slate-100 text-red-500 font-bold py-3 rounded-2xl hover:bg-red-50 transition flex items-center justify-center gap-2"><i class="fas fa-trash-alt"></i> Delete Post</button>`;
                    } else { deleteArea.innerHTML = ''; }

                    // Images
                    const imgSection = document.getElementById('detailImageSection');
                    const imgContainer = document.getElementById('detImg');
                    let images = [];
                    try { images = Array.isArray(d.image) ? d.image : (d.image ? JSON.parse(d.image) : []); } catch(e) { images = []; }
                    
                    totalImgs = images.length;
                    if (totalImgs > 0) {
                        imgSection.classList.remove('hidden');
                        imgContainer.innerHTML = images.map(img => `<div class="w-full h-full flex-shrink-0 snap-center flex items-center justify-center bg-black"><img src="/uploads/${img}" class="max-w-full max-h-full object-contain"></div>`).join('');
                        const prevBtn = document.getElementById('prevBtn');
                        const nextBtn = document.getElementById('nextBtn');
                        if(totalImgs <= 1) { prevBtn.classList.add('hidden'); nextBtn.classList.add('hidden'); } 
                        else { prevBtn.classList.remove('hidden'); nextBtn.classList.remove('hidden'); }
                    } else { imgSection.classList.add('hidden'); }

                    toggleModal('detailModal');
                })
                .catch(err => console.error('Error fetching details:', err));
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
    </script>
</body>
</html>