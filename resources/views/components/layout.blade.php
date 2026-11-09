@props(['title' => null])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}Review Proposal</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
    <header class="border-b border-stone">
        <nav class="mx-auto max-w-6xl px-4 sm:px-6 h-16 flex items-center justify-between gap-4 font-sans">
            <a href="{{ route('dashboard') }}" class="no-underline text-xs font-bold uppercase tracking-[0.16em]">Review Proposal</a>

            @auth
                <div class="flex items-center gap-1 sm:gap-4 text-sm">
                    @can('review')
                        <a href="{{ route('reviews.index') }}" class="no-underline text-cloud-dark hover:text-slate-dark">Dasbor</a>
                    @else
                        <a href="{{ route('proposals.index') }}" class="no-underline text-cloud-dark hover:text-slate-dark">Proposal Saya</a>
                        <a href="{{ route('proposals.create') }}" class="no-underline text-cloud-dark hover:text-slate-dark">Ajukan</a>
                    @endcan
                    <span class="hidden md:inline text-cloud-dark">·</span>
                    <span class="hidden md:inline">{{ auth()->user()->name }} <span class="text-cloud-dark">({{ auth()->user()->role->label() }})</span></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-ghost text-sm py-2">Keluar</button>
                    </form>
                </div>
            @endauth
        </nav>
    </header>

    <main class="flex-1 mx-auto w-full max-w-6xl px-4 sm:px-6 py-10 sm:py-16">
        @if (session('status'))
            <div class="mb-8 rounded-3xl bg-manilla px-6 py-4 font-sans text-[15px]" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="bg-slate-dark text-ivory-light">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-8 font-sans text-xs flex flex-wrap justify-between gap-2">
            <span class="font-semibold uppercase tracking-[0.16em]">Review Proposal</span>
            <span class="text-cloud-medium">Isi nomor registrasi → skor → catatan → rekomendasi &amp; TTE</span>
        </div>
    </footer>
</body>
</html>
