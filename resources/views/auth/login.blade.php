<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>NeighborHub | Login</title>
</head>
<body class="bg-white min-h-screen flex items-center justify-center p-6 font-sans">

    <div class="w-full max-w-sm">
        <div class="mb-10">
            <p class="text-[#36B3C9] text-xl font-bold mb-0">Welcome To</p>
            <h1 class="text-5xl font-black tracking-tighter text-black mt-[-5px]">NeighborHub</h1>
        </div>

        <div class="bg-[#F3F3F7] rounded-sm overflow-hidden shadow-sm">
            <div class="flex">
                <div class="flex-1 py-4 text-center bg-[#00BCD4]">
                    <span class="text-[#fcfcfc] font-black tracking-widest uppercase text-sm">Sign In</span>
                </div>
                <a href="{{ route('register') }}" class="flex-1 py-4 text-center bg-[#8A8A9D] hover:bg-[#7a7a8c] transition">
                    <span class="text-white font-black tracking-widest uppercase text-sm">Sign Up</span>
                </a>
            </div>

            <form method="POST" action="{{ route('login') }}" class="p-8 space-y-6">
                @csrf
                <div>
                    <label class="block text-gray-500 font-bold text-xs uppercase mb-2">Email</label>
                    <input type="email" name="email" class="w-full p-3 bg-[#D9D9D9] border-none focus:ring-2 focus:ring-[#36B3C9] outline-none transition" required>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <label class="block text-gray-500 font-bold text-xs uppercase mb-2">Password</label>
                    <input type="password" name="password" class="w-full p-3 bg-[#D9D9D9] border-none focus:ring-2 focus:ring-[#36B3C9] outline-none transition" required>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-[#8A8A9D] hover:bg-[#00acc1] text-white font-black py-3 uppercase text-xs tracking-widest transition shadow-lg">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>