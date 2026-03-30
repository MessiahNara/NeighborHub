<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Tags') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl shadow-sm">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-2xl p-8 border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">Dynamic Tags</h3>
                    <button onclick="document.getElementById('createTagModal').classList.remove('hidden')" class="bg-[#36B3C9] text-white px-5 py-2.5 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-cyan-100 hover:brightness-110 active:scale-95 transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> New Tag
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="text-xs text-slate-400 uppercase tracking-widest bg-slate-50 rounded-lg">
                            <tr>
                                <th scope="col" class="px-6 py-4 rounded-l-lg">Tag Name</th>
                                <th scope="col" class="px-6 py-4">Belongs to Category</th>
                                <th scope="col" class="px-6 py-4 rounded-r-lg text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($tags as $tag)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-black text-slate-600"><span class="bg-slate-100 px-3 py-1 rounded-lg text-xs">{{ $tag->name }}</span></td>
                                <td class="px-6 py-4 font-bold text-[#36B3C9] uppercase tracking-widest text-[10px]">{{ str_replace('-', ' ', $tag->category_slug) }}</td>
                                <td class="px-6 py-4 flex justify-end">
                                    <form action="{{ route('admin.tags.destroy', $tag->id) }}" method="POST" onsubmit="return confirm('Delete this tag?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-white font-black text-[10px] uppercase tracking-widest bg-red-50 hover:bg-red-500 px-3 py-2 rounded-lg transition">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-slate-400 font-bold uppercase tracking-widest text-xs">No tags created yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="createTagModal" class="hidden fixed inset-0 z-[100] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md transition-all">
        <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative">
            <button onclick="document.getElementById('createTagModal').classList.add('hidden')" class="absolute top-8 right-8 text-slate-300 hover:text-slate-800 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <h2 class="text-3xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">New Tag</h2>
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-8">Add a filterable tag</p>
            
            <form action="{{ route('admin.tags.store') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-2">Assign to Category</label>
                    <select name="category_slug" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-600 cursor-pointer uppercase tracking-widest text-xs" required>
                        <option value="" disabled selected>Select a Category...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ str_replace('-', ' ', $cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-2">Tag Name</label>
                    <input type="text" name="name" placeholder="E.g. Electronics, Plumbing, etc." class="w-full p-4 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-800 placeholder:text-slate-300" required>
                </div>
                <button type="submit" class="w-full bg-[#36B3C9] text-white font-black py-4 rounded-[1.8rem] shadow-xl shadow-cyan-100 mt-4 transition hover:brightness-110 active:scale-95 uppercase tracking-widest text-xs">Save Tag</button>
            </form>
        </div>
    </div>
</x-app-layout>