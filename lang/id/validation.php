<?php

// ponytail: hanya aturan yang dipakai aplikasi ini; sisanya jatuh ke bahasa Inggris (fallback).
return [
    'accepted' => ':Attribute harus disetujui.',
    'array' => ':Attribute tidak valid.',
    'between' => ['numeric' => ':Attribute harus antara :min dan :max.'],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'exists' => ':Attribute tidak ditemukan.',
    'file' => ':Attribute harus berupa berkas.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa angka bulat.',
    'max' => [
        'file' => ':Attribute maksimal :max kilobita.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'mimes' => ':Attribute harus berupa berkas: :values.',
    'mimetypes' => ':Attribute harus berupa berkas: :values.',
    'min' => [
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'password' => [
        'mixed' => ':Attribute harus memuat minimal satu huruf besar dan satu huruf kecil.',
        'numbers' => ':Attribute harus memuat minimal satu angka.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'unique' => ':Attribute sudah digunakan.',
    'enum' => ':Attribute yang dipilih tidak valid.',

    'custom' => [
        'name' => ['regex' => 'Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.'],
        'registration_number' => ['regex' => 'Format nomor registrasi harus PL{prodi}/{No}/{Bulan Romawi}/{Tahun}, contoh PLTI/001/XI/2026.'],
    ],

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'role' => 'peran',
        'title' => 'judul',
        'summary' => 'ringkasan',
        'budget' => 'anggaran',
        'amount' => 'anggaran tersedia',
        'file' => 'berkas proposal',
        'proposal_id' => 'proposal',
        'registration_number' => 'nomor registrasi',
        'recommendation_note' => 'rekomendasi',
        'scores' => 'skor',
        'values' => 'nilai',
        'notes' => 'catatan',
        'scores.*' => 'skor',
        'values.*' => 'nilai',
        'notes.*' => 'catatan',
    ],
];
