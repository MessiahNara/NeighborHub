<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>NeighborHub | Sign Up</title>
</head>
<body class="bg-white min-h-screen flex items-center justify-center p-6 font-sans">

    <div class="w-full max-w-sm">
        <div class="mb-10">
            <p class="text-[#36B3C9] text-xl font-bold mb-0">Join Us At</p>
            <h1 class="text-5xl font-black tracking-tighter text-black mt-[-5px]">NeighborHub</h1>
        </div>

        <div class="bg-[#F3F3F7] rounded-sm overflow-hidden shadow-sm">
            <div class="flex">
                <a href="{{ route('login') }}" class="flex-1 py-4 text-center bg-[#00BCD4] hover:bg-[#00acc1] transition">
                    <span class="text-white font-black tracking-widest uppercase text-sm">Sign In</span>
                </a>
                <div class="flex-1 py-4 text-center bg-white border-b-2 border-[#36B3C9]">
                    <span class="text-[#36B3C9] font-black tracking-widest uppercase text-sm">Sign Up</span>
                </div>
            </div>

            <form method="POST" action="{{ route('register') }}" class="p-8 space-y-5">
                @csrf
                
                <div>
                    <label class="block text-gray-500 font-bold text-xs uppercase mb-1">Full Name</label>
                    <input type="text" name="name" :value="old('name')" class="w-full p-3 bg-[#D9D9D9] border-none focus:ring-2 focus:ring-[#36B3C9] outline-none transition" required autofocus autocomplete="name">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <label class="block text-gray-500 font-bold text-xs uppercase mb-1">Email</label>
                    <input type="email" name="email" :value="old('email')" class="w-full p-3 bg-[#D9D9D9] border-none focus:ring-2 focus:ring-[#36B3C9] outline-none transition" required autocomplete="username">
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div>
                    <label class="block text-gray-500 font-bold text-xs uppercase mb-1">Password</label>
                    <input type="password" name="password" class="w-full p-3 bg-[#D9D9D9] border-none focus:ring-2 focus:ring-[#36B3C9] outline-none transition" required autocomplete="new-password">
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <div>
                    <label class="block text-gray-500 font-bold text-xs uppercase mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full p-3 bg-[#D9D9D9] border-none focus:ring-2 focus:ring-[#36B3C9] outline-none transition" required autocomplete="new-password">
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-[#8A8A9D] hover:bg-[#7a7a8c] text-white font-black py-4 uppercase text-xs tracking-widest transition shadow-lg active:scale-95">
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>