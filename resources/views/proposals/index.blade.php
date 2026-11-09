<x-layout title="Proposal Saya">
    <div class="flex flex-wrap items-end justify-between gap-6 mb-10">
        <div>
            <p class="eyebrow mb-3">Pengusul</p>
            <h1 class="font-sans font-bold text-4xl sm:text-5xl leading-tight tracking-tight">Proposal saya</h1>
        </div>
        <a href="{{ route('proposals.create') }}" class="btn btn-primary">Ajukan proposal</a>
    </div>

    @forelse ($proposals as $proposal)
        @if ($loop->first) <div class="grid gap-4 md:grid-cols-2"> @endif
        <a href="{{ route('proposals.show', $proposal) }}" class="card no-underline block hover:border-slate-dark transition-colors">
            <div class="flex items-center justify-between gap-3 font-sans text-xs">
                <span class="font-semibold tracking-wide">{{ $proposal->registrationLabel() }}</span>
                <span class="{{ $proposal->reviews_count ? 'bg-oat-warm' : 'border border-stone' }} rounded-full px-3 py-1">
                    {{ $proposal->reviews_count ? "Dinilai {$proposal->reviews_count} reviewer" : 'Menunggu penilaian' }}
                </span>
            </div>
            <h2 class="mt-4 text-xl leading-snug">{{ $proposal->title }}</h2>
            <p class="mt-2 font-sans text-sm text-cloud-dark">Diajukan {{ $proposal->created_at->translatedFormat('d F Y') }}</p>
        </a>
        @if ($loop->last) </div> @endif
    @empty
        <div class="rounded-3xl bg-manilla p-10 sm:p-14">
            <h2 class="text-2xl">Belum ada proposal.</h2>
            <p class="mt-3 text-lg text-slate-medium">Ajukan proposal pertama Anda untuk dinilai reviewer.</p>
        </div>
    @endforelse
</x-layout>
