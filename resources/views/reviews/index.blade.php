<x-layout title="Dasbor Reviewer">
    <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
        <div>
            <p class="eyebrow mb-3">Reviewer</p>
            <h1 class="font-sans font-bold text-4xl sm:text-5xl leading-tight tracking-tight">Dasbor penilaian</h1>
        </div>
    </div>

    <div class="grid gap-10 lg:grid-cols-2">
        <section>
            <h2 class="font-sans font-semibold text-xl mb-4">Menunggu penilaian Anda <span class="text-cloud-dark font-normal">({{ $pendingProposals->count() }})</span></h2>
            <div class="rounded-3xl bg-oat-warm p-2 sm:p-3 space-y-2">
                @forelse ($pendingProposals as $proposal)
                    <div class="rounded-2xl bg-ivory-light px-5 py-4 flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-sans text-xs font-semibold tracking-wide">{{ $proposal->registrationLabel() }} · <span class="font-normal text-cloud-dark">{{ $proposal->approvalLabel() }}</span></p>
                            <p class="mt-1 text-lg leading-snug truncate">{{ $proposal->title }}</p>
                        </div>
                        <a href="{{ route('reviews.create', ['proposal' => $proposal->id]) }}" class="btn btn-secondary shrink-0 px-4 py-2 text-sm">Nilai</a>
                    </div>
                @empty
                    <p class="px-5 py-6 text-lg text-slate-medium">Semua proposal sudah Anda nilai.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="font-sans font-semibold text-xl mb-4">Sudah dinilai <span class="text-cloud-dark font-normal">({{ $reviews->count() }})</span></h2>
            <div class="space-y-3">
                @forelse ($reviews as $review)
                    <a href="{{ route('proposals.show', $review->proposal) }}" class="card no-underline block !p-5 hover:border-slate-dark transition-colors">
                        <div class="flex items-center justify-between gap-3 font-sans text-xs">
                            <span class="font-semibold tracking-wide">{{ $review->proposal->registrationLabel() }}</span>
                                                    </div>
                        <p class="mt-2 text-lg leading-snug">{{ $review->proposal->title }}</p>
                        <p class="mt-1 font-sans text-sm text-cloud-dark">{{ $review->signed_at->translatedFormat('d M Y') }}</p>
                    </a>
                @empty
                    <p class="text-lg text-slate-medium">Belum ada penilaian.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layout>
