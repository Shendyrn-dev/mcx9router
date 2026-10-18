# PRD: mcx9route

**Product Requirements Document (versi lengkap)**

| | |
|---|---|
| **Nama produk** | mcx9route |
| **Versi dokumen** | 2.0 |
| **Tanggal** | 18 Oktober 2026 |
| **Status** | Draf, siap dikerjakan |
| **Pemilik produk** | Pemilik instance 9Router (pengguna tunggal) |
| **Teknologi** | Laravel 11/12 (PHP 8.2+) |
| **Hosting web** | Railway |
| **Sumber data** | Instance 9Router di blitz.cloud |

Dokumen ini menggantikan PRD versi 1.0. Perubahan utama: teknologi menjadi Laravel, autentikasi ke 9Router dilakukan otomatis oleh backend, serta penambahan bagian keamanan, pengujian, dan keputusan polling versus streaming.

---

## Daftar isi

1. Ringkasan eksekutif
2. Latar belakang dan masalah
3. Tujuan, sasaran, dan non-tujuan
4. Pengguna dan skenario penggunaan
5. Ruang lingkup dan fase
6. Sumber data dan kontrak API
7. Kebutuhan fungsional
8. Kebutuhan non-fungsional
9. Arsitektur
10. Struktur kode
11. Konfigurasi
12. Desain antarmuka
13. Keamanan dan privasi
14. Deployment di Railway
15. Pengujian dan kriteria penerimaan
16. Metrik keberhasilan
17. Risiko dan mitigasi
18. Rencana rilis
19. Asumsi dan dependensi
20. Pertanyaan terbuka
21. Lampiran

---

## 1. Ringkasan eksekutif

mcx9route adalah web dashboard pribadi untuk memantau pemakaian 9Router, yaitu AI router open source yang meneruskan permintaan dari tool coding ke banyak provider AI. Instance 9Router pemilik sudah di-deploy di blitz.cloud. mcx9route berjalan terpisah di Railway, mengambil data overview dari 9Router, dan menampilkannya dalam antarmuka milik sendiri yang aman, ringkas, dan bisa dikembangkan.

Versi 1 bersifat **hanya-baca**: menampilkan total permintaan, token, estimasi biaya, serta rincian per provider dan per model untuk periode yang dipilih.

## 2. Latar belakang dan masalah

| Masalah | Dampak |
|---|---|
| Dashboard bawaan 9Router tidak bisa disesuaikan tanpa mengubah kode 9Router. | Tampilan dan fitur terbatas pada yang disediakan 9Router. |
| Data usage di 9Router bisa hilang bila instance restart atau di-redeploy, tergantung penyimpanan di hosting. | Riwayat pemakaian tidak terjamin. |
| Endpoint data dashboard 9Router dilindungi login berbasis cookie yang berlaku sekitar 24 jam. | Web lain tidak bisa mengambil data tanpa mengelola login. |
| Dashboard 9Router dan web ini sama-sama terbuka ke internet. | Perlu perlindungan agar data pemakaian tidak bisa dilihat orang lain. |

## 3. Tujuan, sasaran, dan non-tujuan

### 3.1 Tujuan

1. Menampilkan overview pemakaian 9Router per periode dalam satu halaman.
2. Menampilkan rincian per provider dan per model.
3. Mengelola autentikasi ke 9Router secara otomatis, tanpa menyalin cookie manual.
4. Melindungi akses web dengan login.
5. Berjalan stabil di Railway dengan biaya rendah.

### 3.2 Sasaran terukur (versi 1)

| Sasaran | Ukuran |
|---|---|
| Kesesuaian data dengan dashboard 9Router | Angka sama untuk periode yang sama |
| Waktu muat halaman utama | Di bawah 2 detik pada koneksi normal |
| Ketahanan terhadap token kedaluwarsa | Pemulihan otomatis tanpa intervensi |
| Akses tanpa login | Selalu ditolak |

