<?php
// Mulai session untuk menyimpan data login pengguna
session_start();

// Hubungkan file konfigurasi database
include 'config.php';

// Hubungkan file header (mungkin berisi kode awal HTML/CSS/JS)
include 'header.php';

// --- PROTEKSI HALAMAN ADMIN ---
// Cek jika pengguna belum login atau bukan admin, redirect ke halaman login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// AMBIL NAMA PENGGUNA DARI SESSION
$username = $_SESSION['username'];

// Set judul halaman untuk tag <title>
$page_title = "Kelola Karir - PT INTIM";

// Variabel untuk pesan notifikasi
$pesan = '';          // Pesan sukses
$pesan_error = '';    // Pesan error
$karir_id = 0;        // ID lowongan (untuk mode edit)
$mode = 'tambah';     // Mode default: tambah data
$judul_form = 'Tambah Lowongan Baru'; // Judul form default

// CEK MODE EDIT - apakah ada parameter edit di URL
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $karir_id = intval($_GET['edit']); // Konversi parameter ke integer
    $mode = 'edit';                    // Ubah mode ke edit
    $judul_form = 'Edit Lowongan';     // Ubah judul form
    
    // AMBIL DATA LOWONGAN DARI DATABASE berdasarkan ID
    $query_select = "SELECT * FROM karir WHERE id = $karir_id";
    $result_select = mysqli_query($conn, $query_select);
    $karir_data = mysqli_fetch_assoc($result_select);
    
    // JIKA DATA TIDAK DITEMUKAN, kembali ke mode tambah
    if (!$karir_data) {
        $pesan_error = "Lowongan tidak ditemukan!";
        $mode = 'tambah';
        $judul_form = 'Tambah Lowongan Baru';
    }
}

// PROSES HAPUS SATU LOWONGAN (Single Delete)
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $hapus_id = intval($_GET['hapus']); // Ambil ID dari URL parameter
    
    // QUERY HAPUS DARI DATABASE
    $delete_query = "DELETE FROM karir WHERE id = $hapus_id";
    if (mysqli_query($conn, $delete_query)) {
        $pesan = "Lowongan berhasil dihapus!";
    } else {
        $pesan_error = "Gagal menghapus lowongan: " . mysqli_error($conn);
    }
    
}

// PROSES HAPUS MASAL (Bulk Delete) - hapus banyak lowongan sekaligus
if (isset($_POST['hapus_masal'])) {
    // CEK APAKAH ADA LOWONGAN YANG DIPILIH
    if (!empty($_POST['pilih_hapus'])) {
        $ids = $_POST['pilih_hapus']; // Array ID yang dipilih
        $jumlah_hapus = count($ids);  // Hitung jumlah yang akan dihapus
        // SANITASI ID: konversi semua ID menjadi integer
        $ids_clean = array_map(function($id) {
            return intval($id);
        }, $ids);
        
        // FILTER ID YANG VALID (lebih besar dari 0)
        $ids_clean = array_filter($ids_clean, function($id) {
            return $id > 0;
        });
        
        // JIKA ADA ID YANG VALID
        if (!empty($ids_clean)) {
            $string_ids = implode(',', $ids_clean); // Gabungkan ID menjadi string
            
            // QUERY HAPUS DARI DATABASE
            $query_hapus = "DELETE FROM karir WHERE id IN ($string_ids)";
            if (mysqli_query($conn, $query_hapus)) {
                $pesan = "Berhasil menghapus $jumlah_hapus lowongan!";
            } else {
                $pesan_error = "Gagal menghapus data: " . mysqli_error($conn);
            }
        }
    } else {
        $pesan_error = "Pilih minimal satu lowongan untuk dihapus!";
    }
}

// PROSES UBAH STATUS (Toggle Status) - aktif/nonaktif
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $toggle_id = intval($_GET['toggle']); // Ambil ID dari URL
    
    // TOGGLE STATUS is_active (1 menjadi 0, 0 menjadi 1)
    $query_toggle = "UPDATE karir SET is_active = NOT is_active WHERE id = $toggle_id";
    if (mysqli_query($conn, $query_toggle)) {
        $pesan = "Status lowongan berhasil diubah!";
    } else {
        $pesan_error = "Gagal mengubah status: " . mysqli_error($conn);
    }
    
    
}

