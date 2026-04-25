<x-app-layout>
    {{-- Hide default header --}}
    <x-slot name="header"></x-slot>

    <div class="min-h-screen pb-28 pt-8 relative">
        
        <div class="relative z-10 max-w-2xl mx-auto px-4 space-y-6">
            
            {{-- 2. THE MAIN PANEL (The Bubble) --}}
            <div class="bg-white rounded-[3rem] p-8 sm:p-10 shadow-2xl shadow-slate-200/60 relative mt-8 border border-slate-100">
                
                {{-- 1. TITLE & DESCRIPTION --}}
                <header class="mb-8 flex flex-col sm:flex-row items-center sm:items-start gap-4 border-b border-slate-100 pb-6">
                    <div class="w-14 h-14 bg-[#36B3C9]/10 text-[#36B3C9] rounded-[1.2rem] flex items-center justify-center text-2xl flex-shrink-0">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="text-center sm:text-left">
                        <h1 class="text-2xl font-black text-slate-800 tracking-tighter uppercase">
                            {{ request('onboarding') ? 'Welcome!' : 'Profile' }}
                        </h1>
                        <p class="mt-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            @if(request('onboarding'))
                                Let's get your profile set up! Upload a photo and your ID to unlock full access.
                            @elseif(Auth::user()->is_verified)
                                Click your picture to update your profile photo.
                            @elseif(Auth::user()->verification_document)
                                Your documents have been submitted. Please wait for admin approval.
                            @else
                                Update your verification documents or change your profile picture.
                            @endif
                        </p>
                    </div>
                </header>

                {{-- x-data initializes Alpine.js for live previews and button toggling --}}
                <form method="post" action="{{ route('profile.upload-docs') }}" enctype="multipart/form-data" class="flex flex-col items-center w-full"
                      x-data="{ avatarPreview: null, docName: null, docPreview: null, isImage: false, showSubmit: false }">
                    @csrf
                    
                    {{-- SUCCESS FLASH MESSAGE --}}
                    @if (session('status') || session('success'))
                        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 5000)" 
                             class="w-full bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-2xl mb-6 text-[10px] font-black uppercase tracking-widest text-center shadow-sm">
                            <i class="fas fa-check-circle mr-1"></i> Files submitted successfully!
                        </div>
                    @endif

                    {{-- Avatar Section --}}
                    <div class="relative group cursor-pointer mb-6">
                        <div class="w-24 h-24 rounded-full shadow-xl border-4 border-white bg-slate-50 group-hover:scale-105 transition-transform duration-300 relative overflow-hidden flex items-center justify-center text-3xl text-slate-300">
                            {{-- Live Preview of new Avatar --}}
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" class="w-full h-full object-cover">
                            </template>

                            {{-- Existing Avatar or Placeholder --}}
                            <template x-if="!avatarPreview">
                                @if(Auth::user()->profile_picture)
                                    <img src="{{ asset('uploads/profiles/' . basename(Auth::user()->profile_picture)) }}" class="w-full h-full object-cover">
                                @else
                                    <i class="fas fa-user mt-2"></i>
                                @endif
                            </template>
                        </div>
                        
                        {{-- Camera Overlay Button --}}
                        <div class="absolute bottom-0 right-0 bg-[#36B3C9] text-white w-8 h-8 rounded-full flex items-center justify-center shadow-md border-2 border-white pointer-events-none group-hover:bg-cyan-400 transition-colors">
                            <i class="fas fa-camera text-[10px]"></i>
                        </div>
                        
                        <input id="profile_picture" name="profile_picture" type="file" accept="image/*" title="" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" 
                               @change="
                                   const file = $event.target.files[0];
                                   if(file) {
                                       avatarPreview = URL.createObjectURL(file);
                                       showSubmit = true;
                                   }
                               " />
                    </div>

                    {{-- User Name --}}
                    <div class="mb-4">
                        <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase leading-none text-center">
                            {{ Auth::user()->name }}
                        </h2>
                    </div>
                    
                    {{-- 2. SMART VERIFICATION BADGE --}}
                    <div class="flex justify-center mb-8">
                        @if(Auth::user()->is_verified)
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl text-[10px] font-black uppercase tracking-widest border border-emerald-200 shadow-sm">
                                <i class="fas fa-check-circle text-sm"></i> Fully Verified
                            </span>
                        @elseif(Auth::user()->verification_document)
                            {{-- PENDING REVIEW STATE --}}
                            <span class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-50 text-blue-600 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-blue-100 shadow-sm">
                                <i class="fas fa-hourglass-half text-sm animate-pulse"></i> Pending Review
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-amber-50 text-amber-600 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-amber-100 shadow-sm">
                                <i class="fas fa-clock text-sm"></i> Unverified Profile
                            </span>
                        @endif
                    </div>

                    {{-- 3. DOCUMENT UPLOAD AREA --}}
                    @if(!Auth::user()->is_verified)
                        
                        {{-- Pending Info Box --}}
                        @if(Auth::user()->verification_document)
                            <div class="w-full bg-blue-50/50 border border-blue-100 p-4 rounded-3xl mb-4 flex items-center gap-4 text-left">
                                <div class="w-10 h-10 bg-white rounded-2xl shadow-sm flex items-center justify-center text-blue-500 shrink-0">
                                    <i class="fas fa-file-signature text-lg"></i>
                                </div>
                                <div>
                                    <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-widest">Document Submitted</h4>
                                    <p class="text-[9px] font-bold text-blue-400 uppercase tracking-widest mt-0.5">Please wait for admin approval.</p>
                                </div>
                            </div>
                        @endif

                        {{-- The Dashed Dropzone Box --}}
                        <div class="w-full mb-8 relative group">
                            <div class="border-2 border-dashed rounded-[2rem] p-8 text-center transition-all duration-300 relative overflow-hidden"
                                 :class="docName ? 'border-[#36B3C9] bg-cyan-50/30' : 'border-slate-200 bg-slate-50 group-hover:border-slate-300 group-hover:bg-slate-100'">
                                
                                {{-- Default State: No file selected --}}
                                <div x-show="!docName" class="flex flex-col items-center gap-2 pointer-events-none">
                                    <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center text-[#36B3C9] mb-2">
                                        <i class="fas fa-cloud-upload-alt text-xl"></i>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-800 uppercase tracking-widest">
                                        {{ Auth::user()->verification_document ? 'Upload a different document' : 'Upload Valid ID / Brgy. Certificate' }}
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Tap to browse files (Images or PDF) <span class="text-red-400">*</span></span>
                                </div>

                                {{-- Active State: File Selected (Preview Mode) --}}
                                <div x-show="docName" style="display: none;" class="flex items-center gap-4 text-left pointer-events-none">
                                    {{-- Image Preview Thumbnail --}}
                                    <div x-show="isImage" class="w-16 h-16 rounded-2xl bg-white shadow-sm border border-slate-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        <img :src="docPreview" class="w-full h-full object-cover">
                                    </div>
                                    
                                    {{-- PDF / File Icon --}}
                                    <div x-show="!isImage" class="w-16 h-16 rounded-2xl bg-white shadow-sm border border-slate-100 shrink-0 flex items-center justify-center text-red-400 text-2xl">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>

                                    <div class="flex flex-col flex-1 overflow-hidden">
                                        <span class="text-[11px] font-black text-emerald-600 uppercase tracking-widest mb-1 flex items-center gap-1">
                                            <i class="fas fa-check-circle"></i> File Attached
                                        </span>
                                        <span x-text="docName" class="text-sm font-bold text-slate-700 truncate w-full"></span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Tap box to change file</span>
                                    </div>
                                </div>

                                {{-- Hidden Input --}}
                                <input id="verification_document" name="verification_document" type="file" accept="image/*,.pdf" title=""
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20"
                                       @change="
                                           const file = $event.target.files[0];
                                           if(file) {
                                               docName = file.name;
                                               isImage = file.type.startsWith('image/');
                                               if(isImage) {
                                                   docPreview = URL.createObjectURL(file);
                                               }
                                               showSubmit = true;
                                           }
                                       " />
                            </div>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="flex flex-col gap-3 w-full max-w-xs mx-auto min-h-[60px]">
                        
                        <button type="submit" x-show="showSubmit" x-transition.opacity.duration.300ms style="display: none;"
                                class="w-full bg-[#36B3C9] hover:brightness-110 text-white px-8 py-4 rounded-[1.5rem] font-black text-[10px] uppercase tracking-widest shadow-xl shadow-cyan-200 transition-all active:scale-95 focus:outline-none text-center">
                            Save Changes
                        </button>
                        
                        @if(request('onboarding'))
                            <a href="{{ route('dashboard') }}" x-show="!showSubmit" x-transition.opacity.duration.300ms class="w-full block text-center bg-slate-100 hover:bg-slate-200 text-slate-500 px-8 py-4 rounded-[1.5rem] font-black text-[10px] uppercase tracking-widest transition-all active:scale-95 border border-slate-200 shadow-sm">
                                Skip for now <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- 3. ADDITIONAL SETTINGS (Only if not onboarding) --}}
            @if(!request('onboarding'))
                <div class="space-y-6">
                    <div class="p-8 bg-white shadow-xl shadow-slate-200/40 rounded-[3rem] border border-slate-100">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                    
                    <div class="p-8 bg-white shadow-xl shadow-slate-200/40 rounded-[3rem] border border-slate-100">
                        @include('profile.partials.update-password-form')
                    </div>
                    
                    <div class="p-8 bg-red-50/30 rounded-[3rem] border border-red-50 text-center sm:text-left">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>