### 3.3 Non-tujuan (versi 1)

- Mengatur provider, OAuth, atau routing 9Router.
- Menggantikan seluruh fitur dashboard 9Router.
- Multi-user atau peran berbeda.
- Menghitung tagihan nyata. Biaya hanya estimasi.
- Menyimpan riwayat jangka panjang (masuk fase 2).

## 4. Pengguna dan skenario penggunaan

### 4.1 Persona

**Pemilik 9Router.** Pengguna tunggal yang memakai 9Router untuk tool coding, ingin memeriksa pemakaian dari laptop atau ponsel, dan nyaman dengan konfigurasi teknis dasar.

### 4.2 Cerita pengguna

| ID | Sebagai pemilik, saya ingin... | Agar... |
|---|---|---|
| U-01 | membuka mcx9route dan langsung melihat ringkasan hari ini | tahu pemakaian tanpa membuka dashboard 9Router |
| U-02 | beralih periode (24 jam, 7 hari, dst.) | membandingkan pemakaian dari waktu ke waktu |
| U-03 | melihat provider mana yang paling banyak dipakai | memahami pola pemakaian |
| U-04 | melihat model mana yang paling mahal secara estimasi | mengoptimalkan pilihan model |
| U-05 | web tetap berfungsi saat sesi 9Router kedaluwarsa | tidak perlu memperbarui kredensial manual |
| U-06 | mendapat pesan error yang jelas bila ada masalah | tahu apa yang harus diperbaiki |
| U-07 | web hanya bisa dibuka dengan login | data pemakaian tidak terlihat orang lain |

## 5. Ruang lingkup dan fase

| Fase | Isi | Status |
|---|---|---|
| **MVP (v1.0)** | Login web, kartu ringkasan, pilihan periode, per provider, per model, polling otomatis, login otomatis ke 9Router | Kode awal sudah dibuat, belum diuji |
| **v1.1** | Grafik tren harian dari endpoint `chart` | Menunggu contoh JSON `chart` |
| **v1.2** | Riwayat di database, ekspor CSV | Belum |
| **v2.0** | Peringatan biaya, tampilan streaming/real-time dari endpoint `stream`, log permintaan | Belum, perlu verifikasi endpoint |

## 6. Sumber data dan kontrak API

Seluruh endpoint di bawah adalah endpoint **internal** dashboard 9Router yang diamati lewat tab Network browser. Endpoint ini bukan API publik terdokumentasi, jadi dapat berubah antar versi 9Router. Endpoint `/v1` pada 9Router adalah API kompatibel OpenAI untuk tool coding dan **tidak** dipakai untuk data overview.

### 6.1 Endpoint yang terkonfirmasi

| Fungsi | Metode | Path | Catatan |
|---|---|---|---|
| Login | POST | `/api/auth/login` | Status 200 saat berhasil. Nama field payload perlu dikonfirmasi (asumsi: `password`). |
| Statistik | GET | `/api/usage/stats?period=<nilai>` | Butuh cookie `auth_token`. Tanpa login: 401. |

### 6.2 Endpoint yang belum terverifikasi

| Fungsi | Path | Catatan |
|---|---|---|
| Grafik | `/api/usage/chart?period=<nilai>` (asumsi) | Terlihat di Network sebagai `chart?period=today`. Path penuh dan struktur JSON belum dicek. |
| Stream | belum diketahui | Request `stream` bertipe `eventsource`, kemungkinan data real-time. Isinya belum diperiksa. |

### 6.3 Autentikasi ke 9Router

- Sesi memakai cookie `auth_token` berformat JWT.
- Masa berlaku token sekitar 24 jam sejak login.
- Instance berada di belakang Cloudflare (terlihat dari cookie `cf_clearance` dan alamat IP). Request dari server dapat dikenai tantangan bot.
- Strategi mcx9route: login otomatis dengan password dari konfigurasi, simpan token di cache 23 jam, dan login ulang satu kali bila menerima 401.

