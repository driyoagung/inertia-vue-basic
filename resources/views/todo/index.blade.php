@extends('layouts.todo')

@section('title', 'Todo App (Blade)')

@section('content')
    <div class="rounded bg-white p-6 shadow">
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold">Todo App
                <span class="ml-2 rounded bg-orange-100 px-2 py-1 text-xs font-medium text-orange-700">
                    Blade (Server-Side)
                </span>
            </h1>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded bg-green-100 px-3 py-2 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- FORM TAMBAH TODO: @csrf + old() + @error --}}
        <form action="{{ route('todos.store') }}" method="POST" class="mb-4 flex gap-2">
            @csrf
            <input
                type="text"
                name="text"
                value="{{ old('text') }}"
                placeholder="Apa yang ingin dilakukan?"
                class="@error('text') border-red-500 @enderror flex-1 rounded border px-3 py-2"
                autofocus
            >
            <button type="submit" class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                Tambah
            </button>
        </form>
        @error('text')
            <p class="-mt-2 mb-4 text-sm text-red-500">{{ $message }}</p>
        @enderror

        {{-- FILTER: link dengan query string (server-side filter) --}}
        <div class="mb-4 flex gap-2 text-sm">
            @foreach (['all' => 'Semua', 'active' => 'Aktif', 'done' => 'Selesai'] as $key => $label)
                <a
                    href="{{ route('todos.index', ['filter' => $key]) }}"
                    class="{{ $filter === $key ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }} rounded px-3 py-1"
                >
                    {{ $label }}
                </a>
            @endforeach

            @if ($stats['done'] > 0)
                <form action="{{ route('todos.clearDone') }}" method="POST" class="ml-auto">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded bg-red-500 px-3 py-1 text-white hover:bg-red-600">
                        Hapus selesai ({{ $stats['done'] }})
                    </button>
                </form>
            @endif
        </div>

        {{-- LIST TODO: @forelse + @empty --}}
        @forelse ($todos as $todo)
            <div class="flex items-center gap-2 border-t py-2">
                {{-- Checkbox toggle: form terpisah yang auto-submit via JS onclick --}}
                <form action="{{ route('todos.update', $todo) }}" method="POST" class="contents">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="done" value="{{ $todo->done ? 0 : 1 }}">
                    <button
                        type="submit"
                        class="flex items-center"
                        title="Toggle selesai"
                    >
                        @if ($todo->done)
                            <span class="flex h-5 w-5 items-center justify-center rounded border-2 border-green-500 bg-green-500 text-xs font-bold text-white">✓</span>
                        @else
                            <span class="h-5 w-5 rounded border-2 border-gray-300"></span>
                        @endif
                    </button>
                </form>

                {{-- Teks todo dengan class conditional --}}
                <span class="{{ $todo->done ? 'text-gray-400 line-through' : '' }} flex-1">
                    {{ $todo->text }}
                </span>

                <small class="text-xs text-gray-400">
                    {{ $todo->created_at->diffForHumans() }}
                </small>

                {{-- Hapus --}}
                <form action="{{ route('todos.destroy', $todo) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:underline">
                        Hapus
                    </button>
                </form>
            </div>
        @empty
            <p class="py-8 text-center text-gray-400">
                Tidak ada todo.
            </p>
        @endforelse

        {{-- STATS --}}
        <div class="mt-4 border-t pt-4 text-sm text-gray-500">
            Total: <strong>{{ $stats['total'] }}</strong> |
            Aktif: <strong>{{ $stats['active'] }}</strong> |
            Selesai: <strong>{{ $stats['done'] }}</strong>
        </div>
    </div>

    <div class="mt-4 rounded border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
        <strong>Catatan:</strong> Versi Blade ini melakukan <em>full page reload</em> setiap aksi (tambah, toggle, hapus). 
        Bandingkan UX-nya dengan versi <a href="{{ route('todo.inertia') }}" class="underline">Inertia</a> di mana UI update reaktif tanpa reload.
    </div>
@endsection
