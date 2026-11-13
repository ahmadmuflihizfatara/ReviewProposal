<x-layout title="Daftar">
    <div class="grid gap-10 lg:grid-cols-2 lg:gap-16 items-start">
        <div class="lg:pt-8">
            <p class="eyebrow mb-4">Buat akun</p>
            <h1 class="font-sans font-bold text-4xl sm:text-5xl lg:text-[61px] leading-[1.1] tracking-tight">
                Pilih peran Anda: mengajukan, menyetujui, atau menilai.
            </h1>
            <dl class="mt-8 space-y-5 text-lg leading-relaxed max-w-xl">
                <div>
                    <dt class="font-sans font-semibold text-base">Pengusul</dt>
                    <dd class="text-slate-medium">Mengunggah proposal, menerima nomor registrasi, dan membaca hasil penilaian.</dd>
                </div>
                <div>
                    <dt class="font-sans font-semibold text-base">Reviewer</dt>
                    <dd class="text-slate-medium">Menilai proposal berdasarkan nomor registrasi, memberi skor, catatan, rekomendasi, dan TTE.</dd>
                </div>
                <div>
                    <dt class="font-sans font-semibold text-base">Kaprodi</dt>
                    <dd class="text-slate-medium">Menyetujui proposal yang diajukan sebelum diteruskan ke Sekretaris.</dd>
                </div>
                <div>
                    <dt class="font-sans font-semibold text-base">Sekretaris</dt>
                    <dd class="text-slate-medium">Mengelola anggaran tersedia dan menyetujui proposal sesuai anggaran.</dd>
                </div>
            </dl>
        </div>

        <form method="POST" action="{{ route('register') }}" data-validate class="card space-y-6">
            @csrf
            <h2 class="font-serif text-2xl">Daftar</h2>

            <fieldset>
                <legend class="label">Peran</legend>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($roles as $role)
                        <label class="flex items-center gap-3 rounded-xl border border-stone px-4 py-3 font-sans cursor-pointer has-[:checked]:border-slate-dark has-[:checked]:bg-oat-warm">
                            <input type="radio" name="role" value="{{ $role->value }}" required class="accent-slate-dark" @checked(old('role', 'issuer') === $role->value)>
                            {{ $role->label() }}
                        </label>
                    @endforeach
                </div>
                @error('role') <p class="error">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="name" class="label">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name') }}" required minlength="3" maxlength="100" pattern="{{ \App\Http\Controllers\AuthController::NAME_PATTERN }}" data-pattern-message="Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung." autocomplete="name" class="input">
                <p class="mt-1 font-sans text-xs text-cloud-dark">3–100 karakter: huruf, spasi, titik, apostrof, tanda hubung.</p>
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="input">
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Kata sandi</label>
                <input id="password" name="password" type="password" required minlength="8" maxlength="64" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).+" data-pattern-message="Kata sandi harus memuat huruf besar, huruf kecil, dan angka." autocomplete="new-password" class="input">
                <p class="mt-1 font-sans text-xs text-cloud-dark">8–64 karakter, memuat huruf besar, huruf kecil, dan angka.</p>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">Konfirmasi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required data-match="password" autocomplete="new-password" class="input">
            </div>

            <button class="btn btn-primary w-full">Buat akun</button>

            <p class="font-sans text-sm text-center text-cloud-dark">
                Sudah punya akun? <a href="{{ route('login') }}" class="text-slate-dark">Masuk</a>
            </p>
        </form>
    </div>
</x-layout>