### 6.4 Nilai periode

Dashboard 9Router menyediakan tombol Today, 24h, 7D, 30D, 60D, All. Nilai `period` yang diasumsikan: `today`, `24h`, `7d`, `30d`, `60d`, `all`. Hanya `today` yang sudah terkonfirmasi.

### 6.5 Struktur respons `stats` (terkonfirmasi untuk `period=today`)

```json
{
  "totalRequests": 27,
  "totalPromptTokens": 930099,
  "totalCompletionTokens": 13182,
  "totalCachedTokens": 93120,
  "totalCost": 1.228,
  "byProvider": {
    "<provider>": {
      "requests": 0, "promptTokens": 0, "completionTokens": 0,
      "cachedTokens": 0, "cost": 0
    }
  },
  "byModel": {
    "<model> (<provider>)": {
      "requests": 0, "promptTokens": 0, "completionTokens": 0,
      "cachedTokens": 0, "cost": 0,
      "rawModel": "string", "provider": "string",
      "lastUsed": "ISO-8601"
    }
  }
}
```

## 7. Kebutuhan fungsional

### 7.1 Autentikasi dan akses

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F-01 | Semua route (halaman dan API internal) dilindungi login HTTP Basic memakai `DASH_USER` dan `DASH_PASSWORD`. | Wajib |
| F-02 | Bila `DASH_PASSWORD` kosong, semua akses ditolak (gagal-aman). | Wajib |
| F-03 | Perbandingan kredensial memakai pembanding waktu-konstan (`hash_equals`). | Wajib |

### 7.2 Integrasi 9Router

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F-04 | Backend login ke 9Router memakai `NINE_ROUTER_PASSWORD` dan membaca cookie `auth_token` dari respons. | Wajib |
| F-05 | Token disimpan di cache selama 23 jam dan dipakai ulang untuk request berikutnya. | Wajib |
| F-06 | Bila 9Router membalas 401, backend menghapus token, login ulang satu kali, dan mengulang request. | Wajib |
| F-07 | Parameter `period` divalidasi terhadap daftar putih; nilai tidak dikenal diganti `today`. | Wajib |
| F-08 | Hasil `stats` dan `chart` di-cache singkat (10 detik) agar 9Router tidak dibanjiri request. | Wajib |
| F-09 | Kegagalan upstream dikembalikan sebagai HTTP 502 dengan pesan yang tidak membocorkan kredensial. | Wajib |

### 7.3 Tampilan

| ID | Kebutuhan | Prioritas |
|---|---|---|
| F-10 | Menampilkan lima ringkasan: total permintaan, token masuk, token cache, token keluar, estimasi biaya. | Wajib |
| F-11 | Pilihan periode (6 opsi); pilihan terakhir diingat di browser. | Wajib |
| F-12 | Bagian "Per provider": nama, jumlah permintaan, biaya, dan bar perbandingan. | Wajib |
| F-13 | Bagian "Per model": nama model, provider, permintaan, token masuk/keluar, biaya, waktu terakhir dipakai (relatif), diurutkan dari biaya terbesar. | Wajib |
| F-14 | Label "Estimasi, bukan tagihan asli" selalu tampil di dekat biaya. | Wajib |
| F-15 | Pembaruan otomatis berkala tanpa memuat ulang halaman (lihat bagian 9.4). | Wajib |
| F-16 | Kondisi kosong dan kondisi error memiliki pesan yang menjelaskan penyebab dan langkah perbaikan. | Wajib |
| F-17 | Angka diformat gaya Indonesia (pemisah ribuan titik). | Sebaiknya |

### 7.4 Fase berikutnya

