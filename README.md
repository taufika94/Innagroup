# Company Profile — PT Innagroup Textile Manufacture

Website company profile full-stack untuk PT Innagroup Textile Manufacture, 
perusahaan manufaktur tekstil di Klaten, Jawa Tengah.

## 🎯 Latar Belakang

Website lama perusahaan memiliki tampilan kuno, tidak responsif di mobile, 
dan tidak memiliki sistem pengelolaan konten dinamis. Proyek ini 
menggantinya dengan website modern, responsif, dan dashboard admin 
yang memungkinkan pengelolaan konten mandiri tanpa mengubah kode.

## ✨ Fitur Utama

### Publik
- Koleksi produk dengan filter & pencarian
- Detail produk dengan varian warna & multiple gambar
- Halaman karir dengan lowongan aktif
- Form lamaran kerja online (upload CV & foto)
- Form kontak AJAX real-time
- Galeri interaktif

### Admin Dashboard
- CRUD produk (multiple gambar & varian warna)
- CRUD karir (toggle aktif/nonaktif)
- CRUD galeri (deteksi duplikasi)
- CRUD kegiatan (status otomatis)
- Manajemen user (reset password, proteksi hapus sendiri)
- Autentikasi dengan bcrypt password hashing

## 📊 Hasil

| Metrik | Sebelum | Sesudah |
|--------|---------|---------|
| Jumlah fitur | ~15 | **45+** |
| Skala penilaian klien | 2/10 | **9/10** |
| Responsivitas mobile | ❌ | ✅ (5 breakpoint) |
| Black box test | - | **171 skenario (100% pass)** |

## ⚙️ Tech Stack

- **Frontend:** HTML5, CSS3 (Grid & Flexbox), JavaScript (AJAX, DOM), Google Fonts, FontAwesome
- **Backend:** PHP 8.1 (session, password hashing, CRUD, proteksi SQL injection)
- **Database:** MySQL (7 tabel relasional)
- **Tools:** Git, Google PageSpeed Insights, XAMPP

## 🗄️ Struktur Database

7 tabel relasional:
- `users` — admin & user
- `produk` — produk tekstil
- `karir` — lowongan kerja
- `lamaran` — lamaran kerja online
- `berita_galeri` — galeri
- `kegiatan` — kegiatan perusahaan
- `contact_messages` — pesan kontak

## 🚀 Cara Menjalankan

### Prasyarat
- PHP 8.1+
- MySQL 5.7+
- XAMPP / Laragon / MAMP

