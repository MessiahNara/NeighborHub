<section>
    <header class="mb-8 flex flex-col sm:flex-row items-center sm:items-start gap-4 border-b border-slate-100 pb-6">
        <div class="w-14 h-14 bg-[#36B3C9]/10 text-[#36B3C9] rounded-[1.2rem] flex items-center justify-center text-2xl flex-shrink-0">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="text-center sm:text-left">
            <h2 class="text-2xl font-black text-slate-800 tracking-tighter uppercase">
                Personal Info
            </h2>
            <p class="mt-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Update your account's basic details and email.
            </p>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-3">Full Name</label>
            <input id="name" name="name" type="text" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border border-slate-100 font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20 transition-all focus:bg-white" value="{{ old('name', $user->name) }}" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-3 mt-2">Email Address</label>
            <input id="email" name="email" type="email" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border border-slate-100 font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20 transition-all focus:bg-white" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-4 bg-yellow-50 p-5 rounded-2xl border border-yellow-100 text-center sm:text-left">
                    <p class="text-[10px] text-yellow-700 font-black uppercase tracking-widest">
                        Your email address is unverified.
                        <button form="send-verification" class="text-[#36B3C9] hover:text-cyan-600 underline font-black uppercase tracking-widest text-[10px] sm:ml-2 mt-2 sm:mt-0 block sm:inline">
                            Click here to re-send link.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-3 font-black text-[10px] uppercase tracking-widest text-emerald-600">
                            A new verification link has been sent to your email address.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-4 pt-4">
            <button type="submit" class="w-full sm:w-auto bg-slate-800 text-white px-10 py-4 rounded-[1.5rem] font-black text-[10px] uppercase tracking-widest shadow-xl shadow-slate-200 transition hover:bg-slate-700 active:scale-95">
                Save Information
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="w-full sm:w-auto text-center text-[10px] font-black text-emerald-500 uppercase tracking-widest bg-emerald-50 px-5 py-4 rounded-[1.2rem] border border-emerald-100 shadow-sm flex items-center justify-center gap-2"><i class="fas fa-check-circle text-sm"></i> Details Updated</p>
            @endif
        </div>
    </form>
</section>