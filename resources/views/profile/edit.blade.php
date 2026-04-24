<x-app-layout>
    <x-slot name="header">
        <h2 class="font-extrabold text-2xl text-slate-900 leading-tight tracking-tight">
            {{ __('Profile Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="p-8 sm:p-10 bg-white shadow-sm sm:rounded-3xl border border-slate-100">
                <section>
                    <header>
                        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                            Profile Picture & Verification
                        </h2>
                        <p class="mt-2 text-sm text-slate-500 font-medium leading-relaxed">
                            Upload a profile picture and your valid ID or Certificate of Residency to get verified. Verified users unlock the ability to post in Buy & Sell, Borrow, and Services.
                        </p>
                        
                        <div class="mt-5">
                            @if(Auth::user()->is_verified)
                                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-xs font-bold uppercase tracking-wide border border-emerald-200 shadow-sm">
                                    <i class="fas fa-check-circle"></i> Verified Account
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-xs font-bold uppercase tracking-wide border border-amber-200 shadow-sm">
                                    <i class="fas fa-clock"></i> Unverified / Pending Review
                                </span>
                            @endif
                        </div>
                    </header>

                    <form method="post" action="{{ route('profile.upload-docs') }}" enctype="multipart/form-data" class="mt-8 space-y-5">
                        @csrf
                        
                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200">
                            <label for="profile_picture" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">
                                Profile Picture <span class="normal-case font-medium text-slate-400">(Optional)</span>
                            </label>
                            
                            @if(Auth::user()->profile_picture)
                                <div class="mb-4">
                                    <img src="{{ asset(Auth::user()->profile_picture) }}" class="w-20 h-20 rounded-full object-cover shadow-md border-4 border-white">
                                </div>
                            @endif
                            
                            <input id="profile_picture" name="profile_picture" type="file" accept="image/*"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 transition-colors cursor-pointer" />
                        </div>

                        <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200">
                            <label for="verification_document" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">
                                Valid ID / Brgy. Certificate <span class="normal-case font-medium text-slate-400">(Required for Verification)</span>
                            </label>
                            
                            @if(Auth::user()->verification_document)
                                <p class="inline-flex items-center gap-1.5 text-xs text-emerald-600 mb-4 font-bold bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-100 shadow-sm">
                                    <i class="fas fa-file-check"></i> Document uploaded — waiting for Admin review.
                                </p>
                            @endif
                            
                            <input id="verification_document" name="verification_document" type="file" accept="image/*,.pdf"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#36B3C9]/10 file:text-[#36B3C9] hover:file:bg-[#36B3C9] hover:file:text-white transition-colors cursor-pointer" />
                            <p class="text-xs font-medium text-slate-400 mt-3 flex items-center gap-2">
                                <i class="fas fa-info-circle text-[#36B3C9]"></i>
                                Uploading a new ID will set your status back to pending until approved.
                            </p>
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <button type="submit" class="bg-[#36B3C9] hover:bg-[#2da0b3] text-white px-8 py-3.5 rounded-xl font-bold text-sm shadow-lg shadow-cyan-500/20 transition-all hover:-translate-y-0.5 active:scale-95 tracking-wide">
                                Upload & Submit for Review
                            </button>
                            
                            @if (session('status') === 'Files uploaded successfully!')
                                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                                    class="text-xs font-bold text-emerald-500 bg-emerald-50 px-4 py-2 rounded-xl border border-emerald-100 shadow-sm flex items-center gap-1.5">
                                    <i class="fas fa-check"></i> Uploaded
                                </p>
                            @endif
                        </div>
                    </form>
                </section>
            </div>

            <div class="p-8 sm:p-10 bg-white shadow-sm sm:rounded-3xl border border-slate-100">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-8 sm:p-10 bg-white shadow-sm sm:rounded-3xl border border-slate-100">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-8 sm:p-10 bg-white shadow-sm sm:rounded-3xl border border-slate-100">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>