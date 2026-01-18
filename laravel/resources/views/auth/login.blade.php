<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Thirdynal Warehouse</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-dark-900 min-h-screen flex items-center justify-center p-4 selection:bg-gold-500 selection:text-white">
    
    <!-- Background Effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-gold-500/5 blur-[120px]"></div>
        <div class="absolute -bottom-[20%] -right-[10%] w-[50%] h-[50%] rounded-full bg-blue-600/5 blur-[120px]"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo Section -->
        <div class="text-center mb-8 animate-fade-in-down">
            <div class="inline-flex items-center justify-center p-1 rounded-2xl bg-gradient-to-br from-dark-700 to-dark-800 border border-dark-600 shadow-2xl mb-6">
                <img src="{{ asset('images/org_logo.jpg') }}" alt="Organization Logo" class="h-24 w-auto rounded-xl">
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight mb-2">Welcome Back</h1>
            <p class="text-slate-400">Sign in to your dashboard</p>
        </div>

        <!-- Login Card -->
        <div class="bg-dark-800/50 backdrop-blur-xl border border-dark-700 rounded-2xl p-8 shadow-xl animate-fade-in-up">
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-error-500/10 border border-error-500/20 flex items-start gap-3">
                    <i class="fas fa-circle-exclamation text-error-400 mt-0.5"></i>
                    <div class="text-sm text-error-400">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf
                
                <div class="space-y-2">
                    <label for="username" class="text-xs font-semibold uppercase tracking-wider text-slate-400 ml-1">Username</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-500 group-focus-within:text-gold-400 transition-colors"></i>
                        </div>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="w-full bg-dark-900/50 border border-dark-600 text-white text-sm rounded-xl focus:ring-2 focus:ring-gold-500/20 focus:border-gold-500 block w-full pl-11 p-3.5 placeholder-slate-600 transition-all" 
                            placeholder="Enter your username" 
                            value="{{ old('username') }}"
                            required 
                            autofocus
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="password" class="text-xs font-semibold uppercase tracking-wider text-slate-400 ml-1">Password</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-500 group-focus-within:text-gold-400 transition-colors"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="w-full bg-dark-900/50 border border-dark-600 text-white text-sm rounded-xl focus:ring-2 focus:ring-gold-500/20 focus:border-gold-500 block w-full pl-11 p-3.5 placeholder-slate-600 transition-all" 
                            placeholder="Enter your password" 
                            required
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" class="w-4 h-4 rounded border-dark-600 bg-dark-900 text-gold-500 focus:ring-gold-500/20 focus:ring-offset-0">
                        <label for="remember" class="ml-2 block text-sm text-slate-400 hover:text-slate-300 cursor-pointer transition-colors">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="w-full group relative flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-gradient-to-r from-gold-500 to-gold-600 hover:from-gold-400 hover:to-gold-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gold-500 shadow-lg shadow-gold-500/20 hover:shadow-gold-500/30 transition-all duration-200">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-arrow-right text-gold-200 group-hover:text-white transition-colors group-hover:translate-x-1 duration-200"></i>
                    </span>
                    Sign In
                </button>
            </form>
        </div>

        <p class="mt-8 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} Organization Name. All rights reserved.
        </p>
    </div>
</body>
</html>
