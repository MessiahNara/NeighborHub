<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-2xl border border-slate-100">
                <section>
                    <header>
                        <h2 class="text-lg font-black text-slate-800 uppercase tracking-tighter">
                            Profile Picture & Verification
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 font-medium">
                            Upload a profile picture and your valid ID or Certificate of Residency to get verified. Verified users unlock the ability to post in Buy & Sell, Borrow, and Services.
                        </p>
                        
                        <div class="mt-4">
                            @if(Auth::user()->is_verified)
                                <span class="px-4 py-1.5 bg-green-100 text-green-700 rounded-xl text-[10px] font-black uppercase tracking-widest border border-green-200 shadow-sm"><i class="fas fa-check-circle mr-1"></i> Verified Account</span>
                            @else
                                <span class="px-4 py-1.5 bg-yellow-100 text-yellow-700 rounded-xl text-[10px] font-black uppercase tracking-widest border border-yellow-200 shadow-sm"><i class="fas fa-clock mr-1"></i> Unverified / Pending Review</span>
                            @endif
                        </div>
                    </header>

                    <form method="post" action="{{ route('profile.upload-docs') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                        @csrf
                        
                        <div class="bg-slate-50 p-5 rounded-[1.5rem] border border-slate-100">
                            <x-input-label for="profile_picture" value="Profile Picture (Optional)" class="font-black text-[10px] uppercase tracking-widest text-slate-400 mb-3" />
                            
                            @if(Auth::user()->profile_picture)
                                <div class="mt-2 mb-4">
                                    <img src="{{ asset('uploads/profiles/' . Auth::user()->profile_picture) }}" class="w-20 h-20 rounded-[1.5rem] object-cover shadow-md border-2 border-white">
                                </div>
                            @endif
                            
                            <input id="profile_picture" name="profile_picture" type="file" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:uppercase file:tracking-widest file:font-black file:bg-[#36B3C9]/10 file:text-[#36B3C9] hover:file:bg-[#36B3C9] hover:file:text-white transition cursor-pointer" />
                        </div>

                        <div class="bg-slate-50 p-5 rounded-[1.5rem] border border-slate-100">
                            <x-input-label for="verification_document" value="Valid ID / Brgy. Certificate (Required for Verification)" class="font-black text-[10px] uppercase tracking-widest text-slate-400 mb-3" />
                            
                            @if(Auth::user()->verification_document)
                                <p class="text-xs text-green-600 mb-4 font-bold bg-green-50 inline-block px-3 py-1 rounded-lg border border-green-100"><i class="fas fa-file-check mr-1"></i> Document uploaded. Waiting for Admin review.</p>
                            @endif
                            
                            <input id="verification_document" name="verification_document" type="file" accept="image/*,.pdf" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0 file:text-[10px] file:uppercase file:tracking-widest file:font-black file:bg-red-50 file:text-red-600 hover:file:bg-red-600 hover:file:text-white transition cursor-pointer" />
                            <p class="text-[10px] font-bold text-slate-400 mt-3"><i class="fas fa-info-circle"></i> Note: Uploading a new ID will set your status back to pending until approved.</p>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="bg-[#36B3C9] text-white px-8 py-3.5 rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-cyan-100 hover:brightness-110 active:scale-95 transition">
                                Upload & Submit for Review
                            </button>
                            
                            @if (session('status') === 'Files uploaded successfully!')
                                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)" class="text-[10px] font-black uppercase tracking-widest text-green-500 bg-green-50 px-3 py-1.5 rounded-lg border border-green-100"><i class="fas fa-check mr-1"></i> Uploaded</p>
                            @endif
                        </div>
                    </form>
                </section>
            </div>
            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-2xl border border-slate-100">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-2xl border border-slate-100">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-2xl border border-slate-100">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>