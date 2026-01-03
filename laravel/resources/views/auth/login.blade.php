<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Warehouse System</title>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-primary);
            margin: 0;
            padding: var(--space-4);
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            border: 1px solid var(--border-primary);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--space-8);
        }

        .login-header h1 {
            font-size: var(--text-2xl);
            font-weight: var(--font-bold);
            color: var(--text-primary);
            margin: 0 0 var(--space-2) 0;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: var(--text-sm);
            margin: 0;
        }

        .form-group {
            margin-bottom: var(--space-5);
        }

        .form-group label {
            display: block;
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }

        .form-group input {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-secondary);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: var(--text-base);
            transition: all var(--transition-fast);
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .btn-login {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            background: var(--accent-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--text-base);
            font-weight: var(--font-semibold);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .btn-login:hover {
            background: var(--accent-primary-hover);
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: var(--space-6);
            color: var(--text-muted);
            font-size: var(--text-sm);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--status-error);
            color: var(--status-error);
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-5);
            font-size: var(--text-sm);
        }

        .remember-group {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-5);
        }

        .remember-group input[type="checkbox"] {
            width: auto;
            accent-color: var(--accent-primary);
        }

        .remember-group label {
            margin: 0;
            font-size: var(--text-sm);
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Sign in to access the Warehouse System</p>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    @foreach ($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Enter username"
                        value="{{ old('username') }}"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter password"
                        required
                    >
                </div>

                <div class="remember-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn-login">Sign In</button>
            </form>


        </div>
    </div>
</body>
</html>
