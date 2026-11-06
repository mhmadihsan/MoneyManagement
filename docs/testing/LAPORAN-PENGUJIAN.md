# Laporan pengujian Management Keuangan

Tanggal: 6 November 2026. Hasil di bawah merekam audit awal setelah penambahan validasi penghapusan kategori, sebelum perubahan aset offline. Untuk hasil terbaru perubahan offline, lihat [laporan offline](OFFLINE.md): 23 skenario browser lulus tanpa permintaan internet; masalah bahasa DataTables B08 sudah diperbaiki.

## Ringkasan

| Pemeriksaan | Hasil |
| --- | --- |
| PHPUnit seluruh suite | **114 skenario: 95 lulus, 19 gagal**, 412 assertions |
| Suite fitur keuangan dan pemeriksaan tambahan `FinanceFeaturesTest` | **76/76 lulus**, 340 assertions |
| Browser Chrome desktop dan mobile | **21 skenario: 20 lulus, 1 gagal** |
| Build frontend `npm run build` | Lulus |
| Sintaks controller dan skrip browser; `git diff --check` | Lulus |

Sembilan belas kegagalan PHPUnit terdiri atas 6 pengujian bawaan untuk registrasi/reset password yang rutenya dinonaktifkan, serta 13 pemeriksaan audit yang membuktikan masalah di bawah. Satu kegagalan browser membuktikan perubahan nilai input pecahan rupiah. Aplikasi belum dapat dinyatakan bebas masalah meskipun alur transaksi utama berfungsi.

## Lingkungan dan metode

- PHP 8.4.15, Laravel 13, Node.js 20.19.3, Vite 8.0.3, Chrome headless melalui Playwright.
- PHPUnit menggunakan SQLite `:memory:`; setiap pengujian fitur memakai database baru melalui `RefreshDatabase`.
- Browser menggunakan database SQLite baru di direktori `/private/tmp/managementMoney-qa-*`, akun uji khusus, serta server `127.0.0.1:18084`. Database aplikasi yang dikonfigurasi di `.env` tidak dimigrasi atau diisi data uji.
- Desktop 1440 × 1000 dan mobile 390 × 844. Pengujian memakai JavaScript dan CDN asli aplikasi.
- Pemeriksaan menggabungkan HTTP melalui kernel Laravel, perubahan database, validasi, isi respons, interaksi browser, dan inspeksi kode/rute.
- Runner awal gagal dengan `Class PHPUnit\Runner\CodeCoverageInitializationStatus not found`. Autoloader lokal diregenerasi memakai `composer dump-autoload --no-scripts`; setelah itu seluruh suite dapat dijalankan. Tidak ada pembaruan dependency.

## Hasil seluruh fitur

“Lulus” merujuk pada skenario yang disebutkan, bukan jaminan seluruh kemungkinan input atau lingkungan.

