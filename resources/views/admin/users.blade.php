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
                                <th scope="col" class="px-6 py-4 rounded-l-lg">Name & Email</th>
                                <th scope="col" class="px-6 py-4">ID Verification</th>
                                <th scope="col" class="px-6 py-4">Assign Role</th>
                                <th scope="col" class="px-6 py-4 text-center">Account Status</th>
                                <th scope="col" class="px-6 py-4 rounded-r-lg text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($users as $user)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-800">{{ $user->name }}</p>
                                    <p class="text-slate-500 text-xs mt-0.5">{{ $user->email }}</p>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <form action="{{ route('admin.verify', $user->id) }}" method="POST">
                                        @csrf
                                        <select name="is_verified" onchange="this.form.submit()" class="text-xs p-1 border border-slate-200 rounded bg-white text-slate-700 font-bold mb-1 w-full">
                                            <option value="0" {{ !$user->is_verified ? 'selected' : '' }}>Pending/Rejected</option>
                                            <option value="1" {{ $user->is_verified ? 'selected' : '' }}>✅ Verified User</option>
                                        </select>
                                    </form>
                                    @if($user->verification_document)
                                        <a href="{{ asset('uploads/verifications/' . $user->verification_document) }}" target="_blank" class="text-blue-500 hover:text-blue-700 underline text-[10px] font-black uppercase tracking-widest block mt-1"><i class="fas fa-id-card mr-1"></i> View ID/Cert</a>
                                    @else
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest block mt-1"><i class="fas fa-times-circle mr-1"></i> No ID Uploaded</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    <form action="{{ route('admin.role', $user->id) }}" method="POST">
                                        @csrf
                                        <select name="role" onchange="this.form.submit()" class="text-xs p-1 border border-slate-200 rounded bg-white text-slate-700 font-bold w-full">
                                            <option value="user" {{ $user->role == 'user' ? 'selected' : '' }}>Standard User</option>
                                            <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="captain" {{ $user->role == 'captain' ? 'selected' : '' }}>Brgy. Captain</option>
                                            <option value="kagawad" {{ $user->role == 'kagawad' ? 'selected' : '' }}>Kagawad</option>
                                            <option value="sk_chairman" {{ $user->role == 'sk_chairman' ? 'selected' : '' }}>SK Chairman</option>
                                            <option value="sk_kagawad" {{ $user->role == 'sk_kagawad' ? 'selected' : '' }}>SK Kagawad</option>
                                            <option value="moderator" {{ $user->role == 'moderator' ? 'selected' : '' }}>Moderator</option>
                                        </select>
                                    </form>
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
                        <option value="captain">Brgy. Captain</option>
                        <option value="kagawad">Kagawad</option>
                        <option value="sk_chairman">SK Chairman</option>
                        <option value="sk_kagawad">SK Kagawad</option>
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