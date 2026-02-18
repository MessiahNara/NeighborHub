<x-app-layout>
    <div class="bg-slate-50 min-h-screen font-sans antialiased pb-20">
        <header class="p-8 max-w-4xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="h-10 w-10 bg-white rounded-full flex items-center justify-center shadow-sm">
                    <i class="fas fa-arrow-left text-[#36B3C9]"></i>
                </a>
                <h1 class="text-3xl font-black text-slate-800 tracking-tighter">Account Settings</h1>
            </div>
        </header>

        <div class="max-w-4xl mx-auto px-6 space-y-6">
            <div class="p-8 bg-white shadow-xl rounded-[2.5rem] border border-slate-100">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-8 bg-white shadow-xl rounded-[2.5rem] border border-slate-100">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-8 bg-white shadow-xl rounded-[2.5rem] border border-red-100">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>