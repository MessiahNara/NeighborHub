<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reported Content') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl shadow-sm">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-2xl p-8 border border-slate-100">
                <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter mb-6">Pending Reports</h3>
                
                @if($reports->isEmpty())
                    <div class="text-center py-8 text-slate-500 font-medium">
                        <i class="fas fa-check-double text-4xl mb-3 text-green-400"></i>
                        <p>All caught up! There are no pending reports.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-xs text-slate-400 uppercase tracking-widest bg-slate-50 rounded-lg">
                                <tr>
                                    <th scope="col" class="px-6 py-4 rounded-l-lg">Reported By</th>
                                    <th scope="col" class="px-6 py-4">Reason</th>
                                    <th scope="col" class="px-6 py-4">Post Content</th>
                                    <th scope="col" class="px-6 py-4">Post Author</th>
                                    <th scope="col" class="px-6 py-4 rounded-r-lg text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($reports as $report)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ $report->user->name }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-[10px] font-black uppercase tracking-widest">
                                            {{ $report->reason }}
                                        </span>
                                        @if($report->details)
                                            <p class="text-xs text-slate-500 mt-2 truncate w-32 whitespace-normal" title="{{ $report->details }}">
                                                "{{ $report->details }}"
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        @if($report->post)
                                            <strong>{{ Str::limit($report->post->title, 20) }}</strong><br>
                                            <span class="text-xs">{{ Str::limit($report->post->description, 30) }}</span>
                                        @else
                                            <span class="text-red-400 italic">Post already deleted</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-500">
                                        {{ $report->post ? ($report->post->user->name ?? 'Deleted User') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 flex justify-end gap-2 items-center h-full">
                                        
                                        @if($report->post)
                                            <form action="{{ route('admin.reports.resolve', $report->id) }}" method="POST" onsubmit="return confirm('This will DELETE the post. Are you sure?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-white font-black text-[10px] uppercase tracking-widest bg-red-50 hover:bg-red-600 px-3 py-2 rounded-lg transition">
                                                    Delete Post
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('admin.reports.dismiss', $report->id) }}" method="POST" onsubmit="return confirm('This will ignore the report and keep the post. Are you sure?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="font-black text-[10px] uppercase tracking-widest px-3 py-2 rounded-lg transition bg-slate-100 text-slate-600 hover:bg-slate-300">
                                                Dismiss
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>