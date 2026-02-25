<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Overview') }}
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

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 border-[#36B3C9]">
                    <div class="text-xs text-slate-400 uppercase tracking-widest font-black mb-1">Total Users</div>
                    <div class="text-4xl font-black text-slate-800">{{ $stats['total_users'] }}</div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 border-blue-500">
                    <div class="text-xs text-slate-400 uppercase tracking-widest font-black mb-1">Total Posts</div>
                    <div class="text-4xl font-black text-slate-800">{{ $stats['total_posts'] }}</div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 border-yellow-500">
                    <div class="text-xs text-slate-400 uppercase tracking-widest font-black mb-1">Pending Requests</div>
                    <div class="text-4xl font-black text-slate-800">{{ $stats['total_requests'] }}</div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 border-red-500">
                    <div class="text-xs text-slate-400 uppercase tracking-widest font-black mb-1">Complaints</div>
                    <div class="text-4xl font-black text-slate-800">{{ $stats['total_complaints'] }}</div>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-white shadow-sm sm:rounded-2xl p-8 border border-slate-100">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter mb-6">Post Distribution</h3>
                    <div class="w-full h-80">
                        <canvas id="postsChart"></canvas>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-2xl p-8 border border-slate-100">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter mb-6">Recent Platform Activity</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm whitespace-nowrap">
                            <thead class="text-xs text-slate-400 uppercase tracking-widest bg-slate-50 rounded-lg">
                                <tr>
                                    <th scope="col" class="px-6 py-4 rounded-l-lg">Title</th>
                                    <th scope="col" class="px-6 py-4">Category</th>
                                    <th scope="col" class="px-6 py-4">Author</th>
                                    <th scope="col" class="px-6 py-4 rounded-r-lg">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($recentPosts as $post)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ Str::limit($post->title, 40) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-black uppercase tracking-widest">{{ $post->category }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 font-medium">{{ $post->user?->name ?? 'Deleted User' }}</td>
                                    <td class="px-6 py-4">
                                        <form action="{{ route('admin.posts.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-black text-[10px] uppercase tracking-widest bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('postsChart').getContext('2d');
        const postsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($categories) !!},
                datasets: [{
                    label: 'Number of Posts',
                    data: {!! json_encode($chartData) !!},
                    backgroundColor: '#36B3C9',
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</x-app-layout>