| Fitur | Pengujian | Hasil |
| --- | --- | --- |
| Beranda | Halaman terbuka dan tautan login bekerja | Lulus server + browser |
| Login | Halaman, kredensial benar, password salah dan pesan kesalahan | Lulus server + browser |
| Ingat saya | Login menerbitkan cookie pengingat | Lulus server; pemulihan sesi setelah menutup browser belum diuji |
| Batas percobaan login | Lima kegagalan menyebabkan percobaan berikutnya dibatasi | Lulus server |
| Logout | Keluar, kembali ke beranda; halaman terlindungi meminta login | Lulus server + browser |
| Proteksi akses | 27 kombinasi metode/endpoint tanpa login dialihkan ke login | Lulus server |
| Halaman tanpa data | Dashboard, kategori, input/laporan transaksi, profil dan konfirmasi password | Lulus server |
| Dashboard: total masuk/keluar/saldo | Total semua periode dan selisih saldo dengan data terkontrol | Lulus server + browser |
| Dashboard: jumlah transaksi tahun ini | Transaksi tahun sebelumnya tidak ikut hitungan tahun berjalan | Lulus server |
| Dashboard: grafik | Chart.js terinisialisasi dengan angka yang sesuai | Lulus browser |
| Dashboard: sesi login | Muat ulang dashboard setelah waktu dimajukan | **Gagal: waktu berubah mengikuti permintaan halaman** |
| Daftar kategori | Daftar dan keadaan kosong | Lulus server + browser |
| Tambah kategori | Penyimpanan, pesan berhasil, batas nama 255 karakter | Lulus server + browser |
| Validasi kategori | Nama kosong/spasi, array, dan lebih dari 255 karakter ditolak pada tambah/ubah | Lulus server |
| Edit kategori | Modal, perubahan nama, dan penyimpanan | Lulus server + browser |
| Hapus kategori tanpa transaksi | Penghapusan dan pesan berhasil | Lulus server + browser |
| Hapus kategori dengan transaksi | Ditolak; kategori dan transaksi tetap ada; pesan kesalahan tampil; saldo tetap | **Lulus setelah perbaikan yang diminta** |
| Hapus kategori setelah transaksi terakhir dihapus | Penghapusan kembali diperbolehkan | Lulus server + browser |
| Form input transaksi | Data kategori dan saldo sebelum transaksi tersimpan | Lulus server + browser |
| Pencarian kategori | Select2 dapat mencari dan memilih kategori | Lulus browser |
| Format nominal bulat | `125000` tampil `125.000`, nilai terkirim `125000` | Lulus browser |
| Format nominal pecahan | Input `123,45` seharusnya mempertahankan nilai pecahan | **Gagal: nilai tersembunyi menjadi `12345`** |
| Simpan transaksi | Uang masuk dan uang keluar, data tersimpan, saldo berubah | Lulus server + browser |
| Validasi transaksi | Tipe salah/kosong; nominal kosong/negatif/nonangka; tanggal kosong/tidak valid; kategori kosong/tidak ada; keterangan array | Lulus server pada tambah dan ubah |
| Keterangan opsional dan nominal nol | Diterima sesuai aturan `nullable` dan `min:0` yang ada | Lulus server; penerimaan nol merupakan perilaku saat ini |
| Batas dan presisi nominal | Nilai melebihi `decimal(15,2)` dan tiga angka desimal | **Gagal: server tidak menolak input** |
| Edit transaksi | Form terisi, nominal diubah, data dan saldo diperbarui | Lulus server + browser |
| Kesalahan pada form transaksi | Pesan kesalahan server terlihat setelah redirect tambah/ubah | **Gagal: pesan tidak dirender di kedua form** |
| Batal hapus transaksi | Membatalkan dialog mempertahankan transaksi | Lulus browser |
| Hapus transaksi | Konfirmasi, data terhapus, saldo berubah | Lulus server + browser |
| Laporan transaksi | Daftar beserta kategori/keterangan dan urutan tanggal | Lulus server + browser |
| Filter laporan | Tipe, kategori, tanggal awal/akhir, batas tanggal inklusif, kombinasi dan hasil kosong | Lulus server; 9 kombinasi |
| Filter dan reset melalui UI | Kombinasi filter diterapkan; Reset mengosongkan filter | Lulus browser |
| Ringkasan laporan | Total masuk, keluar dan saldo dihitung dari hasil filter server | Lulus server + browser |
| Filter tidak valid | Tanggal tidak valid, rentang terbalik, tipe tidak dikenal | **Gagal: tetap HTTP 200 tanpa penjelasan validasi** |
| Pencarian, sorting, pagination tabel | 14 baris, 10 baris halaman pertama, 4 berikutnya; pencarian dan urutan tanggal | Lulus browser |
| Bahasa Indonesia DataTables | Memuat berkas bahasa melalui URL aplikasi | **Bermasalah: permintaan HTTP ke CDN ditolak CORS; fungsi tabel tetap berjalan** |
| Profil | Halaman dan perubahan nama/email; email duplikat/tidak valid ditolak | Lulus server; perubahan nama juga lulus browser |
| Status verifikasi setelah perubahan email | Direset ketika email berubah; tetap ketika email sama | Lulus server |
| Ubah password | Password lama benar, salah dan konfirmasi tidak cocok | Lulus server; ubah dan login memakai password baru lulus browser |
| Konfirmasi password | Halaman serta password benar/salah | Lulus server |
| Hapus akun | Password benar/salah; akun terhapus dan sesi berakhir | Lulus server; modal dan penghapusan lulus browser |
| Verifikasi email | Halaman, tautan bertanda tangan valid, hash salah, notifikasi, batas enam permintaan | Lulus server dengan notifikasi simulasi |
| Kewajiban verifikasi email | Akun belum terverifikasi mengakses dashboard | **Gagal: dashboard tetap terbuka** |
| Resource tidak ditemukan | Ubah/hapus kategori dan ubah/hapus/form edit transaksi untuk ID tidak ada | Lulus server: 404 |
| Rute resource tambahan | `/kategori/create`, `/kategori/{id}`, `/kategori/{id}/edit`, `/transaksi/{id}` | **Gagal: 500 karena metode controller tidak tersedia** |
| Escaping HTML | Tag script dalam kategori/keterangan ditampilkan sebagai teks | Lulus server |
| Data antar akun | Akun kedua membaca dan menghapus transaksi yang sudah ada | Terbukti data bersama; perlu kesesuaian dengan kebutuhan produk |
| Navigasi mobile | Menu dapat dibuka dan dashboard tidak melebar keluar viewport | Lulus browser pada 390 px |
| Health endpoint | `/up` siap sebelum pengujian browser | Lulus |
| Registrasi | Halaman dan submit `/register` | Dinonaktifkan di `routes/auth.php`; 2 pengujian bawaan gagal |
| Lupa/reset password | Permintaan link, halaman token dan penyimpanan password | Dinonaktifkan di `routes/auth.php`; 4 pengujian bawaan gagal |

