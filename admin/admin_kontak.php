<?php
// Memulai session untuk menyimpan data pengguna yang login
session_start();
// Menyertakan file konfigurasi koneksi database
include 'config.php';
// Menyertakan file header untuk bagian atas halaman
include 'header.php';

// Set page title
$page_title = "Pesan Kontak - PT INTIM";

// 1. PROTEKSI HALAMAN ADMIN
// Memeriksa apakah pengguna sudah login dan memiliki role 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika tidak admin, redirect ke halaman login
    header("Location: login.php");
    exit; // Menghentikan eksekusi script lebih lanjut
}

// AMBIL NAMA DARI SESSION
// Mengambil username dari session untuk ditampilkan
$username = $_SESSION['username'];

// 2. LOGIKA HAPUS PESAN
// Memproses penghapusan pesan jika ada parameter delete_id di URL
if (isset($_GET['delete_id'])) {
    // Mencegah SQL Injection dengan meng-escape string
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // Query untuk menghapus pesan berdasarkan ID
    $delete_query = "DELETE FROM contact_messages WHERE id = '$delete_id'";
    
    // Eksekusi query dan beri feedback
    if (mysqli_query($conn, $delete_query)) {
        // Jika berhasil, tampilkan alert dan refresh halaman
        echo "<script>alert('Pesan berhasil dihapus!'); window.location='admin_kontak.php';</script>";
    } else {
        // Jika gagal, tampilkan error dari database
        echo "<script>alert('Gagal menghapus pesan: " . mysqli_error($conn) . "');</script>";
    }
}

// 3. FUNGSI UNTUK FORMAT TANGGAL
// Fungsi untuk mengubah format datetime ke format Indonesia yang lebih singkat
function formatDateTime($datetime) {
    // Handle data kosong atau null
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') return '-';
    
    // Konversi string ke timestamp
    $timestamp = strtotime($datetime);
    
    // Array nama bulan dalam bahasa Indonesia (singkat)
    $bulanIndo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    
    // Format: "01 Jan 2023 14:30"
    return date('d', $timestamp) . ' ' . $bulanIndo[(int)date('m', $timestamp) - 1] . ' ' . date('Y', $timestamp) . ' ' . date('H:i', $timestamp);
}

// 4. SETUP PAGINATION
$limit = 15; // Jumlah data per halaman
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Halaman saat ini, default 1
$offset = ($page - 1) * $limit; // Hitung offset untuk query

// 5. LOGIKA PENCARIAN
// Mengambil keyword pencarian dari parameter GET
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_clause = ''; // Inisialisasi klausa WHERE

// Jika ada pencarian, buat klausa WHERE untuk mencari di beberapa kolom
if ($search_query) {
    $where_clause = "WHERE name LIKE '%$search_query%' OR email LIKE '%$search_query%' OR subject LIKE '%$search_query%' OR message LIKE '%$search_query%'";
}

// 6. MENGHITUNG TOTAL DATA DAN AMBIL DATA
// Query untuk menghitung total data (untuk pagination)
$count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM contact_messages $where_clause");
$total_messages = mysqli_fetch_assoc($count_result)['total']; // Ambil jumlah total
$total_pages = ceil($total_messages / $limit); // Hitung total halaman

// Query untuk mengambil data dengan pagination
$query = "SELECT * FROM contact_messages $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query); // Eksekusi query

// 7. QUERY STATISTIK
// Query untuk statistik: total, hari ini, dan 7 hari terakhir
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week
    FROM contact_messages";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result); // Ambil hasil statistik
?>

<?php include 'template_start.php'; // Menyertakan template awal (mungkin berisi CSS/JS) ?>

<main class="main-content">
    <header class="top-header">
        <div>
            <h1 class="page-title">Pesan Kontak</h1>
            <p style="color: var(--text-light); font-size: 14px;">Kelola pesan dari pengunjung website</p>
        </div>
    </header>

    <div class="content-area">
        <!-- STATISTIK KARTU -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total"><i class="fas fa-envelope"></i></div>
                <div class="stat-content">
                    <h3><?= $stats['total'] ?? 0 ?></h3> <!-- Total semua pesan -->
                    <p>Total Pesan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-content">
                    <h3><?= $stats['today'] ?? 0 ?></h3> <!-- Pesan hari ini -->
                    <p>Hari Ini</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon review"><i class="fas fa-calendar-week"></i></div>
                <div class="stat-content">
                    <h3><?= $stats['week'] ?? 0 ?></h3> <!-- Pesan 7 hari terakhir -->
                    <p>7 Hari Terakhir</p>
                </div>
            </div>
        </div>

        <!-- BARIS AKSI: SEARCH DAN RESET -->
        <div class="action-bar">
            <form method="GET" class="search-box">
                <!-- Input pencarian -->
                <input type="text" name="search" class="search-input" placeholder="Cari pesan..." value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </form>
            <!-- Tombol reset hanya muncul jika ada pencarian -->
            <?php if ($search_query): ?>
                <a href="admin_kontak.php" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </div>

        <!-- TABEL DATA -->
        <div class="table-container">
            <div class="table-responsive">
                <?php if (mysqli_num_rows($result) > 0): // Jika ada data ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pengirim</th>
                            <th>Subjek</th>
                            <th>Pesan</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($message = mysqli_fetch_assoc($result)): // Loop setiap pesan
                            // Ambil huruf pertama nama untuk avatar
                            $firstLetter = strtoupper(substr($message['name'], 0, 1));
                        ?>
                        <tr>
                            <!-- Kolom Pengirim dengan avatar -->
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                        <?= $firstLetter ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($message['name']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-light);"><?= htmlspecialchars($message['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($message['subject']) ?></td> <!-- Subjek pesan -->
                            <td>
                                <!-- Pesan dipotong hanya 1 baris -->
                                <div style="font-size: 13px; color: var(--text-light); display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; max-width: 300px;">
                                    <?= htmlspecialchars($message['message']) ?>
                                </div>
                            </td>
                            <td><div style="font-size: 12px;"><?= formatDateTime($message['created_at']) ?></div></td> <!-- Tanggal -->
                            <td>
                                <!-- Tombol aksi -->
                                <div class="action-buttons">
                                    <!-- Tombol hapus dengan konfirmasi JavaScript -->
                                    <button class="action-btn delete" onclick="confirmDelete(<?= $message['id'] ?>, '<?= htmlspecialchars(addslashes($message['name'])) ?>')" title="Hapus"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: // Jika tidak ada data ?>
                    <div style="padding: 40px; text-align: center; color: var(--text-light);">
                        <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                        <p>Tidak ada pesan yang ditemukan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
// FUNGSI KONFIRMASI HAPUS
// Menampilkan dialog konfirmasi sebelum menghapus
function confirmDelete(id, name) {
    if (confirm('Apakah Anda yakin ingin menghapus pesan dari ' + name + '?')) {
        // Jika OK, redirect ke URL hapus
        window.location.href = 'admin_kontak.php?delete_id=' + id;
    }
}

</script>

<?php 

?>