| ID | Kebutuhan | Fase |
|---|---|---|
| F-18 | Grafik tren harian dari endpoint `chart`. | v1.1 |
| F-19 | Sinkronisasi berkala ke database untuk riwayat jangka panjang. | v1.2 |
| F-20 | Ekspor CSV untuk periode terpilih. | v1.2 |
| F-21 | Peringatan bila estimasi biaya harian melewati ambang. | v2.0 |
| F-22 | Pembaruan real-time lewat SSE bila endpoint `stream` terverifikasi dan layak. | v2.0 |

## 8. Kebutuhan non-fungsional

| Aspek | Kebutuhan |
|---|---|
| **Performa** | Halaman termuat di bawah 2 detik; respons API internal dari cache di bawah 300 ms; respons tanpa cache bergantung pada 9Router. |
| **Ketahanan** | Token kedaluwarsa dipulihkan otomatis; kegagalan 9Router ditampilkan sebagai error yang jelas tanpa merusak halaman. |
| **Keamanan** | Lihat bagian 13. |
| **Kompatibilitas** | Browser modern; responsif dari lebar 360 px ke atas. |
| **Aksesibilitas** | Kontras teks memadai, fokus keyboard terlihat, status periode terbaca oleh pembaca layar (`aria-pressed`). |
| **Pemeliharaan** | Path endpoint, nama field login, dan kredensial berada di konfigurasi, bukan di kode. |
| **Biaya** | Tanpa database pada v1; cache berbasis file; paket Railway terendah. |
| **Observabilitas** | Kegagalan login atau upstream dicatat di log Laravel tanpa mencetak password atau token. |

## 9. Arsitektur

### 9.1 Gambaran umum

```
Browser
   |  HTTPS + login Basic
   v
mcx9route (Laravel, Railway)
   |-- Middleware DashboardAuth
   |-- UsageController  --> cache 10 dtk
   |-- NineRouterClient --> cache token 23 jam
   |  HTTPS + cookie auth_token
   v
9Router (blitz.cloud, di belakang Cloudflare)
   |-- POST /api/auth/login
   |-- GET  /api/usage/stats
   |-- GET  /api/usage/chart
```

### 9.2 Alur data normal

1. Browser membuka `/`; middleware memeriksa login; Blade menampilkan halaman.
2. JavaScript memanggil `/api/stats?period=...`.
3. Controller memvalidasi `period`, mencari di cache 10 detik.
4. Bila cache kosong, `NineRouterClient` mengambil token dari cache (atau login), memanggil 9Router, mengembalikan JSON.
5. Frontend merender kartu, bar provider, dan tabel model.

### 9.3 Alur pemulihan token

1. 9Router membalas 401 pada request data.
2. Client menghapus token dari cache.
3. Client login ulang dan mengulang request tepat satu kali.
4. Bila tetap gagal, controller mengembalikan 502 dengan pesan penyebab.

### 9.4 Keputusan: polling atau streaming

| Opsi | Kelebihan | Kekurangan |
|---|---|---|
| **Polling (dipilih untuk v1)** | Sederhana, stabil, cocok untuk satu pengguna | Tidak instan |
| Streaming (SSE) | Real-time | Setiap koneksi menahan satu proses PHP; endpoint `stream` belum diperiksa isinya |

**Keputusan v1:** polling. Interval awal 30 detik, direkomendasikan diturunkan menjadi 5 detik dengan cache backend 3 detik. Streaming dipertimbangkan di v2.0 setelah isi dan path endpoint `stream` terverifikasi dan terbukti memberi nilai tambah.

## 10. Struktur kode

| File | Peran |
|---|---|
| `app/Services/NineRouterClient.php` | Login, cache token, request ke 9Router, pemulihan 401 |
| `app/Http/Controllers/UsageController.php` | Validasi periode, cache 10 detik, endpoint `/api/stats` dan `/api/chart` |
| `app/Http/Middleware/DashboardAuth.php` | Login HTTP Basic untuk seluruh route |
| `resources/views/dashboard.blade.php` | Halaman dashboard (HTML, CSS, JS dalam satu berkas) |
| `routes/web.php` | Tiga route di bawah middleware |
| `config/services.php` | Pengaturan `ninerouter` dan `dashboard` dari `.env` |
| `bootstrap/app.php` | Pengaturan trusted proxies untuk Railway |

