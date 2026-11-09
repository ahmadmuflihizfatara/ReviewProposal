<x-layout title="Masuk">
    <div class="grid gap-10 lg:grid-cols-2 lg:gap-16 items-start">
        <div class="lg:pt-8">
            <p class="eyebrow mb-4">Sistem penilaian proposal</p>
            <h1 class="font-sans font-bold text-4xl sm:text-5xl lg:text-[61px] leading-[1.1] tracking-tight">
                Nilai proposal dengan tertib, dari nomor registrasi sampai tanda tangan.
            </h1>
            <p class="mt-6 text-lg sm:text-xl leading-relaxed text-slate-medium max-w-xl">
                Pengusul mengajukan proposal dan mendapat nomor registrasi. Reviewer mengisi skor dan catatan untuk setiap aspek, lalu menutup penilaian dengan rekomendasi dan TTE.
            </p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="card space-y-6">
            @csrf
            <h2 class="font-serif text-2xl">Masuk</h2>

            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="text" value="{{ old('email') }}" required autofocus autocomplete="email" class="input">
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" class="input">
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 font-sans text-sm">
                <input type="checkbox" name="remember" class="accent-slate-dark"> Ingat saya
            </label>

            <button class="btn btn-primary w-full">Masuk</button>

            <p class="font-sans text-sm text-center text-cloud-dark">
                Belum punya akun? <a href="{{ route('register') }}" class="text-slate-dark">Daftar</a>
            </p>
        </form>
    </div>
</x-layout>
