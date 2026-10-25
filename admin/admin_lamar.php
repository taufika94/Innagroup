<?php
session_start();
include 'config.php';
include 'header.php';

// Set page title
$page_title = "Kelola Pelamar - PT INTIM";

// Variabel untuk pesan
$pesan = '';
$pesan_error = '';
$pelamar_id = 0;
$mode = 'tambah';
$judul_form = 'Tambah Pelamar Baru';

// PROTEKSI: Jika belum login, tendang ke halaman login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// AMBIL NAMA DARI SESSION
$username = $_SESSION['username'];

// Cek mode edit
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $pelamar_id = intval($_GET['edit']);
    $mode = 'edit';
    $judul_form = 'Edit Data Pelamar';
    
    // Ambil data pelamar untuk diedit
    $query_select = "SELECT l.*, k.position as lowongan_position, k.department 
                     FROM lamaran l 
                     LEFT JOIN karir k ON l.karir_id = k.id 
                     WHERE l.id = $pelamar_id";
    $result_select = mysqli_query($conn, $query_select);
    $pelamar_data = mysqli_fetch_assoc($result_select);
    
    if (!$pelamar_data) {
        $pesan_error = "Pelamar tidak ditemukan!";
        $mode = 'tambah';
        $judul_form = 'Tambah Pelamar Baru';
    }
}

// Proses Hapus Single
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $hapus_id = intval($_GET['hapus']);
    
    // Hapus dari database
    $delete_query = "DELETE FROM lamaran WHERE id = $hapus_id";
    if (mysqli_query($conn, $delete_query)) {
        $pesan = "Data pelamar berhasil dihapus!";
    } else {
        $pesan_error = "Gagal menghapus data pelamar: " . mysqli_error($conn);
    }
    
    
}

// Proses Hapus Massal (Bulk Delete)
if (isset($_POST['hapus_masal'])) {
    if (!empty($_POST['pilih_hapus'])) {
        $ids = $_POST['pilih_hapus'];
        $jumlah_hapus = count($ids);
        
        // Sanitasi ID
        $ids_clean = array_map(function($id) {
            return intval($id);
        }, $ids);
        
        $ids_clean = array_filter($ids_clean, function($id) {
            return $id > 0;
        });
        
        if (!empty($ids_clean)) {
            $string_ids = implode(',', $ids_clean);
            
            // Hapus dari database
            $query_hapus = "DELETE FROM lamaran WHERE id IN ($string_ids)";
            if (mysqli_query($conn, $query_hapus)) {
                $pesan = "Berhasil menghapus $jumlah_hapus data pelamar!";
            } else {
                $pesan_error = "Gagal menghapus data: " . mysqli_error($conn);
            }
        }
    } else {
        $pesan_error = "Pilih minimal satu pelamar untuk dihapus!";
    }
}



