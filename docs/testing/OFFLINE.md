# Aset dan pengujian offline

Semua CSS, JavaScript, font, ikon dan berkas bahasa yang digunakan antarmuka aplikasi sekarang dimuat dari server aplikasi sendiri. Tidak ada permintaan frontend ke CDN, Google Fonts atau Bunny Fonts. Perubahan berlaku pada beranda, login, dashboard, kategori, transaksi, laporan, profil dan layout autentikasi lainnya.

## Aset lokal

Berkas disimpan di `public/vendor`, beserta lisensinya:

| Komponen | Versi |
| --- | --- |
| Bootstrap CSS dan JavaScript bundle, termasuk Popper | 5.3.3 |
| Bootstrap Icons CSS, font WOFF/WOFF2 | 1.11.3 |
| jQuery, digunakan bersama oleh Select2 dan DataTables | 3.7.1 |
| Select2 | 4.1.0-rc.0 |
| Select2 Bootstrap 5 theme | 1.3.0 |
| DataTables Bootstrap 5 dan terjemahan Indonesia | 1.13.7 |
| Chart.js | 4.6.1 |
| Poppins lokal, bobot 400/600/700 | Fontsource 5.3.0 |
| Figtree lokal, bobot 400/500/600/700 | Fontsource 5.3.0 |

Alpine.js, Axios dan CSS Tailwind untuk halaman profil/autentikasi tetap menggunakan build Vite lokal di `public/build`. Tidak ada penambahan dependency npm untuk aset vendor ini.

[Manifest vendor](../../public/vendor/manifest.json) mencatat 31 berkas dengan sumber unduhan, ukuran dan SHA-256. URL internet di manifest, lisensi atau komentar hak cipta adalah informasi asal, bukan resource yang dimuat aplikasi. Rujukan source map di library dihapus agar tidak meminta berkas map yang tidak disertakan. Font dan gambar dalam CSS mengarah ke berkas lokal atau data URI.

## Hasil pengujian, 6 November 2026

- **12 pengujian aset lulus**: sembilan halaman hanya memuat stylesheet/script lokal yang tersedia; template tidak memiliki resource eksternal; font/gambar CSS tersedia lokal; checksum seluruh berkas vendor sesuai.
- **23 skenario Chrome lulus** dengan browser context baru dan seluruh permintaan di luar origin `http://127.0.0.1:18084` diblokir. Cache sesi browser sebelumnya tidak dipakai dan service worker diblokir.
- **0 percobaan permintaan internet, 0 kegagalan resource lokal, 0 kegagalan jaringan, 0 exception JavaScript dan 0 error console.**
- Alur yang diperiksa: beranda, login salah/benar, grafik, Poppins/ikon, tambah/edit kategori dengan modal, pencarian Select2, format rupiah bulat, transaksi masuk/keluar/edit/hapus, validasi hapus kategori, DataTables pencarian/pagination/sorting/bahasa Indonesia, filter/reset, navigasi mobile, profil, Figtree, konfirmasi password, perubahan password, logout/login kembali dan modal hapus akun.
- Build `npm run build` lulus.
- Regresi fitur aktif ditambah pengujian aset: **107 pengujian lulus**. Temuan lama di grup audit dan pengujian registrasi/reset password yang dinonaktifkan tetap dicatat dalam [laporan awal](LAPORAN-PENGUJIAN.md); perubahan offline tidak memperbaiki masalah tersebut. Pemeriksaan pecahan rupiah berada di audit awal, bukan dalam 23 skenario penerimaan offline ini.

Bukti: [hasil browser lengkap](offline-browser/results.json), [dashboard desktop offline](offline-browser/dashboard-desktop.png), [dashboard mobile offline](offline-browser/dashboard-mobile.png), [hasil PHPUnit offline](offline-phpunit-results.json), [output PHPUnit](offline-phpunit-output.txt).

Masalah pemuatan bahasa DataTables melalui HTTP/CDN pada laporan awal (B08) selesai: terjemahan Indonesia kini dimuat dari `public/vendor/datatables/i18n/id.json` dan diperiksa melalui browser.

## Pemakaian pada komputer offline

1. Sertakan seluruh aplikasi, dependency PHP dalam `vendor/`, `public/vendor/`, serta **`public/build/` lengkap dengan `manifest.json` dan seluruh asetnya**. Direktori `public/build` diabaikan Git; sertakan hasil build saat menyalin atau membuat paket aplikasi.
2. Jika sumber frontend berubah, jalankan `npm run build` pada komputer persiapan, lalu salin ulang `public/build`. Pengguna aplikasi tidak perlu menjalankan npm atau mengunduh library saat memakai aplikasi.
3. Paketkan tanpa `public/hot`, karena file pengembangan tersebut membuat `@vite` menunjuk ke server development. Gunakan URL aplikasi lokal dan jangan mengatur `ASSET_URL` ke host internet.
4. Jalankan server PHP dan database secara lokal atau melalui jaringan lokal. Arahkan document root web server ke `public/` seperti pemasangan Laravel biasa.

Pengujian ini membuktikan antarmuka berjalan tanpa internet selama server aplikasi dan database tetap tersedia. Pengiriman email nyata ke alamat luar tetap memerlukan layanan email yang dapat dijangkau; tidak ada pengiriman email eksternal dalam pengujian ini.

## Menjalankan ulang

```sh
php artisan test --filter=OfflineAssetsTest

# Memerlukan Playwright dan Chrome yang telah tersedia di komputer pengujian.
# PLAYWRIGHT_MODULE dapat diisi path paket Playwright lokal jika belum ada di node_modules.
OFFLINE_ASSETS=1 node tests/browser/all-features.cjs
```

Skrip menggunakan database SQLite baru di direktori sementara, bukan database aplikasi. Setiap permintaan keluar diblokir sekaligus dicatat, sehingga tes gagal bila dependensi internet muncul kembali.
