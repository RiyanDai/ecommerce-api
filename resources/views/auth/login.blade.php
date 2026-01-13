<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - E-Commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-blue-600 mb-2">
                    <i class="fas fa-shopping-bag"></i> E-Commerce
                </h1>
                <h2 class="text-2xl font-bold text-gray-800">Customer Login</h2>
            </div>
            
            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-envelope mr-2"></i>Email
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           required autofocus>
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input type="password" name="password" id="password" 
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                           required>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">Don't have an account? 
                    <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800 font-semibold">Register here</a>
                </p>
            </div>

            <div class="mt-4 text-center">
                <a href="{{ route('admin.login') }}" class="text-sm text-gray-500 hover:text-gray-700">
                    <i class="fas fa-user-shield mr-1"></i>Admin Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>

