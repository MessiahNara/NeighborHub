<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-[#36B3C9]/10 text-[#36B3C9] rounded-2xl flex items-center justify-center text-2xl shadow-sm">
                <i class="fas fa-user-cog"></i>
            </div>
            <h2 class="font-black text-3xl md:text-4xl text-slate-800 leading-tight tracking-tighter uppercase">
                {{ __('Profile Settings') }}
            </h2>
        </div>
    </x-slot>

```
<div class="py-12 pb-24">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-10">

        {{-- 1. BIG AVATAR & VERIFICATION PANEL --}}
        <div class="p-8 sm:p-12 bg-white shadow-xl shadow-slate-200/40 rounded-[3rem] border border-slate-50 relative overflow-hidden text-center">
            <div class="absolute top-0 left-0 w-full h-32 bg-slate-50 border-b border-slate-100"></div>
            
            <form method="post" action="{{ route('profile.upload-docs') }}" enctype="multipart/form-data" class="relative z-10 flex flex-col items-center">
                @csrf
                
                {{-- Smaller Circle Avatar --}}
                <div class="relative mb-6 group cursor-pointer mt-4">
                    @if(Auth::user()->profile_picture)
                        <img src="{{ asset('uploads/profiles/' . basename(Auth::user()->profile_picture)) }}" class="w-24 h-24 rounded-full object-cover shadow-xl border-4 border-white group-hover:scale-105 transition-transform duration-300 mx-auto bg-white">
                    @else
                        <div class="w-24 h-24 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center text-3xl shadow-inner border-4 border-white group-hover:scale-105 transition-transform duration-300 mx-auto relative overflow-hidden">
                            <i class="fas fa-user mt-2"></i>
                        </div>
                    @endif
                    
                    {{-- Smaller Camera Overlay --}}
                    <div class="absolute bottom-1 right-1 bg-[#36B3C9] text-white w-8 h-8 rounded-full flex items-center justify-center shadow-lg border-2 border-white pointer-events-none group-hover:bg-cyan-400 transition-colors">
                        <i class="fas fa-camera text-xs"></i>
                    </div>
                    
                    {{-- Invisible File Input --}}
                    <input id="profile_picture" name="profile_picture" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" onchange="document.getElementById('saveAvatarBtn').classList.remove('hidden');" />
                </div>

                <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase mb-3">
                    {{ Auth::user()->name }}
                </h2>
                
                <div class="flex justify-center mb-6">
                    @if(Auth::user()->is_verified)
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-[1rem] text-[9px] font-black uppercase tracking-widest border border-emerald-200 shadow-sm">
                            <i class="fas fa-check-circle text-sm"></i> Fully Verified
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-50 text-amber-600 rounded-[1rem] text-[9px] font-black uppercase tracking-widest border border-amber-200 shadow-sm">
                            <i class="fas fa-clock text-sm"></i> Unverified
                        </span>
                    @endif
                </div>

                <p class="text-[10px] uppercase tracking-widest text-slate-400 font-black leading-relaxed max-w-sm mx-auto mb-8">
                    @if(Auth::user()->is_verified)
                        Tap your picture above to change it. Your identity is already verified!
                    @else
                        Upload a profile picture and your valid ID or Certificate of Residency below to unlock posting abilities.
                    @endif
                </p>

                {{-- Verification Document --}}
                @if(!Auth::user()->is_verified)
                    <div class="bg-slate-50 p-6 rounded-[2rem] border border-slate-100 w-full max-w-md mb-8 text-left group hover:border-[#36B3C9]/30 transition-all">
                        <label for="verification_document" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">
                            Valid ID / Brgy. Certificate <span class="normal-case font-bold text-red-400 ml-1">*Required</span>
                        </label>
                        
                        @if(Auth::user()->verification_document)
                            <div class="inline-flex items-center gap-3 text-[9px] uppercase tracking-widest text-emerald-600 font-black bg-emerald-50 px-4 py-3 rounded-xl border border-emerald-200 shadow-sm mb-4">
                                <i class="fas fa-file-check text-base"></i> Document uploaded — waiting review.
                            </div>
                        @endif
                        
                        <input id="verification_document" name="verification_document" type="file" accept="image/*,.pdf"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-6 file:rounded-2xl file:border-0 file:text-[10px] file:font-black file:uppercase file:tracking-widest file:bg-[#36B3C9]/10 file:text-[#36B3C9] hover:file:bg-[#36B3C9] hover:file:text-white transition-colors cursor-pointer focus:outline-none bg-white p-2 border border-slate-200 rounded-[1.5rem]" onchange="document.getElementById('saveAvatarBtn').classList.remove('hidden');" />
                    </div>
                @endif

                <button id="saveAvatarBtn" type="submit" class="hidden w-full max-w-xs bg-slate-800 hover:bg-slate-700 text-white px-8 py-4 rounded-[1.5rem] font-black text-[10px] uppercase tracking-widest shadow-xl shadow-slate-200 transition-all hover:-translate-y-1 active:scale-95 focus:outline-none">
                    {{ Auth::user()->is_verified ? 'Save Profile Picture' : 'Submit for Review' }}
                </button>
                
                @if (session('status') === 'Files uploaded successfully!')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                        class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 px-5 py-3 rounded-xl border border-emerald-200 shadow-sm flex items-center gap-2 mt-4 mx-auto w-max">
                        <i class="fas fa-check-circle text-sm"></i> Saved Successfully
                    </p>
                @endif
            </form>
        </div>

        {{-- 2. UPDATE PROFILE INFO --}}
        <div class="p-8 sm:p-12 bg-white shadow-xl shadow-slate-200/40 rounded-[3rem] border border-slate-50">
            <div class="max-w-2xl mx-auto">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        {{-- 3. UPDATE PASSWORD --}}
        <div class="p-8 sm:p-12 bg-white shadow-xl shadow-slate-200/40 rounded-[3rem] border border-slate-50">
            <div class="max-w-2xl mx-auto">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        {{-- 4. DELETE ACCOUNT --}}
        <div class="p-8 sm:p-12 bg-red-50/30 shadow-xl shadow-slate-200/40 rounded-[3rem] border border-red-50">
            <div class="max-w-2xl mx-auto text-center sm:text-left">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
```

</x-app-layout>