// PROSES SIMPAN DATA (Upload/Edit Karir) saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // ESCAPE SEMUA INPUT UNTUK MENCEGAH SQL INJECTION
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $requirements = mysqli_real_escape_string($conn, $_POST['requirements']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);
    $is_active = isset($_POST['is_active']) ? 1 : 0; // Checkbox, 1 jika dicentang
    
    // MODE TAMBAH DATA BARU
    if ($mode === 'tambah') {
        // QUERY INSERT KE DATABASE
        $query = "INSERT INTO karir (position, department, location, type, description, requirements, deadline, is_active, created_at) 
                 VALUES ('$position', '$department', '$location', '$type', '$description', '$requirements', '$deadline', $is_active, NOW())";
        
        if (mysqli_query($conn, $query)) {
            $pesan = "Lowongan berhasil ditambahkan!";
            $_POST = []; // Kosongkan form setelah submit berhasil
        } else {
            $pesan_error = "Gagal menyimpan ke database: " . mysqli_error($conn);
        }
    }
    // MODE EDIT DATA YANG SUDAH ADA
    elseif ($mode === 'edit' && $karir_id > 0) {
        // QUERY UPDATE DATABASE
        $query = "UPDATE karir SET 
                 position = '$position',
                 department = '$department',
                 location = '$location',
                 type = '$type',
                 description = '$description',
                 requirements = '$requirements',
                 deadline = '$deadline',
                 is_active = $is_active,
                 updated_at = NOW()
                 WHERE id = $karir_id";
        
        if (mysqli_query($conn, $query)) {
            $pesan = "Lowongan berhasil diperbarui!";
            // REFRESH DATA untuk ditampilkan di form
            $result_select = mysqli_query($conn, "SELECT * FROM karir WHERE id = $karir_id");
            $karir_data = mysqli_fetch_assoc($result_select);
        } else {
            $pesan_error = "Gagal memperbarui database: " . mysqli_error($conn);
        }
    }
}

// LOGIKA PENCARIAN & FILTER DATA
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$dept_filter = isset($_GET['department']) ? mysqli_real_escape_string($conn, $_GET['department']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// QUERY DASAR UNTUK MENGAMBIL DATA KARIR
$query_karir = "SELECT * FROM karir WHERE 1=1";

// TAMBAHKAN KONDISI PENCARIAN JIKA ADA
if ($search != '') {
    $query_karir .= " AND (position LIKE '%$search%' OR department LIKE '%$search%' OR description LIKE '%$search%')";
}

// TAMBAHKAN FILTER DEPARTEMEN JIKA DIPILIH
if ($dept_filter != '') {
    $query_karir .= " AND department = '$dept_filter'";
}

// TAMBAHKAN FILTER STATUS
if ($status_filter == 'active') {
    $query_karir .= " AND is_active = 1";
} elseif ($status_filter == 'inactive') {
    $query_karir .= " AND is_active = 0";
}

// TAMBAHKAN PENGURUTAN DATA
if ($sort_order == 'newest') {
    $query_karir .= " ORDER BY created_at DESC"; // Urutkan berdasarkan tanggal dibuat (terbaru)
} elseif ($sort_order == 'deadline') {
    $query_karir .= " ORDER BY deadline ASC"; // Urutkan berdasarkan deadline (terdekat)
} elseif ($sort_order == 'az') {
    $query_karir .= " ORDER BY position ASC"; // Urutkan berdasarkan posisi (A-Z)
}

// EKSEKUSI QUERY UTAMA
$result_karir = mysqli_query($conn, $query_karir);

// QUERY UNTUK MENDAPATKAN DAFTAR DEPARTEMEN UNIK
$query_department = "SELECT DISTINCT department FROM karir WHERE department IS NOT NULL AND department != '' ORDER BY department";
$result_department = mysqli_query($conn, $query_department);

// AMBIL PESAN DARI URL PARAMETER (jika ada redirect sebelumnya)
if (isset($_GET['pesan']) && !empty($_GET['pesan'])) {
    $pesan = urldecode($_GET['pesan']);
}
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $pesan_error = urldecode($_GET['error']);
}

