<x-app-layout>
    <div class="max-w-4xl mx-auto p-6 pb-24">
        <h1 class="text-4xl font-black uppercase tracking-tighter text-slate-800 mb-8">My Inbox</h1>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
            @if($conversations->isEmpty())
                <div class="p-16 text-center">
                    <div class="bg-slate-50 text-slate-300 w-24 h-24 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-4xl">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <p class="text-slate-800 font-black text-2xl uppercase tracking-tighter">No Messages Yet</p>
                    <p class="text-slate-400 font-bold text-sm mt-2">When someone messages you about a post, it will appear here.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($conversations as $conversation)
                        @php
                            // Determine who the other person in the chat is
                            $otherUser = $conversation->sender_id === auth()->id() ? $conversation->receiver : $conversation->sender;
                            $latestMessage = $conversation->messages->first();
                            $isUnread = $latestMessage && $latestMessage->user_id !== auth()->id() && !$latestMessage->is_read;
                        @endphp

                        <div class="p-6 hover:bg-slate-50 transition cursor-pointer flex items-center gap-6 group" onclick="openChatBox({{ $conversation->post_id }})">
                            <div class="w-16 h-16 rounded-[1.5rem] flex-shrink-0 flex items-center justify-center text-2xl font-bold transition-all group-hover:scale-110 
                                {{ $isUnread ? 'bg-[#36B3C9] text-white shadow-lg shadow-cyan-100' : 'bg-slate-100 text-slate-400' }}">
                                <i class="fas fa-user"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h3 class="text-lg font-black text-slate-800 truncate {{ $isUnread ? 'text-[#36B3C9]' : '' }}">
                                        {{ $otherUser->name ?? 'Deleted User' }}
                                    </h3>
                                    @if($latestMessage)
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap ml-4">
                                            {{ $latestMessage->created_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>

                                @if($conversation->post)
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1 truncate">
                                        <i class="fas fa-tag mr-1 text-slate-300"></i> Regarding: {{ $conversation->post->title }}
                                    </p>
                                @endif

                                <p class="text-sm font-medium truncate {{ $isUnread ? 'text-slate-800 font-bold' : 'text-slate-500' }}">
                                    @if($latestMessage)
                                        @if($latestMessage->user_id === auth()->id())
                                            <span class="text-slate-400 mr-1">You:</span>
                                        @endif
                                        {{ $latestMessage->body }}
                                    @else
                                        <span class="italic text-slate-400">No messages yet.</span>
                                    @endif
                                </p>
                            </div>

                            <div class="text-slate-300 group-hover:text-[#36B3C9] transition pr-2">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>

                        @if($conversation->post)
                            <div id="chatBox-{{ $conversation->post_id }}" class="hidden fixed bottom-0 right-4 sm:right-10 w-80 bg-white border border-slate-200 shadow-2xl rounded-t-2xl flex-col z-[200]">
                                <div class="bg-[#36B3C9] text-white p-4 rounded-t-2xl flex justify-between items-center cursor-pointer shadow-sm" onclick="toggleChatBody({{ $conversation->post_id }})">
                                    <span class="font-black uppercase tracking-widest text-[10px] truncate"><i class="fas fa-comment-dots mr-2"></i> {{ $conversation->post->title }}</span>
                                    <button onclick="closeChatBox({{ $conversation->post_id }}, event)" class="text-white hover:text-slate-200 text-lg leading-none transition">&times;</button>
                                </div>
                                <div id="chatBody-{{ $conversation->post_id }}" class="flex flex-col">
                                    <div id="chatMessages-{{ $conversation->post_id }}" class="h-64 overflow-y-auto p-4 bg-slate-50 flex flex-col gap-2">
                                        <div class="text-center text-[10px] font-black uppercase tracking-widest text-slate-300 mt-10">Loading messages...</div>
                                    </div>
                                    <div class="p-3 border-t border-slate-100 bg-white">
                                        <form onsubmit="sendChatMessage(event, {{ $conversation->post_id }})" class="flex gap-2">
                                            <input type="text" id="chatInput-{{ $conversation->post_id }}" class="flex-1 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-2 text-sm font-bold text-slate-700 focus:outline-none focus:border-[#36B3C9] focus:ring-1 focus:ring-[#36B3C9] placeholder:text-slate-300 transition" placeholder="Write a message..." required autocomplete="off">
                                            <button type="submit" class="bg-[#36B3C9] text-white w-10 h-10 flex items-center justify-center rounded-2xl font-bold hover:brightness-110 active:scale-95 transition shadow-md shadow-cyan-100">
                                                <i class="fas fa-paper-plane text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif

                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>