Tidak ada paket Composer tambahan dan tidak ada migrasi database pada v1.

## 11. Konfigurasi

| Variabel | Fungsi | Wajib |
|---|---|---|
| `APP_KEY` | Kunci aplikasi Laravel | Ya |
| `APP_ENV` | `production` | Ya |
| `APP_DEBUG` | `false` | Ya |
| `SESSION_DRIVER` | `file` | Ya |
| `CACHE_STORE` | `file` (Laravel 10 ke bawah: `CACHE_DRIVER`) | Ya |
| `NINE_ROUTER_URL` | URL dasar instance 9Router, tanpa `/v1` | Ya |
| `NINE_ROUTER_LOGIN_PATH` | Default `/api/auth/login` | Tidak |
| `NINE_ROUTER_LOGIN_FIELD` | Nama field password pada payload login (default `password`) | Tidak |
| `NINE_ROUTER_PASSWORD` | Password login dashboard 9Router | Ya |
| `STATS_PATH` | Default `/api/usage/stats` | Tidak |
| `CHART_PATH` | Default `/api/usage/chart` | Tidak |
| `DASH_USER` | Username login mcx9route | Tidak (default `admin`) |
| `DASH_PASSWORD` | Password login mcx9route | Ya |

Semua nilai rahasia hanya disimpan di Variables Railway dan `.env` lokal, tidak pernah di repositori.

## 12. Desain antarmuka

### 12.1 Prinsip

- Satu halaman, fokus pada angka.
- Tema gelap bernuansa biru-tua dengan aksen teal (token keluar), biru (token masuk), dan amber (biaya).
- Angka menggunakan huruf monospace dengan angka sejajar (tabular) agar mudah dibandingkan.
- Teks dalam bahasa Indonesia dengan huruf kapital kalimat biasa.

### 12.2 Tata letak

```
+----------------------------------------------------------+
| Pemakaian 9Router        [Hari ini|24 jam|7 hari|30 hari..]|
+----------------------------------------------------------+
| Permintaan | Token masuk | Token cache | Token keluar | Biaya |
|     27     |   930.099   |    93.120   |    13.182    | $1,23 |
|                                             Estimasi, bukan tagihan asli |
+----------------------------------------------------------+
| Per provider                                             |
| kiro        ██████████████████        10 permintaan $1,00 |
| codex       ████████████████           9 permintaan $0,12 |
+----------------------------------------------------------+
| Per model                                                |
| Model            Permintaan  Token masuk  Token keluar ...|
+----------------------------------------------------------+
```

### 12.3 Kondisi tampilan

| Kondisi | Tampilan |
|---|---|
| Memuat | Teks "Memuat data…" |
| Kosong | "Belum ada data pada periode ini." |
| Error | Kotak merah: apa yang gagal dan variabel mana yang perlu diperiksa |
| Mobile | Ringkasan menyusun ke bawah; tabel model dapat digeser horizontal |

## 13. Keamanan dan privasi

### 13.1 Model ancaman

| Ancaman | Mitigasi |
|---|---|
| Orang lain membuka mcx9route | Login Basic di seluruh route; gagal-aman bila password kosong; HTTPS dari Railway |
| Kebocoran password 9Router | Hanya di Variables Railway; tidak dikirim ke browser; tidak dicatat di log |
| Kebocoran token sesi (`auth_token`) | Token hanya di cache server; token tidak pernah dikembalikan ke frontend |
| Token atau cookie tertempel di chat, tangkapan layar, atau repositori | Hapus header `cookie` sebelum membagikan hasil Network; bila terlanjur terbagi, logout dan login ulang atau ganti password/secret 9Router |
| Penyalahgunaan parameter `period` | Daftar putih nilai |
| Error yang membocorkan konfigurasi | `APP_DEBUG=false`; pesan error hanya berisi penyebab umum |
| Serangan brute force pada login web | Password kuat; opsional pembatasan laju (rate limit) di fase berikutnya |
| Request server diblokir Cloudflare | Lihat bagian 17 |

