@php
    use App\Models\Proposal;
    use App\Models\Review;

    $steps = [
        1 => 'Pilih proposal & isi nomor registrasi',
        2 => 'Skor setiap aspek',
        3 => 'Catatan setiap aspek',
        4 => 'Rekomendasi',
    ];
    $canReview = $registrationReady && ! $existingReview;
@endphp

<x-layout title="Nilai Proposal">
    <p class="eyebrow mb-3">Reviewer</p>
    <h1 class="font-sans font-bold text-4xl sm:text-5xl leading-tight tracking-tight">Penilaian proposal</h1>

    {{-- Penanda alur sesuai flowchart: 1 → 2 → 3 → 4 --}}
    <ol class="mt-8 mb-10 grid grid-cols-2 md:grid-cols-4 gap-2 font-sans text-sm">
        @foreach ($steps as $number => $label)
            <li data-step-indicator="{{ $number }}"
                data-state="{{ $number === 1 ? ($canReview ? 'done' : 'active') : ($canReview && $number === 2 ? 'active' : 'todo') }}"
                class="group flex items-center gap-3 rounded-2xl border border-stone px-4 py-3 text-cloud-dark
                       data-[state=active]:border-slate-dark data-[state=active]:text-slate-dark data-[state=active]:bg-ivory-light
                       data-[state=done]:bg-oat-warm data-[state=done]:border-oat-warm data-[state=done]:text-slate-dark">
                <span class="grid place-items-center size-7 shrink-0 rounded-full border border-current text-xs font-semibold
                             group-data-[state=done]:bg-slate-dark group-data-[state=done]:text-ivory-light group-data-[state=done]:border-slate-dark">{{ $number }}</span>
                <span class="leading-tight">{{ $label }}</span>
            </li>
        @endforeach
    </ol>

    {{-- Langkah 1 --}}
    <form method="GET" action="{{ route('reviews.create') }}" class="card" data-validate>
        <input type="hidden" name="proposal" value="{{ $proposal->id }}">
        <p class="label">1. Proposal yang dinilai</p>
        <p class="text-xl leading-snug">{{ $proposal->title }}</p>

        <label for="registration_number" class="label mt-5">Nomor registrasi</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <input id="registration_number" name="registration_number" value="{{ $registrationNumber }}" required maxlength="30"
                   pattern="{{ Proposal::REGISTRATION_NUMBER_PATTERN }}" data-uppercase
                   data-pattern-message="Format harus PL{prodi}/{No}/{Bulan Romawi}/{Tahun}, contoh PLTI/001/XI/{{ now()->year }}."
                   placeholder="contoh: PLTI/001/{{ ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1] }}/{{ now()->year }}" class="input uppercase sm:max-w-sm" autocomplete="off"
                   @readonly($proposal->registration_number)>
            <button class="btn btn-secondary">Lanjutkan</button>
        </div>
        @if ($registrationError)
            <p class="error">{{ $registrationError }}</p>
        @endif
        <p class="mt-2 font-sans text-xs text-cloud-dark">Format PL{prodi}/{No}/{Bulan Romawi}/{Tahun}: prodi 2–5 huruf kapital, nomor 3 digit (001–999), bulan I–XII, tahun 4 digit. Langkah 2–4 terbuka setelah nomor valid. Bila proposal sudah punya nomor, nomor tersebut dipakai.</p>

        @if ($existingReview)
            <p class="mt-3 font-sans text-sm">
                Anda sudah menilai proposal ini.
                <a href="{{ route('proposals.show', $proposal) }}">Lihat hasil penilaian</a>
            </p>
        @endif
    </form>

    @if ($canReview)
        <div class="mt-6 rounded-3xl bg-manilla p-6 sm:p-8">
            <p class="eyebrow text-slate-medium">{{ $registrationNumber }}</p>
            <h2 class="mt-2 text-2xl sm:text-3xl leading-snug">{{ $proposal->title }}</h2>
            <p class="mt-3 text-lg leading-relaxed text-slate-medium line-clamp-4">{{ $proposal->summary }}</p>
            @if ($proposal->file_path)
                <a href="{{ route('proposals.file', $proposal) }}" class="mt-4 inline-block font-sans text-sm">Unduh berkas PDF</a>
            @endif
        </div>

        <form method="POST" action="{{ route('reviews.store') }}" class="mt-6" data-wizard data-validate @if ($errors->any()) data-show-all @endif>
            @csrf
            <input type="hidden" name="proposal_id" value="{{ $proposal->id }}">
            <input type="hidden" name="registration_number" value="{{ $registrationNumber }}">

            @if ($errors->any())
                <div class="mb-6 rounded-3xl border border-clay-deep px-6 py-4 font-sans text-sm text-clay-deep">
                    Periksa kembali isian Anda. {{ $errors->first() }}
                </div>
            @endif

            {{-- Langkah 2 --}}
            <section data-step class="card">
                <h3 class="font-sans font-semibold text-xl">2. Skor untuk setiap aspek substantif</h3>
                <p class="mt-1 font-sans text-sm text-cloud-dark">Skor wajib diisi untuk setiap aspek, berupa bilangan bulat 0–100. Nilai terisi otomatis: skor × bobot.</p>

                <div class="mt-6 divide-y divide-stone">
                    @foreach (Review::ASPECTS as $key => [$label, $weight])
                        <div class="py-5 grid gap-3 md:grid-cols-[1fr_auto] md:items-center">
                            <label for="score-{{ $key }}" class="text-lg leading-snug">
                                {{ $label }}
                                <span class="block font-sans text-sm text-cloud-dark">Bobot {{ $weight }}%</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input id="score-{{ $key }}" name="scores[{{ $key }}]" value="{{ old("scores.$key") }}" type="number" min="0" max="100" step="1" inputmode="numeric" required placeholder="Skor" aria-label="Skor {{ $label }}" data-weight="{{ $weight }}" data-value-target="value-{{ $key }}" class="input md:w-32" autocomplete="off">
                                <span class="font-sans text-sm text-cloud-dark">× {{ $weight }}% =</span>
                                <output id="value-{{ $key }}" for="score-{{ $key }}" aria-label="Nilai {{ $label }}" class="w-20 rounded-xl bg-oat-warm px-4 py-3 font-sans text-base text-right">–</output>
                            </div>
                            @error("scores.$key") <p class="error md:col-span-2">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    <div class="pt-5 flex items-center justify-end gap-3 font-sans">
                        <span class="font-semibold">Total nilai</span>
                        <output id="value-total" aria-live="polite" class="w-20 rounded-xl bg-slate-dark text-ivory-light px-4 py-3 text-right font-semibold">–</output>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" data-next class="btn btn-secondary">Lanjut ke catatan →</button>
                </div>
            </section>

            {{-- Langkah 3 --}}
            <section data-step class="card mt-6">
                <h3 class="font-sans font-semibold text-xl">3. Catatan untuk setiap aspek</h3>
                <p class="mt-1 font-sans text-sm text-cloud-dark">Wajib, 10–2.000 karakter per aspek. Tuliskan alasan skor serta saran perbaikan.</p>

                <div class="mt-6 space-y-6">
                    @foreach (Review::ASPECTS as $key => [$label])
                        <div>
                            <label for="notes-{{ $key }}" class="label">{{ $label }}</label>
                            <textarea id="notes-{{ $key }}" name="notes[{{ $key }}]" rows="3" required minlength="10" maxlength="2000"
                                      class="input font-serif text-lg leading-relaxed">{{ old("notes.$key") }}</textarea>
                            @error("notes.$key") <p class="error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-between">
                    <button type="button" data-prev class="btn btn-ghost">← Kembali</button>
                    <button type="button" data-next class="btn btn-secondary">Lanjut ke rekomendasi →</button>
                </div>
            </section>

            {{-- Langkah 4 --}}
            <section data-step class="card mt-6">
                <label for="recommendation_note" class="label text-xl font-semibold">4. Rekomendasi</label>
                <textarea id="recommendation_note" name="recommendation_note" rows="4" required minlength="10" maxlength="2000"
                          class="input font-serif text-lg leading-relaxed">{{ old('recommendation_note') }}</textarea>
                <p class="mt-1 font-sans text-xs text-cloud-dark">Wajib, 10–2.000 karakter.</p>
                @error('recommendation_note') <p class="error">{{ $message }}</p> @enderror

                <div class="mt-8 flex justify-between">
                    <button type="button" data-prev class="btn btn-ghost">← Kembali</button>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </section>
        </form>
    @endif
</x-layout>
