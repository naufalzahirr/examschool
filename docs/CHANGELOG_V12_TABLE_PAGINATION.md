# Changelog V12 - Perapian Panah Tabel/Pagination

Perbaikan fokus pada tampilan tabel yang sebelumnya panah pagination/default Laravel terlihat terlalu besar dan berantakan.

## Perubahan

1. Menambahkan custom pagination view:
   - `resources/views/vendor/pagination/custom.blade.php`

2. Mengaktifkan custom pagination melalui:
   - `app/Providers/AppServiceProvider.php`

3. Menambahkan CSS khusus pagination di layout utama:
   - ukuran panah stabil
   - tombol halaman kecil dan rapi
   - tampilan mobile lebih compact
   - tidak bergantung pada class Tailwind bawaan pagination Laravel

## Setelah update

Jalankan:

```bash
php artisan optimize:clear
php artisan view:clear
```

Jika di production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
