@php
    use App\Models\Budget;
    use App\Models\Proposal;
    use App\Models\Review;

    // ponytail: tabel ini ditulis tangan; ubah bersamaan dengan aturan validate() di controller.
    $sections = [
        'Akun: daftar & masuk' => [
            ['Nama lengkap', 'Wajib. 3–100 karakter. Hanya huruf (termasuk beraksen), spasi, titik, apostrof, dan tanda hubung.', 'Ahmad Muflih'],
            ['Email', 'Wajib. Format alamat email yang valid, maks. 255 karakter, belum pernah terdaftar. Disimpan dalam huruf kecil.', 'nama@kampus.ac.id'],
            ['Kata sandi', 'Wajib. 8–64 karakter, memuat minimal satu huruf besar, satu huruf kecil, dan satu angka.', 'Rahasia123'],
            ['Konfirmasi kata sandi', 'Wajib saat daftar. Harus sama persis dengan kata sandi.', '—'],
            ['Peran', 'Wajib. Salah satu dari: Pengusul, Reviewer, Kaprodi, Sekretaris.', 'Pengusul'],
            ['Masuk (login)', 'Email wajib dan berformat email; kata sandi wajib. Maks. 6 percobaan per menit.', '—'],
        ],
        'Pengajuan proposal (Pengusul)' => [
            ['Judul', 'Wajib. 10–255 karakter.', 'Sistem Irigasi Pintar Berbasis IoT'],
            ['Ringkasan', 'Wajib. 50–5.000 karakter.', '—'],
            ['Anggaran', 'Wajib. Bilangan bulat tanpa titik/koma, '.Budget::rupiah(Proposal::MIN_BUDGET).' – '.Budget::rupiah(Proposal::MAX_BUDGET).'.', '15000000'],
            ['Berkas proposal', 'Wajib. Format PDF (application/pdf), ukuran maks. 10 MB.', 'proposal.pdf'],
        ],
        'Anggaran tersedia (Sekretaris)' => [
            ['Anggaran tersedia', 'Wajib. Bilangan bulat '.Budget::rupiah(0).' – '.Budget::rupiah(Budget::MAX_AMOUNT).'.', '100000000'],
        ],
        'Penilaian proposal (Reviewer)' => [
            ['Nomor registrasi', 'Wajib bila proposal belum bernomor. Format PL{prodi}/{No}/{Bulan}/{Tahun}: prodi 2–5 huruf kapital, nomor 3 digit 001–999, bulan angka Romawi I–XII, tahun 4 digit (1900–2099). Huruf kecil otomatis dikapitalkan. Harus unik.', 'PLTI/001/XI/2026'],
            ['Skor (tiap aspek)', 'Wajib untuk '.count(Review::ASPECTS).' aspek. Bilangan bulat 0–100.', '85'],
            ['Nilai (tiap aspek)', 'Tidak diisi manual. Terisi otomatis: skor × bobot aspek, dibulatkan 2 desimal. Total nilai = jumlah nilai semua aspek (0–100).', '85 × 20% = 17'],
            ['Catatan (tiap aspek)', 'Wajib untuk setiap aspek. 10–2.000 karakter.', 'Metode sudah jelas, perlu jadwal rinci.'],
            ['Rekomendasi', 'Wajib. 10–2.000 karakter.', 'Layak didanai dengan perbaikan minor.'],
        ],
    ];

    $flow = [
        ['Pengusul', 'Mengajukan proposal beserta anggaran dan berkas PDF.'],
        ['Kaprodi', 'Menyetujui proposal. Tanpa persetujuan ini Sekretaris tidak bisa menyetujui.'],
        ['Sekretaris', 'Menyetujui proposal yang sudah disetujui Kaprodi, bila anggaran tersedia mencukupi. Anggaran tersedia berkurang sebesar anggaran proposal.'],
        ['Reviewer', 'Hanya bisa menilai proposal yang sudah disetujui Kaprodi dan Sekretaris, dengan langkah berurutan: 1 nomor registrasi → 2 skor & nilai → 3 catatan → 4 rekomendasi. Satu reviewer menilai satu proposal sekali.'],
    ];
@endphp

