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

            <div class="bg-white shadow-sm sm:rounded-2xl p-8 border border-slate-100">
                <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter mb-6">Registered Users Directory</h3>
                
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
</x-app-layout>