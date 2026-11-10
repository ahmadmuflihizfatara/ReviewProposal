<x-layout title="Persetujuan Kaprodi">
    <div class="mb-12">
        <p class="eyebrow mb-3">Kaprodi</p>
        <h1 class="font-sans font-bold text-4xl sm:text-5xl leading-tight tracking-tight">Persetujuan proposal</h1>
        <p class="mt-4 text-lg text-slate-medium">Proposal yang Anda setujui diteruskan ke Sekretaris untuk persetujuan anggaran.</p>
    </div>

    <div class="grid gap-10 lg:grid-cols-2">
        <section>
            <h2 class="font-sans font-semibold text-xl mb-4">Menunggu persetujuan <span class="text-cloud-dark font-normal">({{ $pendingProposals->count() }})</span></h2>
            <div class="rounded-3xl bg-oat-warm p-2 sm:p-3 space-y-2">
                @forelse ($pendingProposals as $proposal)
                    <div class="rounded-2xl bg-ivory-light px-5 py-4 flex items-center justify-between gap-4">
                        <a href="{{ route('proposals.show', $proposal) }}" class="min-w-0 no-underline">
                            <p class="font-sans text-xs font-semibold tracking-wide">{{ $proposal->issuer->name }} · {{ $proposal->budgetLabel() }}</p>
                            <p class="mt-1 text-lg leading-snug truncate">{{ $proposal->title }}</p>
                        </a>
                        <form method="POST" action="{{ route('kaprodi.approve', $proposal) }}" class="shrink-0">
                            @csrf
                            <button class="btn btn-secondary px-4 py-2 text-sm">Setujui</button>
                        </form>
                    </div>
                @empty
                    <p class="px-5 py-6 text-lg text-slate-medium">Tidak ada proposal yang menunggu persetujuan.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="font-sans font-semibold text-xl mb-4">Sudah disetujui <span class="text-cloud-dark font-normal">({{ $approvedProposals->count() }})</span></h2>
            <div class="space-y-3">
                @forelse ($approvedProposals as $proposal)
                    <a href="{{ route('proposals.show', $proposal) }}" class="card no-underline block !p-5 hover:border-slate-dark transition-colors">
                        <div class="flex items-center justify-between gap-3 font-sans text-xs">
                            <span class="font-semibold tracking-wide">{{ $proposal->budgetLabel() }}</span>
                            <span class="bg-oat-warm rounded-full px-3 py-1">{{ $proposal->approvalLabel() }}</span>
                        </div>
                        <p class="mt-2 text-lg leading-snug">{{ $proposal->title }}</p>
                        <p class="mt-1 font-sans text-sm text-cloud-dark">Disetujui {{ $proposal->kaprodi_approved_at->translatedFormat('d M Y') }}</p>
                    </a>
                @empty
                    <p class="text-lg text-slate-medium">Belum ada proposal yang disetujui.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layout>
