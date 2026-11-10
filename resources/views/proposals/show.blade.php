<x-layout :title="$proposal->title">
    <article class="rounded-3xl bg-manilla p-6 sm:p-12">
        <p class="eyebrow text-slate-medium">{{ $proposal->registrationLabel() }}</p>
        <h1 class="mt-4 text-3xl sm:text-5xl leading-[1.1]">{{ $proposal->title }}</h1>
        <p class="mt-4 font-sans text-sm text-slate-medium">
            Oleh {{ $proposal->issuer->name }} · diajukan {{ $proposal->created_at->translatedFormat('d F Y') }}
        </p>
        <p class="mt-2 font-sans text-sm text-slate-medium">
            Anggaran diajukan <strong>{{ $proposal->budgetLabel() }}</strong> · {{ $proposal->approvalLabel() }}
        </p>
        <p class="mt-8 text-lg sm:text-xl leading-relaxed whitespace-pre-line max-w-3xl">{{ $proposal->summary }}</p>
        <div class="mt-8 flex flex-wrap gap-3">
            @if ($proposal->file_path)
                <a href="{{ route('proposals.file', $proposal) }}" class="btn btn-secondary">Unduh berkas PDF</a>
            @endif
            @can('review')
                @if ($reviews->isEmpty())
                    <a href="{{ route('reviews.create', ['proposal' => $proposal->id]) }}" class="btn btn-primary">Nilai proposal ini</a>
                @endif
            @endcan
        </div>
    </article>

    <section class="mt-14">
        <h2 class="font-sans font-bold text-2xl sm:text-3xl tracking-tight mb-6">Hasil penilaian</h2>

        @forelse ($reviews as $review)
            @include('reviews._result', ['review' => $review])
        @empty
            <p class="text-lg text-slate-medium">Belum ada penilaian untuk proposal ini.</p>
        @endforelse
    </section>
</x-layout>
