@extends('layouts.app', ['title' => 'Sinkron SILAP'])

@section('content')
<div class="between mb">
    <div>
        <h1>Sinkron SILAP</h1>
        <p class="muted">Endpoint ini disiapkan untuk website SILAP mengirim data kelas, siswa, dan guru ke aplikasi ujian.</p>
    </div>
    <span class="badge {{ $tokenConfigured ? 'active' : 'warning' }}">{{ $tokenConfigured ? 'Token aktif' : 'Token belum diset' }}</span>
</div>

<div class="two mb">
    <div class="card">
        <h2>Endpoint API</h2>
        <p class="muted">Tambahkan di file <code>.env</code> backend ujian:</p>
        <pre class="code">SILAP_SYNC_TOKEN=isi_token_rahasia_panjang
SILAP_DEFAULT_STUDENT_PASSWORD=</pre>
        <p class="muted small">Jika <code>SILAP_DEFAULT_STUDENT_PASSWORD</code> kosong, akun siswa baru otomatis memakai NIS sebagai password awal.</p>
    </div>
    <div class="card">
        <h2>URL Sinkron</h2>
        <pre class="code">POST {{ $endpoint }}
Authorization: Bearer TOKEN_DARI_ENV
Content-Type: application/json</pre>
        <p class="muted small">Kalau token belum diset, endpoint hanya aman untuk development lokal. Pada APP_ENV=production, request sinkron akan ditolak sampai SILAP_SYNC_TOKEN diisi.</p>
    </div>
</div>

<div class="card">
    <h2>Contoh Payload Sesuai SQL SILAP</h2>
    <pre class="code">{
  "classrooms": [
    {
      "id": 10,
      "term_id": 1,
      "nama_kelas": "XII RPL 1",
      "tingkat": 12
    },
    {
      "id": 11,
      "term_id": 1,
      "nama_kelas": "XI RPL 1",
      "tingkat": 11
    }
  ],
  "siswa": [
    {
      "id": 1,
      "term_id": 1,
      "classroom_id": 10,
      "user_id": 55,
      "nis": "4728",
      "nama_lengkap": "ACHMAD RAFA YUSAPUTRA",
      "jenis_kelamin": "L",
      "tempat_lahir": null,
      "tanggal_lahir": null,
      "agama": null,
      "alamat": null,
      "kontak": null,
      "photo": null,
      "nama_ayah": null,
      "pekerjaan_ayah": null,
      "kontak_ayah": null,
      "nama_ibu": null,
      "pekerjaan_ibu": null,
      "kontak_ibu": null,
      "nama_wali_murid": null,
      "kontak_wali": null,
      "alamat_orangtua": null,
      "alamat_wali": null
    }
  ],
  "guru": [
    {
      "id": 47,
      "nip": "NIPPPK 200005102024211004",
      "nama_lengkap": "NAUFAL ZAHIR RIZQULLAH, S.Kom.",
      "tempat_lahir": null,
      "tanggal_lahir": null,
      "jenis_kelamin": "L",
      "kontak": null,
      "alamat": null,
      "photo": null,
      "user_id": 48
    }
  ]
}</pre>
</div>
@endsection