<x-layout title="Dokumentasi">
    <div class="max-w-4xl">
        <p class="eyebrow mb-3">Dokumentasi</p>
        <h1 class="font-sans font-bold text-4xl sm:text-5xl leading-tight tracking-tight">Aturan input &amp; alur aplikasi</h1>
        <p class="mt-4 text-lg text-slate-medium">Aturan berikut diperiksa dua kali: di browser saat formulir dikirim, dan di server. Isian yang melanggar ditolak dengan pesan kesalahan di bawah kolomnya.</p>

        <nav class="mt-8 flex flex-wrap gap-2 font-sans text-sm" aria-label="Daftar isi">
            <a href="#alur" class="rounded-full border border-stone px-3 py-1 no-underline hover:border-slate-dark">Alur persetujuan</a>
            @foreach (array_keys($sections) as $title)
                <a href="#{{ Str::slug($title) }}" class="rounded-full border border-stone px-3 py-1 no-underline hover:border-slate-dark">{{ $title }}</a>
            @endforeach
            <a href="#bobot" class="rounded-full border border-stone px-3 py-1 no-underline hover:border-slate-dark">Aspek &amp; bobot</a>
            <a href="#umum" class="rounded-full border border-stone px-3 py-1 no-underline hover:border-slate-dark">Ketentuan umum</a>
        </nav>

        <section id="alur" class="mt-14 scroll-mt-8">
            <h2 class="font-sans font-bold text-2xl sm:text-3xl tracking-tight mb-6">Alur persetujuan</h2>
            <ol class="space-y-3">
                @foreach ($flow as [$role, $description])
                    <li class="card !p-5 flex gap-4">
                        <span class="grid place-items-center size-8 shrink-0 rounded-full bg-slate-dark text-ivory-light font-sans text-sm font-semibold">{{ $loop->iteration }}</span>
                        <div>
                            <p class="font-sans font-semibold">{{ $role }}</p>
                            <p class="mt-1 text-slate-medium">{{ $description }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        @foreach ($sections as $title => $rows)
            <section id="{{ Str::slug($title) }}" class="mt-14 scroll-mt-8">
                <h2 class="font-sans font-bold text-2xl sm:text-3xl tracking-tight mb-6">{{ $title }}</h2>
                <div class="card !p-0 overflow-x-auto">
                    <table class="w-full text-left min-w-[560px]">
                        <thead class="font-sans text-xs uppercase tracking-wider text-cloud-dark">
                            <tr class="border-b border-stone">
                                <th scope="col" class="py-3 px-5 font-semibold w-48">Kolom</th>
                                <th scope="col" class="py-3 px-5 font-semibold">Aturan</th>
                                <th scope="col" class="py-3 px-5 font-semibold w-56">Contoh valid</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as [$field, $rule, $example])
                                <tr class="border-b border-stone last:border-0 align-top">
                                    <th scope="row" class="py-4 px-5 font-sans text-sm font-semibold">{{ $field }}</th>
                                    <td class="py-4 px-5 leading-relaxed">{{ $rule }}</td>
                                    <td class="py-4 px-5 font-mono text-sm break-all">{{ $example }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        <section id="bobot" class="mt-14 scroll-mt-8">
            <h2 class="font-sans font-bold text-2xl sm:text-3xl tracking-tight mb-6">Aspek &amp; bobot penilaian</h2>
            <div class="card !p-0 overflow-x-auto">
                <table class="w-full text-left min-w-[480px]">
                    <thead class="font-sans text-xs uppercase tracking-wider text-cloud-dark">
                        <tr class="border-b border-stone">
                            <th scope="col" class="py-3 px-5 font-semibold w-12">No</th>
                            <th scope="col" class="py-3 px-5 font-semibold">Aspek</th>
                            <th scope="col" class="py-3 px-5 font-semibold w-24 text-right">Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (Review::ASPECTS as [$label, $weight])
                            <tr class="border-b border-stone align-top">
                                <td class="py-4 px-5 font-sans text-sm">{{ $loop->iteration }}</td>
                                <td class="py-4 px-5 leading-relaxed">{{ $label }}</td>
                                <td class="py-4 px-5 font-sans text-sm text-right">{{ $weight }}%</td>
                            </tr>
                        @endforeach
                        <tr class="font-sans font-semibold">
                            <td></td>
                            <td class="py-4 px-5">Total</td>
                            <td class="py-4 px-5 text-right">{{ array_sum(array_column(Review::ASPECTS, 1)) }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="umum" class="mt-14 scroll-mt-8">
            <h2 class="font-sans font-bold text-2xl sm:text-3xl tracking-tight mb-6">Ketentuan umum</h2>
            <ul class="card space-y-3 list-disc pl-10 leading-relaxed">
                <li>Spasi di awal dan akhir isian dihapus otomatis; isian yang hanya berisi spasi dianggap kosong.</li>
                <li>Batas karakter dihitung setelah spasi di awal dan akhir dihapus.</li>
                <li>Angka ditulis tanpa pemisah ribuan, tanpa desimal, dan tanpa awalan "Rp".</li>
                <li>Setiap peran hanya bisa membuka halaman perannya; Pengusul hanya bisa melihat proposal miliknya.</li>
                <li>Persetujuan yang sudah diberikan tidak bisa diberikan dua kali, dan anggaran tidak berkurang dua kali.</li>
            </ul>
        </section>
    </div>
</x-layout>
