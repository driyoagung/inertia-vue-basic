<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Todo App')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 font-sans antialiased">
    <nav class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-3">
            <a href="{{ route('todos.index') }}" class="text-lg font-bold text-gray-800">
                Todo App
            </a>
            <div class="flex gap-3 text-sm">
                <a href="{{ route('todos.index') }}"
                   class="text-blue-600 hover:underline {{ request()->routeIs('todos.index') ? 'font-semibold' : '' }}">
                    Blade
                </a>
                <a href="{{ route('todo.inertia') }}"
                   class="text-blue-600 hover:underline {{ request()->routeIs('todo.inertia') ? 'font-semibold' : '' }}">
                    Inertia
                </a>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-3xl px-4 py-6">
        @yield('content')
    </main>
</body>
</html>