// Proses Tambah/Edit Pelamar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $nama_pelamar = mysqli_real_escape_string($conn, $_POST['nama_pelamar']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);
    $pendidikan = mysqli_real_escape_string($conn, $_POST['pendidikan']);
    $pengalaman = mysqli_real_escape_string($conn, $_POST['pengalaman']);
    $motivasi = mysqli_real_escape_string($conn, $_POST['motivasi']);
    $karir_id = intval($_POST['karir_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validasi
    $errors = [];
    if (empty($nama_pelamar)) $errors[] = "Nama pelamar harus diisi";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid";
    if (empty($telepon)) $errors[] = "Telepon harus diisi";
    if (empty($pendidikan)) $errors[] = "Pendidikan harus diisi";
    if (empty($pengalaman)) $errors[] = "Pengalaman harus diisi";
    if (empty($motivasi)) $errors[] = "Motivasi harus diisi";
    if ($karir_id <= 0) $errors[] = "Lowongan harus dipilih";
    if (empty($status)) $errors[] = "Status harus dipilih";
    
    if (empty($errors)) {
        if ($mode === 'tambah') {
            // Insert ke database
            $query = "INSERT INTO lamaran (karir_id, nama_pelamar, email, telepon, pendidikan, pengalaman, motivasi, status, created_at, updated_at) 
                     VALUES ($karir_id, '$nama_pelamar', '$email', '$telepon', '$pendidikan', '$pengalaman', '$motivasi', '$status', NOW(), NOW())";
            
            if (mysqli_query($conn, $query)) {
                $pesan = "Data pelamar berhasil ditambahkan!";
                $_POST = []; // Clear form
            } else {
                $pesan_error = "Gagal menyimpan ke database: " . mysqli_error($conn);
            }
        }
        // Handle edit mode
        elseif ($mode === 'edit' && $pelamar_id > 0) {
            // Update database
            $query = "UPDATE lamaran SET 
                     karir_id = $karir_id,
                     nama_pelamar = '$nama_pelamar',
                     email = '$email',
                     telepon = '$telepon',
                     pendidikan = '$pendidikan',
                     pengalaman = '$pengalaman',
                     motivasi = '$motivasi',
                     status = '$status',
                     updated_at = NOW()
                     WHERE id = $pelamar_id";
            
            if (mysqli_query($conn, $query)) {
                $pesan = "Data pelamar berhasil diperbarui!";
                // Refresh data
                $result_select = mysqli_query($conn, "SELECT l.*, k.position as lowongan_position, k.department 
                                                     FROM lamaran l 
                                                     LEFT JOIN karir k ON l.karir_id = k.id 
                                                     WHERE l.id = $pelamar_id");
                $pelamar_data = mysqli_fetch_assoc($result_select);
            } else {
                $pesan_error = "Gagal memperbarui database: " . mysqli_error($conn);
            }
        }
    } else {
        $pesan_error = implode("<br>", $errors);
    }
}

// Filter parameters
$filter_status = isset($_GET['status_filter']) ? mysqli_real_escape_string($conn, $_GET['status_filter']) : '';
$filter_karir = isset($_GET['karir_filter']) ? intval($_GET['karir_filter']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Query untuk data pelamar
$query_pelamar = "SELECT l.*, k.position as lowongan_position, k.department 
                  FROM lamaran l 
                  LEFT JOIN karir k ON l.karir_id = k.id 
                  WHERE 1=1";

if (!empty($search)) {
    $query_pelamar .= " AND (l.nama_pelamar LIKE '%$search%' 
                  OR l.email LIKE '%$search%' 
                  OR l.telepon LIKE '%$search%' 
                  OR k.position LIKE '%$search%')";
}

if (!empty($filter_status)) {
    $query_pelamar .= " AND l.status = '$filter_status'";
}

if ($filter_karir > 0) {
    $query_pelamar .= " AND l.karir_id = $filter_karir";
}

// Sorting
if ($sort_order == 'newest') {
    $query_pelamar .= " ORDER BY l.created_at DESC";
} elseif ($sort_order == 'oldest') {
    $query_pelamar .= " ORDER BY l.created_at ASC";
} elseif ($sort_order == 'name') {
    $query_pelamar .= " ORDER BY l.nama_pelamar ASC";
}

$result_pelamar = mysqli_query($conn, $query_pelamar);

// Query untuk lowongan
$query_lowongan = "SELECT id, position, department FROM karir WHERE is_active = 1 ORDER BY position ASC";
$result_lowongan = mysqli_query($conn, $query_lowongan);

// Ambil pesan dari URL
if (isset($_GET['pesan']) && !empty($_GET['pesan'])) {
    $pesan = urldecode($_GET['pesan']);
}
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $pesan_error = urldecode($_GET['error']);
}

// Hitung statistik
$total_pelamar = mysqli_num_rows($result_pelamar);
$pelamar_pending = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM lamaran WHERE status = 'pending'"));
$pelamar_review = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM lamaran WHERE status = 'review'"));
$pelamar_interview = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM lamaran WHERE status = 'interview'"));
$pelamar_diterima = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM lamaran WHERE status = 'accepted'"));
$pelamar_ditolak = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM lamaran WHERE status = 'rejected'"));
$total_lowongan = mysqli_num_rows($result_lowongan);

// Fungsi Format Tanggal
function format_tanggal($date) {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') return '-';
    
    $timestamp = strtotime($date);
    if ($timestamp === false) return '-';
    
    $bulan = array(
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar',
        '04' => 'Apr', '05' => 'Mei', '06' => 'Jun',
        '07' => 'Jul', '08' => 'Agu', '09' => 'Sep',
        '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    );
    
    $tanggal = date('d', $timestamp);
    $bulanNum = date('m', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $tanggal . ' ' . $bulan[$bulanNum] . ' ' . $tahun;
}

// Fungsi Status Label
function get_status_label($status) {
    switch ($status) {
        case 'pending': return 'Menunggu';
        case 'review': return 'Dalam Review';
        case 'interview': return 'Interview';
        case 'accepted': return 'Diterima';
        case 'rejected': return 'Ditolak';
        default: return ucfirst($status);
    }
}

// Fungsi Status Color
function get_status_color($status) {
    switch ($status) {
        case 'pending': return 'status-pending';
        case 'review': return 'status-review';
        case 'interview': return 'status-interview';
        case 'accepted': return 'status-active';
        case 'rejected': return 'status-inactive';
        default: return 'status-pending';
    }
}
?>

<?php include 'template_start.php'; ?>

<main class="main-content">
    <header class="top-header">
        <div>
            <h1 class="page-title">Kelola Data Pelamar</h1>
            <p style="color: var(--text-light); font-size: 14px;">Kelola dan monitor data pelamar kerja di PT INTIM</p>
        </div>
        
        <div class="header-actions">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="content-area">
        <!-- Flash Messages -->
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

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_pelamar ?></h3>
                    <p>Total Pelamar</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $pelamar_pending ?></h3>
                    <p>Menunggu</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon review">
                    <i class="fas fa-search"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $pelamar_review ?></h3>
                    <p>Dalam Review</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon interview">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $pelamar_interview ?></h3>
                    <p>Interview</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $pelamar_diterima ?></h3>
                    <p>Diterima</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon expired">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $pelamar_ditolak ?></h3>
                    <p>Ditolak</p>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar" style="margin-bottom: 20px;">
            <div class="search-box">
                <form action="" method="GET">
                    <input type="text" name="search" class="search-input" placeholder="Cari nama, email, atau telepon..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="btn-group">
                <a href="?add=true" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Pelamar
                </a>
                
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section" style="margin-bottom: 20px;">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status_filter" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Menunggu</option>
                        <option value="review" <?= $filter_status == 'review' ? 'selected' : '' ?>>Dalam Review</option>
                        <option value="interview" <?= $filter_status == 'interview' ? 'selected' : '' ?>>Interview</option>
                        <option value="accepted" <?= $filter_status == 'accepted' ? 'selected' : '' ?>>Diterima</option>
                        <option value="rejected" <?= $filter_status == 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Lowongan</label>
                    <select name="karir_filter" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Lowongan</option>
                        <?php mysqli_data_seek($result_lowongan, 0); while($lowongan = mysqli_fetch_assoc($result_lowongan)): ?>
                            <option value="<?= $lowongan['id'] ?>" <?= $filter_karir == $lowongan['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lowongan['position']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Urutkan</label>
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="newest" <?= $sort_order == 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="oldest" <?= $sort_order == 'oldest' ? 'selected' : '' ?>>Terlama</option>
                        <option value="name" <?= $sort_order == 'name' ? 'selected' : '' ?>>Nama A-Z</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <div class="filter-buttons">
                        <span style="font-size: 14px; color: var(--text-light);">
                            Total: <b><?= $total_pelamar ?></b>
                        </span>
                        <?php if ($search != '' || $filter_status != '' || $filter_karir != ''): ?>
                            <a href="admin_lamar.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Hapus Filter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Main Content Grid -->
        <div style="display: grid; grid-template-columns: <?= (isset($_GET['add']) || $mode === 'edit') ? '1fr 1fr' : '1fr' ?>; gap: 30px; margin-bottom: 40px;">
            <!-- Form Tambah/Edit -->
            <?php if (isset($_GET['add']) || $mode === 'edit'): ?>
            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas <?= $mode === 'edit' ? 'fa-edit' : 'fa-user-plus' ?>"></i>
                    <?= $mode === 'edit' ? 'Edit Data Pelamar' : 'Tambah Pelamar Baru' ?>
                </h2>
                
                <form action="" method="POST">
                    <input type="hidden" name="mode" value="<?= $mode ?>">
                    
                    <div class="form-group">
                        <label for="nama_pelamar" class="form-label required">
                            <i class="fas fa-user"></i> Nama Lengkap
                        </label>
                        <input type="text" id="nama_pelamar" name="nama_pelamar" class="form-control" required 
                               value="<?= $mode === 'edit' ? htmlspecialchars($pelamar_data['nama_pelamar']) : (isset($_POST['nama_pelamar']) ? htmlspecialchars($_POST['nama_pelamar']) : '') ?>"
                               placeholder="Masukkan nama lengkap">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email" class="form-label required">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" id="email" name="email" class="form-control" required 
                                   value="<?= $mode === 'edit' ? htmlspecialchars($pelamar_data['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '') ?>"
                                   placeholder="email@contoh.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="telepon" class="form-label required">
                                <i class="fas fa-phone"></i> Telepon
                            </label>
                            <input type="text" id="telepon" name="telepon" class="form-control" required 
                                   value="<?= $mode === 'edit' ? htmlspecialchars($pelamar_data['telepon']) : (isset($_POST['telepon']) ? htmlspecialchars($_POST['telepon']) : '') ?>"
                                   placeholder="081234567890">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pendidikan" class="form-label required">
                                <i class="fas fa-graduation-cap"></i> Pendidikan
                            </label>
                            <select id="pendidikan" name="pendidikan" class="form-control" required>
                                <option value="">-- Pilih Pendidikan --</option>
                                <option value="SMA/SMK" <?= ($mode === 'edit' && $pelamar_data['pendidikan'] == 'SMA/SMK') || (isset($_POST['pendidikan']) && $_POST['pendidikan'] == 'SMA/SMK') ? 'selected' : '' ?>>SMA/SMK</option>
                                <option value="D1/D2/D3" <?= ($mode === 'edit' && $pelamar_data['pendidikan'] == 'D1/D2/D3') || (isset($_POST['pendidikan']) && $_POST['pendidikan'] == 'D1/D2/D3') ? 'selected' : '' ?>>D1/D2/D3</option>
                                <option value="D4/S1" <?= ($mode === 'edit' && $pelamar_data['pendidikan'] == 'D4/S1') || (isset($_POST['pendidikan']) && $_POST['pendidikan'] == 'D4/S1') ? 'selected' : '' ?>>D4/S1</option>
                                <option value="S2" <?= ($mode === 'edit' && $pelamar_data['pendidikan'] == 'S2') || (isset($_POST['pendidikan']) && $_POST['pendidikan'] == 'S2') ? 'selected' : '' ?>>S2</option>
                                <option value="S3" <?= ($mode === 'edit' && $pelamar_data['pendidikan'] == 'S3') || (isset($_POST['pendidikan']) && $_POST['pendidikan'] == 'S3') ? 'selected' : '' ?>>S3</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="karir_id" class="form-label required">
                                <i class="fas fa-briefcase"></i> Lowongan
                            </label>
                            <select id="karir_id" name="karir_id" class="form-control" required>
                                <option value="">-- Pilih Lowongan --</option>
                                <?php 
                                mysqli_data_seek($result_lowongan, 0);
                                while($lowongan = mysqli_fetch_assoc($result_lowongan)): 
                                    $selected = ($mode === 'edit' && $pelamar_data['karir_id'] == $lowongan['id']) || (isset($_POST['karir_id']) && $_POST['karir_id'] == $lowongan['id']);
                                ?>
                                    <option value="<?= $lowongan['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lowongan['position']) ?> (<?= htmlspecialchars($lowongan['department']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pengalaman" class="form-label required">
                            <i class="fas fa-briefcase"></i> Pengalaman Kerja
                        </label>
                        <textarea id="pengalaman" name="pengalaman" class="form-control" rows="3" required 
                                  placeholder="Deskripsikan pengalaman kerja"><?= $mode === 'edit' ? htmlspecialchars($pelamar_data['pengalaman']) : (isset($_POST['pengalaman']) ? htmlspecialchars($_POST['pengalaman']) : '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="motivasi" class="form-label required">
                            <i class="fas fa-bullseye"></i> Motivasi Bergabung
                        </label>
                        <textarea id="motivasi" name="motivasi" class="form-control" rows="3" required 
                                  placeholder="Jelaskan motivasi bergabung dengan perusahaan"><?= $mode === 'edit' ? htmlspecialchars($pelamar_data['motivasi']) : (isset($_POST['motivasi']) ? htmlspecialchars($_POST['motivasi']) : '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="form-label required">
                            <i class="fas fa-tag"></i> Status Lamaran
                        </label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="pending" <?= ($mode === 'edit' && $pelamar_data['status'] == 'pending') || (isset($_POST['status']) && $_POST['status'] == 'pending') ? 'selected' : '' ?>>Menunggu</option>
                            <option value="review" <?= ($mode === 'edit' && $pelamar_data['status'] == 'review') || (isset($_POST['status']) && $_POST['status'] == 'review') ? 'selected' : '' ?>>Dalam Review</option>
                            <option value="interview" <?= ($mode === 'edit' && $pelamar_data['status'] == 'interview') || (isset($_POST['status']) && $_POST['status'] == 'interview') ? 'selected' : '' ?>>Interview</option>
                            <option value="accepted" <?= ($mode === 'edit' && $pelamar_data['status'] == 'accepted') || (isset($_POST['status']) && $_POST['status'] == 'accepted') ? 'selected' : '' ?>>Diterima</option>
                            <option value="rejected" <?= ($mode === 'edit' && $pelamar_data['status'] == 'rejected') || (isset($_POST['status']) && $_POST['status'] == 'rejected') ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn <?= $mode === 'edit' ? 'btn-success' : 'btn-primary' ?>">
                            <i class="fas <?= $mode === 'edit' ? 'fa-save' : 'fa-plus' ?>"></i>
                            <?= $mode === 'edit' ? 'Update Data' : 'Simpan Data' ?>
                        </button>
                        
                        <a href="admin_lamar.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Daftar Pelamar -->
            <div class="table-container" <?php if (isset($_GET['add']) || $mode === 'edit'): ?>style="grid-column: span 1;"<?php endif; ?>>
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list"></i> Daftar Pelamar
                    </div>
                    
                    <?php if ($total_pelamar > 0): ?>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="selectAll" class="check-all">
                            <label for="selectAll" style="font-weight: 600; color: var(--text-dark);">Pilih Semua</label>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Bulk Actions -->
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
                
                <?php if ($total_pelamar > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" id="checkAllHeader" class="check-all">
                                    </th>
                                    <th>No</th>
                                    <th>Pelamar</th>
                                    <th>Lowongan</th>
                                    <th>Pendidikan & Pengalaman</th>
                                    <th>Status</th>
                                    <th>Tanggal Lamar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="pelamarTable">
                                <?php 
                                $no = 1;
                                mysqli_data_seek($result_pelamar, 0);
                                while($row = mysqli_fetch_assoc($result_pelamar)): 
                                    $tanggal = format_tanggal($row['created_at']);
                                ?>
                                    <tr data-id="<?= $row['id'] ?>">
                                        <td class="checkbox-cell">
                                            <input type="checkbox" 
                                                   class="item-checkbox" 
                                                   data-id="<?= $row['id'] ?>">
                                        </td>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($row['nama_pelamar']) ?></strong>
                                                <div style="font-size: 13px; color: var(--text-light); margin-top: 4px;">
                                                    <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($row['email']) ?></div>
                                                    <div><i class="fas fa-phone"></i> <?= htmlspecialchars($row['telepon']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div style="font-weight: 600; color: var(--primary); margin-bottom: 4px;">
                                                    <?= htmlspecialchars($row['lowongan_position'] ?? 'Tidak diketahui') ?>
                                                </div>
                                                <div style="font-size: 13px; color: var(--text-light);">
                                                    <?= htmlspecialchars($row['department'] ?? '-') ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size: 13px;">
                                                <strong>Pendidikan:</strong> <?= htmlspecialchars($row['pendidikan']) ?><br>
                                                <strong>Pengalaman:</strong> <?= htmlspecialchars(substr($row['pengalaman'], 0, 60)) . (strlen($row['pengalaman']) > 60 ? '...' : '') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= get_status_color($row['status']) ?>">
                                                <?= get_status_label($row['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $tanggal ?><br>
                                            <small style="color: var(--text-light);">
                                                <?= date('H:i', strtotime($row['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin_lamar.php?edit=<?= $row['id'] ?>" 
                                                   class="action-btn edit"
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                
                                                
                                                <button type="button" 
                                                        class="action-btn delete"
                                                        onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_pelamar'])) ?>')"
                                                        title="Hapus">
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
                        <i class="fas fa-users"></i>
                        <h3>Belum Ada Data Pelamar</h3>
                        <p>Tidak ada data lamaran yang ditemukan.</p>
                        <?php if (!isset($_GET['add']) && $mode !== 'edit'): ?>
                            <a href="?add=true" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Pelamar
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tips Section -->
        <div style="background: var(--white); padding: 24px; border-radius: var(--radius-xl); box-shadow: var(--shadow);">
            <h3 style="margin-bottom: 16px; color: var(--primary);">
                <i class="fas fa-lightbulb"></i> Tips Manajemen Pelamar
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div style="background: rgba(52, 152, 219, 0.1); padding: 16px; border-radius: var(--radius);">
                    <h4 style="color: var(--accent); margin-bottom: 8px;">
                        <i class="fas fa-bell"></i> Cepat Tanggap
                    </h4>
                    <p style="font-size: 14px; color: var(--text-light);">
                        Berikan tanggapan pada pelamar dalam waktu 3-7 hari kerja untuk meningkatkan kepuasan kandidat.
                    </p>
                </div>
                <div style="background: rgba(46, 204, 113, 0.1); padding: 16px; border-radius: var(--radius);">
                    <h4 style="color: var(--success); margin-bottom: 8px;">
                        <i class="fas fa-comments"></i> Komunikasi
                    </h4>
                    <p style="font-size: 14px; color: var(--text-light);">
                        Jaga komunikasi yang baik dengan pelamar, berikan feedback yang konstruktif.
                    </p>
                </div>
                <div style="background: rgba(243, 156, 18, 0.1); padding: 16px; border-radius: var(--radius);">
                    <h4 style="color: var(--warning); margin-bottom: 8px;">
                        <i class="fas fa-database"></i> Data Backup
                    </h4>
                    <p style="font-size: 14px; color: var(--text-light);">
                        Backup data pelamar secara berkala untuk keperluan audit dan dokumentasi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal -->
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
                    Apakah Anda yakin ingin menghapus data pelamar ini?
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

<style>
/* Dropdown Styling */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    display: none;
    position: absolute;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    min-width: 180px;
    z-index: 1000;
    top: 100%;
    left: 0;
    margin-top: 5px;
}

.dropdown-item {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: var(--text-dark);
    font-size: 14px;
    border-bottom: 1px solid var(--gray-100);
    transition: background 0.2s;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: var(--gray-50);
}

.dropdown-item.active {
    background: var(--gray-100);
    color: var(--primary);
}

.dropdown:hover .dropdown-menu {
    display: block;
}

/* Status Badge Colors */
.status-pending {
    background: rgba(241, 196, 15, 0.1);
    color: #f39c12;
    border: 1px solid rgba(241, 196, 15, 0.3);
}

.status-review {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border: 1px solid rgba(52, 152, 219, 0.3);
}

.status-interview {
    background: rgba(155, 89, 182, 0.1);
    color: #9b59b6;
    border: 1px solid rgba(155, 89, 182, 0.3);
}
</style>

<script>
    // Bulk Selection Functionality
    const selectAll = document.getElementById('selectAll');
    const checkAllHeader = document.getElementById('checkAllHeader');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const bulkIdsInput = document.getElementById('bulkIds');
    const pelamarTable = document.getElementById('pelamarTable');
    
    // Update selection state
    function updateSelection() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        const count = selected.length;
        
        // Update count display
        selectedCount.textContent = count + ' item terpilih';
        
        // Show/hide bulk actions
        if (count > 0) {
            bulkActions.classList.add('active');
            
            // Update selected rows style
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (cb.checked) {
                    row.style.backgroundColor = 'var(--gray-50)';
                } else {
                    row.style.backgroundColor = '';
                }
            });
            
            // Update bulk IDs
            const ids = selected.map(cb => cb.getAttribute('data-id'));
            bulkIdsInput.value = JSON.stringify(ids);
        } else {
            bulkActions.classList.remove('active');
            
            // Remove selected style
            checkboxes.forEach(cb => {
                cb.closest('tr').style.backgroundColor = '';
            });
        }
        
        // Update select all state
        const allChecked = count === checkboxes.length && checkboxes.length > 0;
        const someChecked = count > 0 && count < checkboxes.length;
        
        if (selectAll) selectAll.checked = allChecked;
        if (selectAll) selectAll.indeterminate = someChecked;
        if (checkAllHeader) checkAllHeader.checked = allChecked;
        if (checkAllHeader) checkAllHeader.indeterminate = someChecked;
    }
    
    // Select All functionality
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
    
    // Individual checkbox functionality
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
    
    // Clear selection
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
    
    // Bulk delete form submission
    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function(e) {
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            if (selected.length === 0) {
                e.preventDefault();
                alert('Pilih minimal satu pelamar untuk dihapus!');
                return;
            }
            
            if (!confirm(`Hapus ${selected.length} data pelamar yang dipilih?`)) {
                e.preventDefault();
                return;
            }
            
            // Convert JSON string to hidden inputs
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
    
    // Delete Confirmation Modal
    let currentDeleteId = null;
    
    function confirmDelete(id, nama) {
        currentDeleteId = id;
        const modal = document.getElementById('deleteModal');
        const message = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        message.textContent = `Apakah Anda yakin ingin menghapus data pelamar "${nama}"?`;
        confirmBtn.href = `admin_lamar.php?hapus=${id}`;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        document.getElementById('deleteModal').classList.remove('active');
        document.body.style.overflow = '';
        currentDeleteId = null;
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateSelection();
        
        // Auto-hide messages after 5 seconds
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
        
        // Scroll to form if in edit mode or add mode
        <?php if ($mode === 'edit' || isset($_GET['add'])): ?>
            document.querySelector('.form-container')?.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        <?php endif; ?>
        
        // Initialize dropdowns
        const dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('mouseenter', function() {
                this.querySelector('.dropdown-menu').style.display = 'block';
            });
            
            dropdown.addEventListener('mouseleave', function() {
                this.querySelector('.dropdown-menu').style.display = 'none';
            });
        });
    });
    
    // Prevent form resubmission on refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // Close modal on outside click
    document.getElementById('deleteModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>