// HITUNG STATISTIK UNTUK DASHBOARD
$total_karir = mysqli_num_rows($result_karir); // Total semua lowongan
$karir_aktif = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM karir WHERE is_active = 1")); // Lowongan aktif
$karir_kadaluarsa = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM karir WHERE deadline < CURDATE()")); // Lowongan kadaluarsa
$karir_deadline_dekat = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM karir WHERE deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")); // Deadline dekat
$total_department = mysqli_num_rows($result_department); // Total departemen unik

// FUNGSI UNTUK FORMAT TANGGAL INDONESIA
function tgl_indo($date) {
    if (empty($date) || $date == '0000-00-00') return '-';
    $bulan = array(
        'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    );
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $bulan[(int)date('m', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}

// FUNGSI UNTUK MENGECEK APAKAH DEADLINE SUDAH LEWAT
function isExpired($date) {
    if (empty($date) || $date == '0000-00-00') return false;
    return strtotime($date) < strtotime(date('Y-m-d'));
}
?>

<?php include 'template_start.php'; ?>

<main class="main-content">
    <header class="top-header">
        <div>
            <h1 class="page-title">Kelola Lowongan Karir</h1>
            <p style="color: var(--text-light); font-size: 14px;">Upload dan kelola lowongan pekerjaan PT INTIM</p>
        </div>
        
        <div class="header-actions">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="content-area">
        <!-- PESAN NOTIFIKASI (Sukses/Error) -->
        <?php if ($pesan): ?>
            <div class="message-alert success" id="messageAlert">
                <i class="fas fa-check-circle"></i> 
                <span><?= $pesan ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($pesan_error): ?>
            <div class="message-alert error" id="messageAlert">
                <i class="fas fa-exclamation-circle"></i> 
                <span><?= $pesan_error ?></span>
            </div>
        <?php endif; ?>

        <!-- KARTU STATISTIK - Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_karir ?></h3>
                    <p>Total Lowongan</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $karir_aktif ?></h3>
                    <p>Aktif</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $karir_deadline_dekat ?></h3>
                    <p>Deadline Dekat</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon review">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $karir_kadaluarsa ?></h3>
                    <p>Kadaluarsa</p>
                </div>
            </div>
        </div>

        <!-- BARIS AKSI - Pencarian dan tombol tambah -->
        <div class="action-bar" style="margin-bottom: 20px;">
            <div class="search-box">
                <form action="" method="GET">
                    <input type="text" name="search" class="search-input" placeholder="Cari posisi atau departemen..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="btn-group">
                <a href="?add=true" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Lowongan
                </a>
            </div>
        </div>

        <!-- BAGIAN FILTER - Filter dan sorting data -->
        <div class="filter-section" style="margin-bottom: 20px;">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">Departemen</label>
                    <select name="department" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Departemen</option>
                        <?php mysqli_data_seek($result_department, 0); while($dept = mysqli_fetch_assoc($result_department)): ?>
                            <option value="<?= htmlspecialchars($dept['department']) ?>" <?= $dept_filter == $dept['department'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['department']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="inactive" <?= $status_filter == 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Urutkan</label>
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="newest" <?= $sort_order == 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="deadline" <?= $sort_order == 'deadline' ? 'selected' : '' ?>>Deadline Terdekat</option>
                        <option value="az" <?= $sort_order == 'az' ? 'selected' : '' ?>>A-Z</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <div class="filter-buttons">
                        <span style="font-size: 14px; color: var(--text-light);">
                            Total: <b><?= $total_karir ?></b>
                        </span>
                        <?php if ($search != '' || $dept_filter != '' || $status_filter != ''): ?>
                            <a href="admin_karir.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Hapus Filter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- GRID KONTEN UTAMA - Form dan tabel dalam grid -->
        <div style="display: flex; flex-direction: column; gap: 30px; margin-bottom: 40px;">
            <!-- FORM TAMBAH/EDIT (hanya tampil jika mode tambah atau edit) -->
            <?php if (isset($_GET['add']) || $mode === 'edit'): ?>
            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas <?= $mode === 'edit' ? 'fa-edit' : 'fa-plus' ?>"></i>
                    <?= $mode === 'edit' ? 'Edit Lowongan' : 'Tambah Lowongan Baru' ?>
                </h2>
                
                <form action="" method="POST">
                    <input type="hidden" name="mode" value="<?= $mode ?>">
                    
                    <div class="form-group">
                        <label for="position" class="form-label required">
                            <i class="fas fa-user-tie"></i> Posisi / Jabatan
                        </label>
                        <input type="text" id="position" name="position" class="form-control" required 
                               value="<?= $mode === 'edit' ? htmlspecialchars($karir_data['position']) : (isset($_POST['position']) ? htmlspecialchars($_POST['position']) : '') ?>"
                               placeholder="Contoh: Staff Administrasi">
                    </div>
                    
                    <div class="form-group">
                        <label for="department" class="form-label required">
                            <i class="fas fa-building"></i> Departemen
                        </label>
                        <select id="department" name="department" class="form-control" required>
                            <option value="">-- Pilih Departemen --</option>
                            <option value="IT" <?= ($mode === 'edit' && $karir_data['department'] == 'IT') || (isset($_POST['department']) && $_POST['department'] == 'IT') ? 'selected' : '' ?>>IT</option>
                            <option value="HRD" <?= ($mode === 'edit' && $karir_data['department'] == 'HRD') || (isset($_POST['department']) && $_POST['department'] == 'HRD') ? 'selected' : '' ?>>HRD</option>
                            <option value="Produksi" <?= ($mode === 'edit' && $karir_data['department'] == 'Produksi') || (isset($_POST['department']) && $_POST['department'] == 'Produksi') ? 'selected' : '' ?>>Produksi</option>
                            <option value="Marketing" <?= ($mode === 'edit' && $karir_data['department'] == 'Marketing') || (isset($_POST['department']) && $_POST['department'] == 'Marketing') ? 'selected' : '' ?>>Marketing</option>
                            <option value="Keuangan" <?= ($mode === 'edit' && $karir_data['department'] == 'Keuangan') || (isset($_POST['department']) && $_POST['department'] == 'Keuangan') ? 'selected' : '' ?>>Keuangan</option>
                            <?php 
                            mysqli_data_seek($result_department, 0);
                            while($dept = mysqli_fetch_assoc($result_department)): 
                                if (!empty($dept['department']) && !in_array($dept['department'], ['IT', 'HRD', 'Produksi', 'Marketing', 'Keuangan'])) {
                                    $selected = ($mode === 'edit' && $karir_data['department'] == $dept['department']) || (isset($_POST['department']) && $_POST['department'] == $dept['department']);
                            ?>
                                <option value="<?= htmlspecialchars($dept['department']) ?>" <?= $selected ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['department']) ?>
                                </option>
                            <?php 
                                }
                            endwhile; 
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="type" class="form-label required">
                                <i class="fas fa-clock"></i> Tipe Pekerjaan
                            </label>
                            <select id="type" name="type" class="form-control" required>
                                <option value="">-- Pilih Tipe --</option>
                                <option value="Full-time" <?= ($mode === 'edit' && $karir_data['type'] == 'Full-time') || (isset($_POST['type']) && $_POST['type'] == 'Full-time') ? 'selected' : '' ?>>Full-time</option>
                                <option value="Part-time" <?= ($mode === 'edit' && $karir_data['type'] == 'Part-time') || (isset($_POST['type']) && $_POST['type'] == 'Part-time') ? 'selected' : '' ?>>Part-time</option>
                                <option value="Contract" <?= ($mode === 'edit' && $karir_data['type'] == 'Contract') || (isset($_POST['type']) && $_POST['type'] == 'Contract') ? 'selected' : '' ?>>Contract</option>
                                <option value="Internship" <?= ($mode === 'edit' && $karir_data['type'] == 'Internship') || (isset($_POST['type']) && $_POST['type'] == 'Internship') ? 'selected' : '' ?>>Internship</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="location" class="form-label required">
                                <i class="fas fa-map-marker-alt"></i> Lokasi
                            </label>
                            <input type="text" id="location" name="location" class="form-control" required 
                                   value="<?= $mode === 'edit' ? htmlspecialchars($karir_data['location']) : (isset($_POST['location']) ? htmlspecialchars($_POST['location']) : 'Klaten, Jawa Tengah') ?>"
                                   placeholder="Masukkan lokasi kerja">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deadline" class="form-label required">
                            <i class="fas fa-calendar-alt"></i> Deadline
                        </label>
                        <input type="date" id="deadline" name="deadline" class="form-control" required 
                               value="<?= $mode === 'edit' ? htmlspecialchars($karir_data['deadline']) : (isset($_POST['deadline']) ? htmlspecialchars($_POST['deadline']) : '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label required">
                            <i class="fas fa-align-left"></i> Deskripsi Pekerjaan
                        </label>
                        <textarea id="description" name="description" class="form-control" rows="3" required 
                                  placeholder="Deskripsikan tanggung jawab dan pekerjaan"><?= $mode === 'edit' ? htmlspecialchars($karir_data['description']) : (isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements" class="form-label required">
                            <i class="fas fa-list-check"></i> Persyaratan
                        </label>
                        <textarea id="requirements" name="requirements" class="form-control" rows="3" required 
                                  placeholder="Pisahkan setiap persyaratan dengan baris baru"><?= $mode === 'edit' ? htmlspecialchars($karir_data['requirements']) : (isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : '') ?></textarea>
                        <div class="form-text">Gunakan baris baru untuk setiap poin persyaratan</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="is_active" class="form-label">
                            <i class="fas fa-toggle-on"></i> Status
                        </label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" 
                                   <?= (!isset($karir_data) || $karir_data['is_active']) ? 'checked' : '' ?>>
                            <label for="is_active">Aktifkan Lowongan</label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn <?= $mode === 'edit' ? 'btn-success' : 'btn-primary' ?>">
                            <i class="fas <?= $mode === 'edit' ? 'fa-save' : 'fa-plus' ?>"></i>
                            <?= $mode === 'edit' ? 'Update Lowongan' : 'Simpan Lowongan' ?>
                        </button>
                        
                        <a href="admin_karir.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- DAFTAR LOWONGAN (Tabel) -->
            <div class="table-container" <?php if (isset($_GET['add']) || $mode === 'edit'): ?>style="grid-column: span 2;"<?php endif; ?>>
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list"></i> Daftar Lowongan
                    </div>
                    
                    <?php if ($total_karir > 0): ?>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="selectAll" class="check-all">
                            <label for="selectAll" style="font-weight: 600; color: var(--text-dark);">Pilih Semua</label>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- AKSI MASAL - Untuk hapus banyak data sekaligus -->
                <div class="bulk-actions" id="bulkActions">
                    <div class="bulk-info">
                        <span class="bulk-count" id="selectedCount">0 item terpilih</span>
                        <span style="color: var(--text-light); font-size: 14px;">Pilih aksi yang ingin dilakukan:</span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <form method="POST" action="" id="bulkDeleteForm" style="display: inline;">
                            <button type="submit" name="hapus_masal" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Hapus Terpilih
                            </button>
                            <input type="hidden" name="pilih_hapus[]" id="bulkIds">
                        </form>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </div>
                
                <?php if ($total_karir > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" id="checkAllHeader" class="check-all">
                                    </th>
                                    <th>No</th>
                                    <th>Posisi</th>
                                    <th>Departemen</th>
                                    <th>Lokasi & Tipe</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="karirTable">
                                <?php 
                                $no = 1;
                                mysqli_data_seek($result_karir, 0);
                                while($row = mysqli_fetch_assoc($result_karir)): 
                                    $is_expired = isExpired($row['deadline']);
                                    $tanggal = tgl_indo($row['deadline']);
                                ?>
                                    <tr data-id="<?= $row['id'] ?>" <?= $is_expired ? 'style="opacity: 0.7;"' : '' ?>>
                                        <td class="checkbox-cell">
                                            <input type="checkbox" 
                                                   class="item-checkbox" 
                                                   data-id="<?= $row['id'] ?>">
                                        </td>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['position']) ?></strong>
                                            <?php if (!empty($row['description'])): ?>
                                                <div style="font-size: 13px; color: var(--text-light); margin-top: 4px;">
                                                    <?= htmlspecialchars(substr($row['description'], 0, 60)) . (strlen($row['description']) > 60 ? '...' : '') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-review">
                                                <?= htmlspecialchars($row['department']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size: 14px;">
                                                <div style="margin-bottom: 5px;">
                                                    <i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>
                                                    <?= htmlspecialchars($row['location']) ?>
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-light);">
                                                    <i class="fas fa-clock"></i> <?= htmlspecialchars($row['type']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column;">
                                                <div style="font-weight: 500; color: <?= $is_expired ? 'var(--danger)' : 'var(--success)' ?>;">
                                                    <?= $tanggal ?>
                                                </div>
                                                <?php if($is_expired): ?>
                                                    <small style="color: var(--danger); font-size: 12px;">Kadaluarsa</small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="?toggle=<?= $row['id'] ?>" 
                                            class="status-badge <?= $row['is_active'] ? 'status-active' : 'status-inactive' ?>" 
                                            style="text-decoration: none; cursor: pointer;"
                                            onclick="return confirm('Ubah status lowongan ini?')">
                                                <?php if($row['is_active']): ?>
                                                    <i class="fas fa-check-circle"></i> Aktif
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle"></i> Nonaktif
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin_karir.php?edit=<?= $row['id'] ?>" 
                                                   class="action-btn edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="admin_karir.php?toggle=<?= $row['id'] ?>" 
                                                   class="action-btn status"
                                                   onclick="return confirm('Ubah status lowongan ini?')"
                                                   title="<?= $row['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                                <button type="button" 
                                                        class="action-btn delete"
                                                        onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['position'])) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <h3>Belum Ada Lowongan</h3>
                        <p>Mulai tambah lowongan pertama Anda.</p>
                        <?php if (!isset($_GET['add']) && $mode !== 'edit'): ?>
                            <a href="?add=true" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Lowongan
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- MODAL KONFIRMASI HAPUS -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Konfirmasi Hapus</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 70px; color: var(--danger); margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 id="modalMessage" style="margin-bottom: 15px; color: var(--text-dark);">
                    Apakah Anda yakin ingin menghapus lowongan ini?
                </h3>
                <p style="color: var(--text-light); margin-bottom: 30px;">
                    Tindakan ini tidak dapat dibatalkan.
                </p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button onclick="closeModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Ya, Hapus
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'template_end.php'; ?>

<script>
    // FUNGSI SELEKSI MASAL (Bulk Selection)
    const selectAll = document.getElementById('selectAll');
    const checkAllHeader = document.getElementById('checkAllHeader');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const bulkIdsInput = document.getElementById('bulkIds');
    const karirTable = document.getElementById('karirTable');
    
    // FUNGSI UNTUK UPDATE STATUS SELEKSI
    function updateSelection() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        const count = selected.length;
        
        // UPDATE JUMLAH ITEM TERPILIH
        selectedCount.textContent = count + ' item terpilih';
        
        // TAMPILKAN/SEMBUNYIKAN AKSI MASAL
        if (count > 0) {
            bulkActions.classList.add('active');
            
            // UPDATE WARNA BARIS TERPILIH
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (cb.checked) {
                    row.style.backgroundColor = 'var(--gray-50)';
                } else {
                    row.style.backgroundColor = '';
                }
            });
            
            // UPDATE ID YANG DIPILIH
            const ids = selected.map(cb => cb.getAttribute('data-id'));
            bulkIdsInput.value = JSON.stringify(ids);
        } else {
            bulkActions.classList.remove('active');
            
            // HAPUS WARNA BARIS
            checkboxes.forEach(cb => {
                cb.closest('tr').style.backgroundColor = '';
            });
        }
        
        // UPDATE STATUS CHECKBOX "PILIH SEMUA"
        const allChecked = count === checkboxes.length && checkboxes.length > 0;
        const someChecked = count > 0 && count < checkboxes.length;
        
        if (selectAll) selectAll.checked = allChecked;
        if (selectAll) selectAll.indeterminate = someChecked;
        if (checkAllHeader) checkAllHeader.checked = allChecked;
        if (checkAllHeader) checkAllHeader.indeterminate = someChecked;
    }
    
    // FUNGSI "PILIH SEMUA"
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateSelection();
        });
    }
    
    if (checkAllHeader) {
        checkAllHeader.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateSelection();
        });
    }
    
    // EVENT LISTENER UNTUK SETIAP CHECKBOX INDIVIDUAL
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
    
    // FUNGSI UNTUK MEMBATALKAN SELEKSI
    function clearSelection() {
        checkboxes.forEach(cb => {
            cb.checked = false;
        });
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        if (checkAllHeader) {
            checkAllHeader.checked = false;
            checkAllHeader.indeterminate = false;
        }
        updateSelection();
    }
    
    // FORM HAPUS MASAL
    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function(e) {
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            if (selected.length === 0) {
                e.preventDefault();
                alert('Pilih minimal satu lowongan untuk dihapus!');
                return;
            }
            
            if (!confirm(`Hapus ${selected.length} lowongan yang dipilih?`)) {
                e.preventDefault();
                return;
            }
            
            // KONVERSI JSON STRING MENJADI INPUT HIDDEN
            const ids = selected.map(cb => cb.getAttribute('data-id'));
            const existingInputs = this.querySelectorAll('input[name="pilih_hapus[]"]');
            existingInputs.forEach(input => input.remove());
            
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'pilih_hapus[]';
                input.value = id;
                this.appendChild(input);
            });
        });
    }
    
    // MODAL KONFIRMASI HAPUS
    let currentDeleteId = null;
    
    function confirmDelete(id, title) {
        currentDeleteId = id;
        const modal = document.getElementById('deleteModal');
        const message = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        message.textContent = `Apakah Anda yakin ingin menghapus lowongan "${title}"?`;
        confirmBtn.href = `admin_karir.php?hapus=${id}`;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        document.getElementById('deleteModal').classList.remove('active');
        document.body.style.overflow = '';
        currentDeleteId = null;
    }
    
    // INISIALISASI SAAT HALAMAN DIMUAT
    document.addEventListener('DOMContentLoaded', function() {
        updateSelection();
        
        // AUTO-HIDE PESAN NOTIFIKASI SETELAH 5 DETIK
        setTimeout(function() {
            const alerts = document.querySelectorAll('.message-alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            });
        }, 5000);
        
        // SCROLL KE FORM JIKA DALAM MODE EDIT ATAU TAMBAH
        <?php if ($mode === 'edit' || isset($_GET['add'])): ?>
            document.querySelector('.form-container')?.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        <?php endif; ?>
        
        // SET DEADLINE DEFAULT 30 HARI DARI SEKARANG UNTUK ENTRI BARU
        <?php if ($mode === 'tambah' && !isset($_POST['deadline'])): ?>
            const today = new Date();
            const thirtyDaysLater = new Date(today.setDate(today.getDate() + 30));
            const formattedDate = thirtyDaysLater.toISOString().split('T')[0];
            const deadlineInput = document.getElementById('deadline');
            if (deadlineInput && !deadlineInput.value) {
                deadlineInput.value = formattedDate;
            }
        <?php endif; ?>
    });
    
    // CEGAH FORM RESUBMISSION PADA REFRESH HALAMAN
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // TUTUP MODAL DENGAN TOMBOL ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // TUTUP MODAL DENGAN KLIK DI LUAR MODAL
    document.getElementById('deleteModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>