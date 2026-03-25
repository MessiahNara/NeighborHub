<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Users') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl shadow-sm">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl shadow-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl shadow-sm">
                    <ul class="list-disc list-inside text-sm font-bold">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-2xl p-8 border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">Registered Users Directory</h3>
                    
                    <button onclick="document.getElementById('createUserModal').classList.remove('hidden')" class="bg-[#36B3C9] text-white px-5 py-2.5 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-cyan-100 hover:brightness-110 active:scale-95 transition flex items-center gap-2">
                        <i class="fas fa-user-plus"></i> Add User
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm whitespace-nowrap">
                        <thead class="text-xs text-slate-400 uppercase tracking-widest bg-slate-50 rounded-lg">
                            <tr>
                                <th scope="col" class="px-6 py-4 rounded-l-lg">Name</th>
                                <th scope="col" class="px-6 py-4">Email</th>
                                <th scope="col" class="px-6 py-4">Role</th>
                                <th scope="col" class="px-6 py-4 text-center">Status</th>
                                <th scope="col" class="px-6 py-4 rounded-r-lg text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($users as $user)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-bold text-slate-800">{{ $user->name }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    @if($user->role === 'admin')
                                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-lg text-[10px] font-black uppercase tracking-widest"><i class="fas fa-shield-alt mr-1"></i> Admin</span>
                                    @elseif($user->role === 'moderator')
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-[10px] font-black uppercase tracking-widest"><i class="fas fa-gavel mr-1"></i> Mod</span>
                                    @else
                                        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-black uppercase tracking-widest"><i class="fas fa-user mr-1"></i> User</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($user->is_banned)
                                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-[10px] font-black uppercase tracking-widest">Banned</span>
                                    @else
                                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-[10px] font-black uppercase tracking-widest">Active</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 flex justify-end gap-2">
                                    
                                    @if($user->role !== 'admin')
                                        @if($user->role !== 'moderator')
                                        <form action="{{ route('admin.users.promoteMod', $user->id) }}" method="POST" onsubmit="return confirm('Promote this user to Moderator? They will be able to review and resolve reported posts.');">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-white font-black text-[10px] uppercase tracking-widest bg-blue-50 hover:bg-blue-600 px-3 py-2 rounded-lg transition">
                                                Make Mod
                                            </button>
                                        </form>
                                        @endif

                                        <form action="{{ route('admin.users.promote', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to make this user an Admin? They will have full control over the system.');">
                                            @csrf
                                            <button type="submit" class="text-purple-600 hover:text-white font-black text-[10px] uppercase tracking-widest bg-purple-50 hover:bg-purple-600 px-3 py-2 rounded-lg transition">
                                                Make Admin
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.users.toggleBan', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            <button type="submit" class="font-black text-[10px] uppercase tracking-widest px-3 py-2 rounded-lg transition {{ $user->is_banned ? 'bg-slate-200 text-slate-700 hover:bg-slate-300' : 'bg-red-50 text-red-600 hover:bg-red-600 hover:text-white' }}">
                                                {{ $user->is_banned ? 'Unban' : 'Ban' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-slate-300 font-bold uppercase tracking-widest py-2">No actions</span>
                                    @endif

                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <div id="createUserModal" class="hidden fixed inset-0 z-[100] bg-slate-900/60 flex items-center justify-center p-4 backdrop-blur-md transition-all">
        <div class="bg-white w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative">
            <button onclick="document.getElementById('createUserModal').classList.add('hidden')" class="absolute top-8 right-8 text-slate-300 hover:text-slate-800 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            
            <h2 class="text-3xl font-black mb-1 uppercase tracking-tighter text-[#36B3C9]">Register Account</h2>
            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-8">Manually onboard a neighbor</p>
            
            <form action="{{ route('admin.users.create') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-2">Full Name</label>
                    <input type="text" name="name" placeholder="E.g. Juan Dela Cruz" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-800 placeholder:text-slate-300" required>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-2">Email Address</label>
                    <input type="email" name="email" placeholder="neighbor@example.com" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-800 placeholder:text-slate-300" required>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-2">Initial Password</label>
                    <input type="text" name="password" placeholder="Minimum 8 characters" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-800 placeholder:text-slate-300" required minlength="8">
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 ml-2">Account Role</label>
                    <select name="role" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border-none focus:ring-2 focus:ring-[#36B3C9]/20 font-bold text-slate-600 cursor-pointer" required>
                        <option value="user" selected>Standard User</option>
                        <option value="moderator">Moderator</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <button type="submit" class="w-full bg-[#36B3C9] text-white font-black py-4 rounded-[1.8rem] shadow-xl shadow-cyan-100 mt-4 transition hover:brightness-110 active:scale-95 uppercase tracking-widest text-xs">
                    Create Account
                </button>
            </form>
        </div>
    </div>
</x-app-layout>