### 13.2 Privasi

Data yang ditampilkan adalah statistik pemakaian milik sendiri (jumlah request, token, biaya, nama model dan provider). Aplikasi tidak menyimpan isi percakapan atau prompt.

## 14. Deployment di Railway

1. Pastikan repositori GitHub berisi proyek Laravel lengkap dan **tidak** memuat `.env`.
2. Buat proyek Railway dari repositori itu.
3. Isi seluruh variabel pada bagian 11 di tab Variables. Buat `APP_KEY` dengan `php artisan key:generate --show`.
4. Tambahkan `$middleware->trustProxies(at: '*');` di `bootstrap/app.php` agar HTTPS dan URL terbaca benar di belakang proxy.
5. Deploy, lalu buka domain Railway dan login.
6. Catatan: konfigurasi build PHP/Laravel di Railway belum diverifikasi terhadap dokumentasi terbaru. Bila build gagal, periksa log build dan dokumentasi Railway.

## 15. Pengujian dan kriteria penerimaan

### 15.1 Uji manual

| ID | Skenario | Hasil yang diharapkan |
|---|---|---|
| T-01 | Buka `/` tanpa login | Ditolak (401), muncul prompt login |
| T-02 | Login dengan password salah | Ditolak |
| T-03 | Login benar | Dashboard tampil dengan data |
| T-04 | Bandingkan angka dengan dashboard 9Router (periode sama) | Sama |
| T-05 | Ganti tiap periode | Data berubah; tidak ada error |
| T-06 | Hapus token dari cache (`php artisan cache:clear`) lalu muat ulang | Login otomatis ke 9Router berhasil |
| T-07 | Salah isi `NINE_ROUTER_PASSWORD` | Pesan error login ke 9Router yang jelas, halaman tidak rusak |
| T-08 | Isi `NINE_ROUTER_URL` dengan alamat salah | Pesan error jelas |
| T-09 | `DASH_PASSWORD` dikosongkan | Semua akses ditolak |
| T-10 | Buka di ponsel (lebar 360 px) | Tata letak terbaca; tabel dapat digeser |
| T-11 | Periksa sumber halaman dan respons API | Tidak ada password atau token 9Router |

### 15.2 Kriteria penerimaan MVP

MVP dianggap selesai bila T-01 sampai T-11 lolos pada lingkungan Railway, termasuk pemulihan otomatis setelah token dihapus (T-06).

## 16. Metrik keberhasilan

| Metrik | Target |
|---|---|
| Kesesuaian angka dengan dashboard 9Router | 100% |
| Akses tanpa login berhasil | 0 |
| Kredensial 9Router terlihat di sisi browser | 0 |
| Error 401 berulang karena token kedaluwarsa | 0 (pulih otomatis) |
| Waktu muat halaman utama | Di bawah 2 detik |

## 17. Risiko dan mitigasi

| Risiko | Kemungkinan | Dampak | Mitigasi |
|---|---|---|---|
| Cloudflare memblokir request dari server Railway (403/tantangan bot) | Sedang | Tinggi | Buat aturan di Cloudflare yang mengizinkan path `/api/*` dari mcx9route, atau gunakan mekanisme token layanan; uji lebih awal |
| Endpoint internal 9Router berubah setelah pembaruan | Sedang | Sedang | Path dan field login dapat dikonfigurasi; pesan error jelas; kunci versi 9Router bila memungkinkan |
| Respons login tidak menyertakan `auth_token` di header `set-cookie` | Rendah | Tinggi | Verifikasi lebih awal; sesuaikan cara pengambilan token |
| Data 9Router hilang saat restart | Sedang | Sedang | Fase v1.2: simpan riwayat di database |
| Biaya estimasi disalahpahami sebagai tagihan | Sedang | Rendah | Label estimasi selalu tampil |
| Kode belum pernah dijalankan | Tinggi (saat ini) | Sedang | Uji lokal dengan `php artisan serve` sebelum deploy |
| Polling rapat membebani 9Router | Rendah | Rendah | Cache backend; interval minimal 5 detik |
| Lisensi 9Router membatasi pemakaian turunan | Rendah | Sedang | Periksa lisensi repositori sebelum membagikan mcx9route ke orang lain |