## Perbaikan penghapusan kategori

`KategoriController::destroy()` sekarang memeriksa `$kategori->transaksiKeuangan()->exists()` sebelum menghapus. Jika kategori masih digunakan, pengguna kembali ke daftar kategori dengan pesan:

> Kategori tidak dapat dihapus karena masih digunakan oleh transaksi.

Layout Bootstrap menampilkan pesan tersebut sebagai alert merah. Kategori tanpa transaksi tetap dapat dihapus. Ketika transaksi terakhir sudah dihapus, kategori dapat dihapus kembali. Perilaku ini sudah diuji melalui HTTP dan browser. Perubahan hanya pada controller dan tampilan pesan; skema database tidak diubah.

## Temuan yang belum diperbaiki

| ID | Prioritas | Bukti dan dampak | Arah perbaikan |
| --- | --- | --- | --- |
| B01 | Tinggi | Form mengubah `123,45` menjadi `12345`. Nilai uang yang terkirim dapat berbeda dari maksud pengguna. | Tetapkan dukungan pecahan atau hanya rupiah bulat, lalu tolak format yang tidak didukung tanpa mengubah makna angkanya. |
| B02 | Tinggi | POST nominal `99999999999999.99` dan `100.123` diterima pada SQLite walau kolom didefinisikan `decimal(15,2)`. | Validasi maksimum dan jumlah desimal pada tambah/ubah. Dampak MySQL belum diuji; tidak boleh mengandalkan SQLite untuk pembatasan presisi. |
| B03 | Sedang | POST/PUT transaksi dengan nominal negatif mengisi error session, tetapi halaman form tidak memuat pesan itu. Nilai tanggal, nominal dan keterangan lama juga belum dipulihkan konsisten menurut inspeksi template. | Render error tiap field dan gunakan `old()` untuk seluruh input. Kegagalan pesan telah diuji; pemulihan setiap field belum diuji browser. |
| B04 | Sedang | Akun `unverified()` mendapat HTTP 200 pada dashboard. `User` tidak mengimplementasikan `MustVerifyEmail` walau rute menggunakan `verified`. | Bila verifikasi diwajibkan, selaraskan model dan pembatasan endpoint. Bila tidak, hapus alur yang menyesatkan. |
| B05 | Sedang | Empat rute resource tercatat tetapi metode `create/show/edit` kategori dan `show` transaksi tidak ada. Pengguna login memperoleh 500. | Batasi resource ke metode yang dipakai dengan `only()`/`except()`, atau implementasikan metode tersebut. |
| B06 | Sedang | Filter `start_date=invalid`, tanggal awal setelah akhir dan `tipe=invalid` mendapat HTTP 200 tanpa validasi. | Validasi format, nilai tipe/kategori dan urutan rentang tanggal; tampilkan pesan. |
| B07 | Rendah | Nilai “Sesi Login Saat Ini” berubah saat dashboard dimuat ulang 10 menit kemudian. Kode mengambil `now()` per request. | Simpan waktu login dalam sesi ketika autentikasi berhasil. |
| B08 | Rendah | Browser mencatat kegagalan CORS untuk `http://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json`. Tidak ada exception JavaScript; pencarian/sorting/pagination tetap lulus. | Gunakan resource bahasa lokal atau URL HTTPS yang dapat diakses dari origin aplikasi, lalu uji kembali. |

