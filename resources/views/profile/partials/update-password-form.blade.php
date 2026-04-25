<section>
    <header class="mb-8 flex flex-col sm:flex-row items-center sm:items-start gap-4 border-b border-slate-100 pb-6">
        <div class="w-14 h-14 bg-slate-800 text-white rounded-[1.2rem] flex items-center justify-center text-xl flex-shrink-0 shadow-md">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="text-center sm:text-left">
            <h2 class="text-2xl font-black text-slate-800 tracking-tighter uppercase">
                Security
            </h2>
            <p class="mt-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Ensure your account is using a long, secure password.
            </p>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-3">Current Password</label>
            <input id="update_password_current_password" name="current_password" type="password" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border border-slate-100 font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20 transition-all focus:bg-white" autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-3 mt-2">New Password</label>
            <input id="update_password_password" name="password" type="password" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border border-slate-100 font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20 transition-all focus:bg-white" autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-3 mt-2">Confirm Password</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border border-slate-100 font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-[#36B3C9]/20 transition-all focus:bg-white" autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-4 pt-4">
            <button type="submit" class="w-full sm:w-auto bg-[#36B3C9] text-white px-10 py-4 rounded-[1.5rem] font-black text-[10px] uppercase tracking-widest shadow-xl shadow-cyan-200 transition hover:brightness-110 active:scale-95">
                Update Password
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="w-full sm:w-auto text-center text-[10px] font-black text-emerald-500 uppercase tracking-widest bg-emerald-50 px-5 py-4 rounded-[1.2rem] border border-emerald-100 shadow-sm flex items-center justify-center gap-2"><i class="fas fa-check-circle text-sm"></i> Password Saved</p>
            @endif
        </div>
    </form>
</section>