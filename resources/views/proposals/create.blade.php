<x-layout title="Ajukan Proposal">
    <div class="max-w-3xl">
        <p class="eyebrow mb-3">Pengusul</p>
        <h1 class="font-sans font-bold text-4xl sm:text-5xl leading-tight tracking-tight">Ajukan proposal</h1>
        <p class="mt-4 text-lg text-slate-medium">Setelah diajukan, proposal disetujui Kaprodi lalu Sekretaris sesuai anggaran yang tersedia, kemudian dinilai reviewer.</p>

        <form data-validate method="POST" action="{{ route('proposals.store') }}" enctype="multipart/form-data" class="card mt-10 space-y-6">
            @csrf

            <div>
                <label for="title" class="label">Judul proposal</label>
                <input id="title" name="title" value="{{ old('title') }}" required maxlength="255" class="input">
                @error('title') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="summary" class="label">Ringkasan</label>
                <textarea id="summary" name="summary" rows="6" required maxlength="5000" class="input font-serif text-lg leading-relaxed">{{ old('summary') }}</textarea>
                @error('summary') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="budget" class="label">Anggaran yang diajukan (Rp)</label>
                <input id="budget" name="budget" type="number" min="0" step="1" inputmode="numeric" value="{{ old('budget') }}" required class="input">
                @error('budget') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="file" class="label">Berkas proposal (PDF, maks. 10 MB)</label>
                <input id="file" name="file" type="file" accept="application/pdf" data-max-size="10485760" class="input file:mr-4 file:border-0 file:bg-oat-warm file:px-4 file:py-2 file:rounded-lg file:font-sans">
                @error('file') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button class="btn btn-primary">Ajukan proposal</button>
                <a href="{{ route('proposals.index') }}" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
</x-layout>