Data keuangan tidak mempunyai pemilik per pengguna dan seluruh akun login memiliki akses yang sama. Ini merupakan perilaku teruji, bukan otomatis bug jika aplikasi memang kas bersama. Jika dibutuhkan keuangan pribadi atau pembagian wewenang, kepemilikan data dan otorisasi perlu ditambahkan.

## Bukti dan cara menjalankan ulang

- [Daftar seluruh hasil PHPUnit](phpunit-results.json), termasuk nama setiap skenario dan pesan kegagalan.
- [Output lengkap PHPUnit](phpunit-output.txt).
- [Hasil browser, console dan jaringan](browser/results.json).
- [Dashboard desktop](browser/dashboard-desktop.png), [dashboard mobile](browser/dashboard-mobile.png), [bukti input pecahan](browser/failure-14.png).
- Suite tambahan: [FinanceFeaturesTest](../../tests/Feature/FinanceFeaturesTest.php) dan [FeatureAuditTest](../../tests/Feature/FeatureAuditTest.php). Grup `audit` berisi ekspektasi yang masih gagal; bukan pengujian yang dilewati atau dipaksa lulus.

```sh
# Seluruh pengujian, termasuk masalah yang masih terbuka dan fitur dinonaktifkan.
php artisan test

# 76 pengujian fitur keuangan dan pemeriksaan tambahan.
php artisan test --filter=FinanceFeaturesTest

# 13 pemeriksaan audit yang membuktikan masalah terbuka.
php artisan test --group=audit

npm run build

# Memerlukan Playwright yang sudah tersedia dan Chrome.
# PLAYWRIGHT_MODULE dapat menunjuk ke direktori paket Playwright lokal.
node tests/browser/all-features.cjs
```

Skrip browser membuat database sementara dan menutup server/browser sesudah selesai. Akun dan data uji tidak ditambahkan ke database aplikasi. Hasil browser akan menimpa `browser/results.json` dan screenshot dengan nama yang sama.

## Batas pengujian

Pengujian ini belum mencakup MySQL/MariaDB, pengiriman email nyata, pemulihan cookie setelah restart browser, Safari/Firefox, seluruh ukuran layar, uji beban, atau permintaan tulis bersamaan. Notifikasi email memakai fake/array sehingga hasilnya membuktikan alur aplikasi, bukan keterkiriman email. Validasi penghapusan kategori diuji lewat jalur HTTP aplikasi; operasi langsung di database dan perilaku konkurensi belum diuji. Tidak ada perhitungan persentase code coverage.
