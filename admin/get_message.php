<?php
// Mulai session untuk menyimpan data login pengguna
session_start();

// Hubungkan file konfigurasi database
include 'config.php';

// CEK APAKAH PENGGUNA ADALAH ADMIN
// Memeriksa apakah role/level pengguna adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak!"); // Hentikan eksekusi jika bukan admin
}

// AMBIL ID PESAN DARI PARAMETER URL
// Mengambil parameter 'id' dari URL dan mengkonversi ke integer untuk keamanan
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// CEK JIKA ID VALID (lebih besar dari 0)
if ($id > 0) {
    // QUERY UNTUK MENGAMBIL DATA PESAN DARI DATABASE
    // Mencari pesan berdasarkan ID yang diberikan
    $query = "SELECT * FROM contact_messages WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    // CEK APAKAH PESAN DITEMUKAN
    // mysqli_fetch_assoc() mengembalikan array asosiatif jika data ditemukan
    if ($message = mysqli_fetch_assoc($result)) {
        // FUNGSI UNTUK MEMFORMAT TANGGAL KE BAHASA INDONESIA
        function format_tanggal($datetime) {
            // CEK JIKA TANGGAL KOSONG ATAU TIDAK VALID
            if (empty($datetime) || $datetime == '0000-00-00 00:00:00') return '-';
            
            // KONVERSI STRING DATETIME KE TIMESTAMP
            $timestamp = strtotime($datetime);
            
            // ARRAY NAMA BULAN DALAM BAHASA INDONESIA
            $bulan = array(
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            );
            
            // EKSTRAK KOMPONEN TANGGAL
            $tanggal = date('d', $timestamp);      // Hari (01-31)
            $bulanNum = date('m', $timestamp);     // Bulan (01-12)
            $tahun = date('Y', $timestamp);        // Tahun (2024)
            $jam = date('H:i', $timestamp);        // Jam:Menit (14:30)
            $hari = date('l', $timestamp);         // Hari dalam bahasa Inggris (Monday)
            
            // ARRAY KONVERSI HARI DARI BAHASA INGGRIS KE INDONESIA
            $hariIndo = [
                'Sunday' => 'Minggu',
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu'
            ];
            
            // FORMAT TANGGAL FINAL: "Senin, 15 Januari 2024 pukul 14:30"
            return $hariIndo[$hari] . ', ' . $tanggal . ' ' . $bulan[$bulanNum] . ' ' . $tahun . ' pukul ' . $jam;
        }
        
        // AMBIL HURUF PERTAMA NAMA PENGIRIM UNTUK AVATAR
        // Contoh: "John Doe" → "J"
        $first_letter = strtoupper(substr($message['name'], 0, 1));
        
        // MULAI OUTPUT HTML/CSS
        ?>
        <style>
            /* STYLING UNTUK DETAIL PESAN */
            .message-detail {
                padding: 20px; /* Ruang padding di sekeliling konten */
            }
            
            /* STYLING UNTUK INFORMASI PENGIRIM */
            .sender-info {
                display: flex;          /* Layout flexbox untuk sejajar horizontal */
                align-items: center;    /* Rata tengah vertikal */
                gap: 15px;             /* Jarak antar elemen */
                margin-bottom: 20px;   /* Jarak bawah */
                padding-bottom: 20px;  /* Padding bawah */
                border-bottom: 1px solid var(--gray-200); /* Garis pemisah */
            }
            
            /* STYLING UNTUK AVATAR BULAT BESAR */
            .avatar-large {
                width: 60px;           /* Lebar avatar */
                height: 60px;          /* Tinggi avatar */
                background: var(--accent); /* Warna background */
                border-radius: 50%;    /* Membuat lingkaran */
                display: flex;         /* Flexbox untuk pusatkan huruf */
                align-items: center;   /* Rata tengah vertikal */
                justify-content: center; /* Rata tengah horizontal */
                color: white;          /* Warna teks putih */
                font-weight: 600;      /* Ketebalan font */
                font-size: 24px;       /* Ukuran font */
                flex-shrink: 0;        /* Cegah penyusutan */
            }
            
            /* STYLING UNTUK DETAIL PENGIRIM */
            .sender-details h3 {
                margin: 0 0 5px 0;    /* Margin untuk judul */
                color: var(--primary); /* Warna teks primary */
            }
            .sender-details p {
                margin: 0;             /* Hapus margin default */
                color: var(--text-light); /* Warna teks light */
                font-size: 14px;       /* Ukuran font kecil */
            }
            
            /* STYLING UNTUK META DATA PESAN */
            .message-meta {
                background: var(--gray-50); /* Warna background abu-abu muda */
                padding: 15px;         /* Ruang padding dalam */
                border-radius: var(--radius); /* Sudut membulat */
                margin-bottom: 20px;   /* Jarak bawah */
            }
            
            /* STYLING UNTUK SETIAP ITEM META DATA */
            .meta-item {
                display: flex;         /* Layout flexbox */
                align-items: center;   /* Rata tengah vertikal */
                gap: 10px;            /* Jarak antara icon dan teks */
                margin-bottom: 10px;  /* Jarak bawah antar item */
                font-size: 14px;      /* Ukuran font kecil */
            }
            .meta-item:last-child {
                margin-bottom: 0;     /* Hapus margin untuk item terakhir */
            }
            .meta-item i {
                color: var(--accent); /* Warna icon accent */
                width: 20px;          /* Lebar tetap untuk icon */
            }
            
            /* STYLING UNTUK KONTEN PESAN */
            .message-content {
                background: var(--white); /* Background putih */
                padding: 20px;         /* Ruang padding dalam */
                border-radius: var(--radius); /* Sudut membulat */
                border: 1px solid var(--gray-200); /* Border abu-abu */
                line-height: 1.6;      /* Spasi antar baris */
                white-space: pre-wrap; /* Pertahankan format baris baru */
                max-height: 300px;     /* Tinggi maksimal */
                overflow-y: auto;      /* Scroll vertikal jika konten panjang */
            }
        </style>
        
        <!-- STRUKTUR HTML DETAIL PESAN -->
        <div class="message-detail">
            <!-- BAGIAN INFORMASI PENGIRIM -->
            <div class="sender-info">
                <!-- AVATAR DENGAN HURUF PERTAMA NAMA -->
                <div class="avatar-large">
                    <?= $first_letter ?> <!-- Tampilkan huruf pertama nama -->
                </div>
                
                <!-- DETAIL NAMA DAN EMAIL -->
                <div class="sender-details">
                    <h3><?= htmlspecialchars($message['name']) ?></h3> <!-- Nama pengirim -->
                    <p><?= htmlspecialchars($message['email']) ?></p> <!-- Email pengirim -->
                </div>
            </div>
            
            <!-- BAGIAN META DATA PESAN -->
            <div class="message-meta">
                <!-- ITEM 1: SUBJEK PESAN -->
                <div class="meta-item">
                    <i class="fas fa-tag"></i> <!-- Icon tag -->
                    <span><strong>Subjek:</strong> <?= htmlspecialchars($message['subject']) ?></span>
                </div>
                
                <!-- ITEM 2: TANGGAL PENGIRIMAN -->
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i> <!-- Icon kalender -->
                    <span><strong>Tanggal:</strong> <?= format_tanggal($message['created_at']) ?></span>
                </div>
                
                <!-- ITEM 3: STATUS PESAN (SUDAH/BELUM DIBACA) -->
                <div class="meta-item">
                    <i class="fas fa-circle"></i> <!-- Icon lingkaran -->
                    <span><strong>Status:</strong> 
                        <span style="color: <?= $message['is_read'] ? 'var(--success)' : 'var(--warning)' ?>;">
                            <?= $message['is_read'] ? 'Sudah Dibaca' : 'Belum Dibaca' ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <!-- BAGIAN ISI/KONTEN PESAN -->
            <div class="message-content">
                <?= nl2br(htmlspecialchars($message['message'])) ?>
                <!-- 
                    nl2br(): Mengubah newline (\n) menjadi <br> tag 
                    htmlspecialchars(): Mencegah XSS (Cross-Site Scripting)
                -->
            </div>
            
            <!-- BAGIAN TOMBOL AKSI -->
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <!-- TOMBOL BALAS VIA EMAIL -->
                <a href="mailto:<?= htmlspecialchars($message['email']) ?>?subject=Balasan: <?= htmlspecialchars($message['subject']) ?>" 
                   class="btn btn-primary" style="flex: 1; justify-content: center;">
                    <i class="fas fa-reply"></i> Balas via Email
                </a>
                
                <!-- TOMBOL HAPUS PESAN DENGAN KONFIRMASI -->
                <a href="kontak_admin.php?hapus=<?= $message['id'] ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('Hapus pesan ini?')" <!-- Konfirmasi sebelum hapus -->
                   style="flex: 1; justify-content: center;">
                    <i class="fas fa-trash"></i> Hapus Pesan
                </a>
            </div>
        </div>
        <?php
    } else {
        // TAMPILKAN PESAN ERROR JIKA PESAN TIDAK DITEMUKAN
        echo '<div style="text-align: center; padding: 40px;">
                <i class="fas fa-exclamation-circle"></i>
                <p>Pesan tidak ditemukan.</p>
              </div>';
    }
} else {
    // TAMPILKAN PESAN ERROR JIKA ID TIDAK VALID
    echo '<div style="text-align: center; padding: 40px;">
            <i class="fas fa-exclamation-circle"></i>
            <p>ID pesan tidak valid.</p>
          </div>';
}
?>