## 18. Rencana rilis

| Tahap | Pekerjaan | Hasil |
|---|---|---|
| 0. Verifikasi | Konfirmasi nama field login, bentuk respons login, nilai `period`, path `chart` | Daftar asumsi terkonfirmasi |
| 1. Lokal | Pasang file ke proyek Laravel, uji dengan `php artisan serve` | T-01 sampai T-09 lolos di lokal |
| 2. Deploy | Deploy ke Railway, atur variabel dan trusted proxies | Dashboard dapat diakses di domain Railway |
| 3. Uji produksi | T-01 sampai T-11, uji Cloudflare | MVP diterima |
| 4. v1.1 | Grafik tren dari `chart` | Grafik tampil |
| 5. v1.2 | Database riwayat dan ekspor CSV | Riwayat tidak hilang |
| 6. v2.0 | Peringatan biaya; evaluasi streaming | Sesuai kebutuhan |

## 19. Asumsi dan dependensi

**Asumsi**
- Nama field password pada login 9Router adalah `password` (belum dikonfirmasi).
- Login 9Router mengirim token lewat header `set-cookie` bernama `auth_token`.
- Nilai `period` selain `today` mengikuti pola `24h`, `7d`, `30d`, `60d`, `all`.
- Hanya satu pengguna yang memakai mcx9route.
- Laravel 11 atau 12 dengan PHP 8.2+.

**Dependensi**
- Instance 9Router di blitz.cloud tetap aktif dan dapat dijangkau dari Railway.
- Cloudflare tidak memblokir request dari server.
- Layanan Railway untuk menjalankan aplikasi Laravel.

## 20. Pertanyaan terbuka

1. Apa nama field pada payload request login (tab Payload)?
2. Apakah respons login benar-benar mengirim `set-cookie: auth_token=...`?
3. Apa path lengkap dan struktur JSON endpoint `chart`?
4. Apa isi dan path endpoint `stream`, dan apakah layak dijadikan sumber data real-time?
5. Apakah Cloudflare di depan 9Router menolak request dari server Railway?
6. Apakah blitz.cloud menyimpan data 9Router secara persisten di antara restart?
7. Apakah mcx9route akan tetap pribadi, atau dibagikan ke orang lain kelak (memengaruhi autentikasi dan lisensi)?

## 21. Lampiran

### 21.1 Glosarium

| Istilah | Arti |
|---|---|
| 9Router | AI router open source yang meneruskan permintaan ke banyak provider AI dan memiliki dashboard usage |
| Provider | Layanan AI di balik 9Router (misalnya kiro, codex, ollama) |
| Token masuk / keluar / cache | Token prompt, token hasil (completion), dan token yang dilayani dari cache |
| Estimasi biaya | Perhitungan biaya seandainya dibayar sebagai API berbayar; bukan tagihan nyata |
| SSE | Server-Sent Events, aliran data satu arah dari server ke browser |
| Polling | Browser meminta data berulang pada interval tetap |
| JWT | Format token sesi yang dipakai cookie `auth_token` |

### 21.2 Catatan keamanan operasional

Saat memeriksa request di tab Network, jangan membagikan header `cookie` atau `authorization` secara utuh. Bila cookie `auth_token` terlanjur terbagikan, anggap sesi itu terbuka: logout dan login ulang, dan bila perlu ganti password atau secret 9Router.
