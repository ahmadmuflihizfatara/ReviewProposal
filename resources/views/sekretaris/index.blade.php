<x-layout title="Persetujuan Sekretaris">
    <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
        <div>
            <p class="eyebrow mb-3">Sekretaris</p>
            <h1 class="font-sans font-bold text-4xl sm:text-5xl leading-tight tracking-tight">Anggaran &amp; persetujuan</h1>
            <p class="mt-4 text-lg text-slate-medium">Proposal yang disetujui mengurangi anggaran tersedia dan siap dinilai reviewer.</p>
        </div>
    </div>

    <form data-validate method="POST" action="{{ route('sekretaris.budget') }}" class="card mb-12 flex flex-col sm:flex-row sm:items-end gap-4">
        @csrf
        @method('PUT')
        <div class="flex-1">
            <p class="label">Anggaran tersedia</p>
            <p class="text-3xl font-sans font-bold tracking-tight">{{ $budget->label() }}</p>
        </div>
        <div class="flex-1">
            <label for="amount" class="label">Ubah anggaran tersedia (Rp)</label>
            <input id="amount" name="amount" type="number" min="0" step="1" inputmode="numeric" value="{{ $budget->amount }}" required class="input">
        </div>
        <button class="btn btn-primary shrink-0">Simpan anggaran</button>
    </form>

    <div class="grid gap-10 lg:grid-cols-2">
        <section>
            <h2 class="font-sans font-semibold text-xl mb-4">Disetujui Kaprodi, menunggu Anda <span class="text-cloud-dark font-normal">({{ $pendingProposals->count() }})</span></h2>
            <div class="rounded-3xl bg-oat-warm p-2 sm:p-3 space-y-2">
                @forelse ($pendingProposals as $proposal)
                    @php($isAffordable = $proposal->budget <= $budget->amount)
                    <div class="rounded-2xl bg-ivory-light px-5 py-4 flex items-center justify-between gap-4">
                        <a href="{{ route('proposals.show', $proposal) }}" class="min-w-0 no-underline">
                            <p class="font-sans text-xs font-semibold tracking-wide">{{ $proposal->issuer->name }} · {{ $proposal->budgetLabel() }}</p>
                            <p class="mt-1 text-lg leading-snug truncate">{{ $proposal->title }}</p>
                            @unless ($isAffordable)
                                <p class="mt-1 font-sans text-sm text-cloud-dark">Anggaran tersedia tidak mencukupi.</p>
                            @endunless
                        </a>
                        <form method="POST" action="{{ route('sekretaris.approve', $proposal) }}" class="shrink-0">
                            @csrf
                            <button class="btn btn-secondary px-4 py-2 text-sm" @disabled(! $isAffordable)>Setujui</button>
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
                            <span class="bg-oat-warm rounded-full px-3 py-1">{{ $proposal->registrationLabel() }}</span>
                        </div>
                        <p class="mt-2 text-lg leading-snug">{{ $proposal->title }}</p>
                        <p class="mt-1 font-sans text-sm text-cloud-dark">Disetujui {{ $proposal->sekretaris_approved_at->translatedFormat('d M Y') }}</p>
                    </a>
                @empty
                    <p class="text-lg text-slate-medium">Belum ada proposal yang disetujui.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layout>
