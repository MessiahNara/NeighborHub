<section class="flex flex-col items-center text-center py-4">
    
    <div class="w-20 h-20 bg-red-100 text-red-500 rounded-[2rem] flex items-center justify-center text-3xl shadow-inner border border-red-200 mb-6">
        <i class="fas fa-exclamation-triangle"></i>
    </div>

    <header class="mb-8">
        <h2 class="text-3xl font-black text-red-500 tracking-tighter uppercase">
            Danger Zone
        </h2>
        <p class="mt-3 text-[10px] font-black text-slate-400 uppercase tracking-widest max-w-sm leading-relaxed mx-auto">
            Once your account is deleted, all resources and data will be permanently wiped.
        </p>
    </header>

    <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" class="w-full sm:w-auto bg-white text-red-500 border-2 border-red-100 hover:bg-red-50 px-10 py-4 rounded-[1.5rem] font-black text-[10px] uppercase tracking-widest shadow-sm transition active:scale-95 flex items-center justify-center gap-3">
        <i class="fas fa-trash-alt text-base"></i> Delete Account
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-8 bg-white rounded-[3rem] text-center shadow-2xl relative animate-pop">
            @csrf
            @method('delete')
            
            <button type="button" x-on:click="$dispatch('close')" class="absolute top-5 right-5 w-10 h-10 bg-slate-50 text-slate-400 hover:bg-slate-100 rounded-full flex items-center justify-center transition focus:outline-none">
                <i class="fas fa-times text-lg"></i>
            </button>

            <div class="w-24 h-24 bg-red-50 text-red-500 rounded-[2.5rem] flex items-center justify-center mx-auto mb-6 text-4xl shadow-sm border border-red-100">
                <i class="fas fa-bomb"></i>
            </div>

            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase mb-4">
                Are you sure?
            </h2>

            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-8 px-4 leading-relaxed">
                This action is irreversible. Please enter your password to confirm deletion.
            </p>

            <div class="mt-6 text-left mb-8 max-w-sm mx-auto">
                <label for="password" class="sr-only">Password</label>
                <input id="password" name="password" type="password" class="w-full p-4 bg-slate-50 rounded-[1.5rem] border-none font-black text-sm text-slate-800 placeholder:text-slate-300 focus:ring-2 focus:ring-red-200 transition-all text-center tracking-widest shadow-inner" placeholder="ENTER PASSWORD" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2 text-center text-[10px] font-black uppercase text-red-500" />
            </div>

            <div class="flex flex-col gap-3 max-w-sm mx-auto">
                <button type="submit" class="w-full bg-red-500 text-white font-black py-4 rounded-[1.5rem] shadow-lg shadow-red-100 hover:bg-red-600 transition active:scale-95 uppercase tracking-widest text-[10px]">
                    Delete Forever
                </button>
                <button type="button" x-on:click="$dispatch('close')" class="w-full text-slate-400 font-black py-3 hover:text-slate-600 transition uppercase tracking-widest text-[10px]">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>
</section>