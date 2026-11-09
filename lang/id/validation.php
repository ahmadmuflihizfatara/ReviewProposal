<?php

// ponytail: hanya aturan yang dipakai aplikasi ini; sisanya jatuh ke bahasa Inggris (fallback).
return [
    'accepted' => ':Attribute harus disetujui.',
    'between' => ['numeric' => ':Attribute harus antara :min dan :max.'],
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'exists' => ':Attribute tidak ditemukan.',
    'file' => ':Attribute harus berupa berkas.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa angka bulat.',
    'max' => [
        'file' => ':Attribute maksimal :max kilobita.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'mimes' => ':Attribute harus berupa berkas: :values.',
    'min' => ['string' => ':Attribute minimal :min karakter.'],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'unique' => ':Attribute sudah digunakan.',
    'enum' => ':Attribute yang dipilih tidak valid.',

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'role' => 'peran',
        'title' => 'judul',
        'summary' => 'ringkasan',
        'file' => 'berkas proposal',
        'registration_number' => 'nomor registrasi',
        'recommendation' => 'rekomendasi',
        'recommendation_note' => 'catatan rekomendasi',
        'scores.*' => 'skor',
        'notes.*' => 'catatan',
    ],
];
