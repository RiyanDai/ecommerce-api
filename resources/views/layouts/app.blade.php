<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - E-Commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white flex flex-col h-screen">
            <div class="p-4">
                <h1 class="text-2xl font-bold">E-Commerce Admin</h1>
            </div>
            <nav class="mt-8 flex-1">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-chart-line w-5 mr-3"></i>
                    Dashboard
                </a>
                <a href="{{ route('admin.products.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.products.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-box w-5 mr-3"></i>
                    Products
                </a>
                <a href="{{ route('admin.orders.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.orders.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-shopping-cart w-5 mr-3"></i>
                    Orders
                </a>
                <a href="{{ route('admin.stock-history.index') }}" class="flex items-center px-4 py-3 hover:bg-gray-700 {{ request()->routeIs('admin.stock-history.*') ? 'bg-gray-700' : '' }}">
                    <i class="fas fa-history w-5 mr-3"></i>
                    Stock History
                </a>
            </nav>
            <div class="p-4 border-t border-gray-700">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2 text-red-400 hover:bg-gray-700 rounded">
                        <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b flex-shrink-0">
                <div class="px-6 py-4">
                    <h2 class="text-2xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                </div>
            </header>

            <!-- Alerts -->
            <div class="flex-shrink-0">
                @if(session('success'))
                    <div class="mx-6 mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mx-6 mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif
            </div>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @yield('scripts')
</body>
</html>

