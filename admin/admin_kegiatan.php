<?php
// MEMULAI SESSION UNTUK MENYIMPAN DAN MENGAKSES DATA SESI PENGGUNA
session_start();
// MENGAKTIFKAN LAPORAN ERROR UNTUK DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// INCLUDE FILE KONFIGURASI DATABASE
// File config.php berisi pengaturan koneksi database
include 'config.php';
// INCLUDE FILE HEADER UNTUK NAVIGASI DAN TAMPILAN ATAS
include 'header.php';

// SET JUDUL HALAMAN UNTUK SEO DAN TAMPILAN BROWSER
$page_title = "Manajemen Kegiatan - PT INTIM";

// ======================================================
// PROTEKSI HALAMAN - HANYA ADMIN YANG BISA AKSES
// ======================================================
// Mengecek apakah pengguna sudah login dan berperan sebagai admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika bukan admin, redirect ke halaman login
    header("Location: login.php");
    exit; // Menghentikan eksekusi script
}

// ======================================================
// VARIABEL UTAMA DAN INISIALISASI
// ======================================================

// Ambil username dari session untuk ditampilkan
$username = $_SESSION['username'];

// Variabel mode menentukan halaman yang akan ditampilkan:
// - list: tampilkan daftar kegiatan
// - add: form tambah kegiatan baru
// - edit: form edit kegiatan
// - delete: proses hapus kegiatan
// - preview: preview detail kegiatan
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';

// ID kegiatan untuk operasi edit/delete/preview
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Variabel untuk menyimpan data kegiatan
$kegiatan = null;

// Array untuk menyimpan pesan error
$errors = [];

// Variabel untuk menyimpan pesan sukses
$success = '';

// ======================================================
// PROSES HAPUS MASSAL (BULK DELETE)
// ======================================================
// Mengecek apakah form hapus massal telah disubmit
if (isset($_POST['hapus_masal'])) {
    // Mengecek apakah ada item yang dipilih untuk dihapus
    if (!empty($_POST['pilih_hapus'])) {
        // Mengambil array ID yang dipilih
        $ids = $_POST['pilih_hapus'];
        $jumlah_hapus = count($ids);
        
        // SANITASI ID UNTUK KEAMANAN
        // Mengubah semua ID menjadi integer untuk mencegah SQL injection
        $ids_clean = array_map('intval', $ids);
        
        // Filter ID yang valid (hanya ID lebih dari 0)
        $ids_clean = array_filter($ids_clean, function($id) {
            return $id > 0;
        });
        
        // Jika ada ID yang valid setelah disaring
        if (!empty($ids_clean)) {
            // Menggabungkan ID menjadi string untuk query SQL
            $string_ids = implode(',', $ids_clean);
            
            // HAPUS FILE GAMBAR FISIK DARI SERVER
            // Query untuk mengambil path gambar dari database sebelum menghapus
            $res_gambar = mysqli_query($conn, "SELECT gambar FROM kegiatan WHERE id IN ($string_ids)");
            $files_deleted = 0;
            
            // Loop melalui setiap hasil query untuk menghapus file gambar
            while ($g = mysqli_fetch_assoc($res_gambar)) {
                // Mengecek apakah file gambar ada di server
                if (!empty($g['gambar']) && file_exists($g['gambar'])) {
                    // Hapus file fisik dari server
                    if (unlink($g['gambar'])) {
                        $files_deleted++; // Hitung file yang berhasil dihapus
                    }
                }
            }
            
            // HAPUS DATA DARI DATABASE
            $query_hapus = "DELETE FROM kegiatan WHERE id IN ($string_ids)";
            if (mysqli_query($conn, $query_hapus)) {
                $success = "Berhasil menghapus $jumlah_hapus kegiatan!";
            } else {
                $errors[] = "Gagal menghapus data: " . mysqli_error($conn);
            }
        }
    } else {
        $errors[] = "Pilih minimal satu kegiatan untuk dihapus!";
    }
}

// ======================================================
// HANDLE BERBAGAI MODE FORM
// ======================================================
switch($mode) {
    case 'add':
    case 'edit':
        // MODE ADD/EDIT: TAMPILKAN FORM TAMBAH/EDIT KEGIATAN
        
        // Jika mode edit dan ID valid, ambil data kegiatan dari database
        if ($mode == 'edit' && $id > 0) {
            $query = "SELECT * FROM kegiatan WHERE id = '$id'";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $kegiatan = mysqli_fetch_assoc($result);
            } else {
                $errors[] = "Kegiatan tidak ditemukan!";
                $mode = 'add'; // Kembalikan ke mode add jika data tidak ditemukan
            }
        }
        
        // HANDLE FORM SUBMISSION (SAAT FORM DISUBMIT)
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // AMBIL DAN AMANKAN DATA DARI FORM
            // mysqli_real_escape_string mencegah SQL injection
            $judul = mysqli_real_escape_string($conn, $_POST['judul'] ?? '');
            $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
            $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? '');
            $waktu = mysqli_real_escape_string($conn, $_POST['waktu'] ?? '');
            $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi'] ?? '');
            $kategori = mysqli_real_escape_string($conn, $_POST['kategori'] ?? '');
            
            // VALIDASI INPUT WAJIB
            if (empty($judul)) $errors[] = "Judul kegiatan harus diisi";
            if (empty($tanggal)) $errors[] = "Tanggal kegiatan harus diisi";
            if (empty($lokasi)) $errors[] = "Lokasi kegiatan harus diisi";
            
            // HANDLE FILE UPLOAD GAMBAR
            // Gunakan gambar lama jika ada (untuk mode edit)
            $gambar = $kegiatan['gambar'] ?? '';
            
            // Mengecek apakah ada file yang diupload
            if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                // Format file yang diperbolehkan
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['gambar']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // VALIDASI FORMAT FILE
                if (in_array($ext, $allowed)) {
                    // Buat nama file unik untuk menghindari konflik nama
                    $new_filename = uniqid() . '_' . time() . '.' . $ext;
                    $upload_path = '../uploads/kegiatan/' . $new_filename;
                    
                    // Buat direktori jika belum ada
                    if (!is_dir('../uploads/kegiatan')) {
                        mkdir('../uploads/kegiatan', 0777, true);
                    }
                    
                    // Pindahkan file dari temporary ke folder upload
                    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                        // Hapus gambar lama jika ada (untuk mode edit)
                        if (!empty($gambar) && file_exists($gambar)) {
                            unlink($gambar);
                        }
                        $gambar = $upload_path; // Simpan path baru
                    } else {
                        $errors[] = "Gagal mengupload gambar";
                    }
                } else {
                    $errors[] = "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP";
                }
            }
            
            // SIMPAN KE DATABASE JIKA TIDAK ADA ERROR
            if (empty($errors)) {
                if ($mode == 'add') {
                    // QUERY INSERT UNTUK TAMBAH DATA BARU
                    $query = "INSERT INTO kegiatan (judul, deskripsi, tanggal, waktu, lokasi, kategori, gambar, created_at) 
                              VALUES ('$judul', '$deskripsi', '$tanggal', '$waktu', '$lokasi', '$kategori', '$gambar', NOW())";
                    $action = "ditambahkan";
                } else {
                    // QUERY UPDATE UNTUK EDIT DATA YANG ADA
                    $query = "UPDATE kegiatan SET 
                              judul = '$judul',
                              deskripsi = '$deskripsi',
                              tanggal = '$tanggal',
                              waktu = '$waktu',
                              lokasi = '$lokasi',
                              kategori = '$kategori',
                              gambar = '$gambar',
                              updated_at = NOW()
                              WHERE id = '$id'";
                    $action = "diupdate";
                }
                
                // EKSEKUSI QUERY
                if (mysqli_query($conn, $query)) {
                    $success = "Kegiatan berhasil $action!";
                    if ($mode == 'add') {
                        $mode = 'list'; // Kembali ke mode list setelah berhasil tambah
                    } else {
                        // Refresh data setelah edit
                        $result = mysqli_query($conn, "SELECT * FROM kegiatan WHERE id = '$id'");
                        $kegiatan = mysqli_fetch_assoc($result);
                    }
                } else {
                    $errors[] = "Gagal menyimpan data: " . mysqli_error($conn);
                }
            }
        }
        break;
        
    case 'delete':
        // MODE DELETE: PROSES PENGHAPUSAN SINGLE ITEM
        if ($id > 0) {
            // Ambil path gambar terlebih dahulu sebelum menghapus
            $query = "SELECT gambar FROM kegiatan WHERE id = '$id'";
            $result = mysqli_query($conn, $query);
            if ($row = mysqli_fetch_assoc($result)) {
                $gambar = $row['gambar'];
                // Hapus file gambar fisik jika ada
                if (!empty($gambar) && file_exists($gambar)) {
                    unlink($gambar);
                }
            }
            
            // Hapus data dari database
            $deleteQuery = "DELETE FROM kegiatan WHERE id = '$id'";
            if (mysqli_query($conn, $deleteQuery)) {
                $success = "Kegiatan berhasil dihapus!";
            } else {
                $errors[] = "Gagal menghapus kegiatan: " . mysqli_error($conn);
            }
        }
        $mode = 'list'; // Kembali ke mode list setelah hapus
        break;
        
    case 'preview':
        // MODE PREVIEW: TAMPILKAN DETAIL KEGIATAN TANPA BISA EDIT
        if ($id > 0) {
            $query = "SELECT * FROM kegiatan WHERE id = '$id'";
            $result = mysqli_query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $kegiatan = mysqli_fetch_assoc($result);
            }
        }
        break;
        
    default:
        $mode = 'list'; // Default ke mode list
}

// ======================================================
// QUERY UNTUK MODE LIST
// ======================================================
if ($mode == 'list') {
    // Dapatkan tanggal hari ini untuk filter status
    $today = date('Y-m-d');
    
    // QUERY SEMUA KEGIATAN (diurutkan dari tanggal terbaru)
    $kegiatanQuery = "SELECT * FROM kegiatan ORDER BY tanggal DESC";
    $resultKegiatan = mysqli_query($conn, $kegiatanQuery);
    $totalKegiatan = $resultKegiatan ? mysqli_num_rows($resultKegiatan) : 0;
    
    // QUERY KEGIATAN YANG AKAN DATANG (tanggal > hari ini)
    $upcomingQuery = "SELECT * FROM kegiatan WHERE tanggal > '$today' ORDER BY tanggal ASC";
    $resultUpcoming = mysqli_query($conn, $upcomingQuery);
    $upcomingCount = $resultUpcoming ? mysqli_num_rows($resultUpcoming) : 0;
    
    // QUERY KEGIATAN YANG SEDANG BERLANGSUNG (tanggal = hari ini)
    $ongoingQuery = "SELECT * FROM kegiatan WHERE tanggal = '$today' ORDER BY waktu ASC";
    $resultOngoing = mysqli_query($conn, $ongoingQuery);
    $ongoingCount = $resultOngoing ? mysqli_num_rows($resultOngoing) : 0;
    
    // QUERY KEGIATAN YANG SUDAH SELESAI (tanggal < hari ini)
    $completedQuery = "SELECT * FROM kegiatan WHERE tanggal < '$today' ORDER BY tanggal DESC";
    $resultCompleted = mysqli_query($conn, $completedQuery);
    $completedCount = $resultCompleted ? mysqli_num_rows($resultCompleted) : 0;
    
    // QUERY UNTUK DATA KATEGORI (untuk filter)
    $kategori_list = mysqli_query($conn, "SELECT DISTINCT kategori FROM kegiatan WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
    $total_kategori = $kategori_list ? mysqli_num_rows($kategori_list) : 0;
}

// ======================================================
// FUNGSI BANTUAN (HELPER FUNCTIONS)
// ======================================================

/**
 * FUNGSI: Mendapatkan path gambar lokal
 * @param string $imageName - Nama file gambar
 * @param string $default - Gambar default jika tidak ditemukan
 * @return string - Path gambar yang valid
 */
function getLocalImage($imageName, $default = 'https://via.placeholder.com/400x300?text=No+Image') {
    // Jika nama gambar kosong, return gambar default
    if (empty($imageName)) {
        return $default;
    }
    
    // Jika file langsung ada di path yang diberikan
    if (file_exists($imageName)) {
        return $imageName;
    }
    
    // Coba beberapa lokasi yang mungkin (relatif path)
    $possibleLocations = [
        $imageName,
        '../' . $imageName,
        '../../' . $imageName,
        '../uploads/kegiatan/' . basename($imageName)
    ];
    
    // Loop melalui semua kemungkinan lokasi
    foreach ($possibleLocations as $location) {
        if (file_exists($location)) {
            return $location;
        }
    }
    
    // Return gambar default jika tidak ditemukan di semua lokasi
    return $default;
}

/**
 * FUNGSI: Format tanggal ke format Indonesia (DD Bulan YYYY)
 * @param string $tanggal - Tanggal dalam format YYYY-MM-DD
 * @return string - Tanggal dalam format Indonesia
 */
function formatTanggalIndo($tanggal) {
    // Validasi input tanggal
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return 'Tanggal belum ditentukan';
    }
    
    // Array nama bulan dalam bahasa Indonesia (format singkat)
    $bulanIndo = [
        'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    
    // Convert string tanggal ke timestamp
    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return 'Tanggal tidak valid';
    }
    
    // Ambil komponen tanggal
    $hari = date('d', $timestamp); // Hari (01-31)
    $bulan = $bulanIndo[(int)date('m', $timestamp) - 1]; // Bulan (0-11)
    $tahun = date('Y', $timestamp); // Tahun (4 digit)
    
    // Format: DD Bulan YYYY
    return $hari . ' ' . $bulan . ' ' . $tahun;
}

/**
 * FUNGSI: Format nama kategori menjadi lebih user-friendly
 * @param string $kategori - Kategori dari database
 * @return string - Nama kategori yang sudah diformat
 */
function formatKategori($kategori) {
    // Jika kategori kosong, return 'Umum'
    if (empty($kategori)) {
        return 'Umum';
    }
    
    // Mapping kategori ke nama yang lebih mudah dipahami
    $kategoriMapping = [
        'pameran' => 'Pameran & Expo',
        'pelatihan' => 'Pelatihan & Workshop',
        'sosial' => 'Kegiatan Sosial',
        'industri' => 'Event Industri',
        'internal' => 'Kegiatan Internal',
        'seminar' => 'Seminar',
        'workshop' => 'Workshop',
        'lomba' => 'Lomba/Pertandingan',
        'rapat' => 'Rapat/Meeting',
        'umum' => 'Kegiatan Umum'
    ];
    
    // Return mapped value atau format awal jika tidak ada di mapping
    return isset($kategoriMapping[$kategori]) ? 
           $kategoriMapping[$kategori] : 
           ucfirst($kategori); // Kapital huruf pertama
}

/**
 * FUNGSI: Mendapatkan badge status berdasarkan tanggal
 * @param string $tanggal - Tanggal kegiatan
 * @return string - HTML badge status
 */
function getStatusBadge($tanggal) {
    $today = date('Y-m-d'); // Tanggal hari ini
    
    // Tentukan status berdasarkan perbandingan tanggal
    if ($tanggal == $today) {
        // Kegiatan berlangsung hari ini
        return '<span class="status-badge status-pending"><i class="fas fa-running"></i> Berlangsung</span>';
    } elseif ($tanggal > $today) {
        // Kegiatan akan datang (masa depan)
        return '<span class="status-badge status-review"><i class="fas fa-clock"></i> Akan Datang</span>';
    }
    // Kegiatan sudah selesai (masa lalu)
    return '<span class="status-badge status-active"><i class="fas fa-check-circle"></i> Selesai</span>';
}

// INCLUDE TEMPLATE AWAL (HTML structure, CSS, JS)
include 'template_start.php';
?>

<!-- ====================================================== -->
<!-- BAGIAN HTML DARI HALAMAN -->
<!-- ====================================================== -->

<main class="main-content">
    <!-- HEADER HALAMAN -->
    <header class="top-header">
        <div>
            <h1 class="page-title">Manajemen Kegiatan</h1>
            <p style="color: var(--text-light); font-size: 14px;">Kelola kegiatan dan event PT INTIM</p>
        </div>
        
        <div class="header-actions">
            <!-- TOMBOL MENU MOBILE -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="content-area">
        <!-- ====================================================== -->
        <!-- FLASH MESSAGES (PESAN SUKSES/ERROR) -->
        <!-- ====================================================== -->
        <?php if ($success): ?>
            <div class="message-alert success" id="messageAlert">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <?php foreach($errors as $error): ?>
                <div class="message-alert error" id="messageAlert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ====================================================== -->
        <!-- MODE ADD ATAU EDIT -->
        <!-- ====================================================== -->
        <?php if ($mode == 'add' || $mode == 'edit'): ?>
        <div style="display: grid; grid-template-columns: 1fr; gap: 30px; margin-bottom: 40px;">
            <!-- FORM UPLOAD/EDIT -->
            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas <?= $mode == 'edit' ? 'fa-edit' : 'fa-calendar-plus' ?>"></i>
                    <?= $mode == 'edit' ? 'Edit Kegiatan' : 'Tambah Kegiatan Baru' ?>
                </h2>
                
                <!-- FORM UNTUK TAMBAH/EDIT KEGIATAN -->
                <!-- enctype="multipart/form-data" diperlukan untuk upload file -->
                <form action="" method="POST" enctype="multipart/form-data">
                    <!-- INPUT JUDUL KEGIATAN -->
                    <div class="form-group">
                        <label for="judul" class="form-label required">
                            <i class="fas fa-heading"></i> Judul Kegiatan
                        </label>
                        <input type="text" id="judul" name="judul" class="form-control" required 
                               value="<?= htmlspecialchars($kegiatan['judul'] ?? '') ?>"
                               placeholder="Masukkan judul kegiatan">
                    </div>
                    
                    <!-- TEXTAREA DESKRIPSI -->
                    <div class="form-group">
                        <label for="deskripsi" class="form-label">
                            <i class="fas fa-align-left"></i> Deskripsi
                        </label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="5" 
                                  placeholder="Masukkan deskripsi kegiatan"><?= htmlspecialchars($kegiatan['deskripsi'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- ROW TANGGAL DAN WAKTU -->
                    <div class="form-row">
                        <!-- INPUT TANGGAL -->
                        <div class="form-group">
                            <label for="tanggal" class="form-label required">
                                <i class="fas fa-calendar-day"></i> Tanggal
                            </label>
                            <input type="date" id="tanggal" name="tanggal" class="form-control" required 
                                   value="<?= htmlspecialchars($kegiatan['tanggal'] ?? '') ?>">
                        </div>
                        
                        <!-- INPUT WAKTU -->
                        <div class="form-group">
                            <label for="waktu" class="form-label">
                                <i class="fas fa-clock"></i> Waktu
                            </label>
                            <input type="time" id="waktu" name="waktu" class="form-control" 
                                   value="<?= htmlspecialchars($kegiatan['waktu'] ?? '09:00') ?>">
                            <div class="form-text">Contoh: 09:00 - 17:00 WIB</div>
                        </div>
                    </div>
                    
                    <!-- INPUT LOKASI -->
                    <div class="form-group">
                        <label for="lokasi" class="form-label required">
                            <i class="fas fa-map-marker-alt"></i> Lokasi
                        </label>
                        <input type="text" id="lokasi" name="lokasi" class="form-control" required 
                               value="<?= htmlspecialchars($kegiatan['lokasi'] ?? 'Klaten, Jawa Tengah') ?>" 
                               placeholder="Masukkan lokasi kegiatan">
                    </div>
                    
                    <!-- SELECT KATEGORI -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="kategori" class="form-label">
                                <i class="fas fa-tag"></i> Kategori
                            </label>
                            <select id="kategori" name="kategori" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                <!-- OPSI KATEGORI DENGAN SELECTED STATE -->
                                <option value="pameran" <?= ($kegiatan['kategori'] ?? '') == 'pameran' ? 'selected' : '' ?>>Pameran & Expo</option>
                                <option value="pelatihan" <?= ($kegiatan['kategori'] ?? '') == 'pelatihan' ? 'selected' : '' ?>>Pelatihan & Workshop</option>
                                <option value="sosial" <?= ($kegiatan['kategori'] ?? '') == 'sosial' ? 'selected' : '' ?>>Kegiatan Sosial</option>
                                <option value="industri" <?= ($kegiatan['kategori'] ?? '') == 'industri' ? 'selected' : '' ?>>Event Industri</option>
                                <option value="internal" <?= ($kegiatan['kategori'] ?? '') == 'internal' ? 'selected' : '' ?>>Kegiatan Internal</option>
                                <option value="seminar" <?= ($kegiatan['kategori'] ?? '') == 'seminar' ? 'selected' : '' ?>>Seminar</option>
                                <option value="workshop" <?= ($kegiatan['kategori'] ?? '') == 'workshop' ? 'selected' : '' ?>>Workshop</option>
                                <option value="lomba" <?= ($kegiatan['kategori'] ?? '') == 'lomba' ? 'selected' : '' ?>>Lomba/Pertandingan</option>
                                <option value="rapat" <?= ($kegiatan['kategori'] ?? '') == 'rapat' ? 'selected' : '' ?>>Rapat/Meeting</option>
                                <option value="umum" <?= empty($kegiatan['kategori'] ?? '') ? 'selected' : '' ?>>Kegiatan Umum</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- INPUT UPLOAD GAMBAR -->
                    <div class="form-group">
                        <label for="gambar" class="form-label">
                            <i class="fas fa-image"></i> Gambar Kegiatan
                        </label>
                        <div class="file-upload">
                            <!-- INPUT FILE DENGAN ACCEPT IMAGE ONLY -->
                            <input type="file" id="gambar" name="gambar" accept="image/*">
                            <label for="gambar" class="file-upload-label">
                                <div>
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: var(--accent); margin-bottom: 10px;"></i>
                                    <div style="font-weight: 500; color: var(--accent); margin-bottom: 5px;">Klik untuk upload gambar</div>
                                    <div style="font-size: 13px; color: var(--text-light);">Format: JPG, PNG, GIF, WebP (Max: 5MB)</div>
                                </div>
                            </label>
                        </div>
                        
                        <!-- TAMPILKAN GAMBAR SAAT INI JIKA SEDANG EDIT -->
                        <?php if (!empty($kegiatan['gambar'])): ?>
                        <div style="margin-top: 15px;">
                            <div style="font-size: 13px; color: var(--text-light); margin-bottom: 5px;">Gambar saat ini:</div>
                            <img src="<?= getLocalImage($kegiatan['gambar']) ?>" 
                                 alt="Current Image" 
                                 class="img-thumb"
                                 style="cursor: pointer; max-width: 200px;"
                                 onclick="previewImage('<?= getLocalImage($kegiatan['gambar']) ?>')">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- TOMBOL AKSI FORM -->
                    <div class="form-actions">
                        <button type="submit" class="btn <?= $mode == 'edit' ? 'btn-success' : 'btn-primary' ?>">
                            <i class="fas <?= $mode == 'edit' ? 'fa-save' : 'fa-calendar-plus' ?>"></i>
                            <?= $mode == 'edit' ? 'Update Kegiatan' : 'Simpan Kegiatan' ?>
                        </button>
                        
                        <!-- TOMBOL BATAL JIKA SEDANG EDIT -->
                        <?php if ($mode == 'edit'): ?>
                            <a href="admin_kegiatan.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- ====================================================== -->
        <!-- MODE PREVIEW -->
        <!-- ====================================================== -->
        <?php elseif ($mode == 'preview' && $kegiatan): ?>
        <div style="display: grid; grid-template-columns: 1fr; gap: 30px; margin-bottom: 40px;">
            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas fa-eye"></i>
                    Preview Kegiatan
                </h2>
                
                <!-- TAMPILKAN GAMBAR BESAR -->
                <?php if (!empty($kegiatan['gambar'])): ?>
                <div style="margin-bottom: 30px; text-align: center;">
                    <img src="<?= getLocalImage($kegiatan['gambar']) ?>" 
                         alt="<?= htmlspecialchars($kegiatan['judul']) ?>" 
                         style="max-width: 100%; max-height: 400px; border-radius: var(--radius); cursor: pointer;"
                         onclick="previewImage('<?= getLocalImage($kegiatan['gambar']) ?>')">
                </div>
                <?php endif; ?>
                
                <!-- DETAIL KEGIATAN DALAM READ-ONLY MODE -->
                <div class="form-group">
                    <label class="form-label">Judul Kegiatan</label>
                    <div style="background: var(--gray-50); padding: 15px; border-radius: var(--radius); font-size: 18px; font-weight: 600;">
                        <?= htmlspecialchars($kegiatan['judul']) ?>
                    </div>
                </div>
                
                <!-- TANGGAL DAN WAKTU -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal</label>
                        <div style="background: var(--gray-50); padding: 15px; border-radius: var(--radius);">
                            <?= formatTanggalIndo($kegiatan['tanggal']) ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Waktu</label>
                        <div style="background: var(--gray-50); padding: 15px; border-radius: var(--radius);">
                            <?= date('H:i', strtotime($kegiatan['waktu'])) ?> WIB
                        </div>
                    </div>
                </div>
                
                <!-- LOKASI -->
                <div class="form-group">
                    <label class="form-label">Lokasi</label>
                    <div style="background: var(--gray-50); padding: 15px; border-radius: var(--radius);">
                        <?= htmlspecialchars($kegiatan['lokasi']) ?>
                    </div>
                </div>
                
                <!-- KATEGORI -->
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <div style="background: var(--gray-50); padding: 15px; border-radius: var(--radius);">
                        <?= formatKategori($kegiatan['kategori']) ?>
                    </div>
                </div>
                
                <!-- DESKRIPSI -->
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <div style="background: var(--gray-50); padding: 15px; border-radius: var(--radius); white-space: pre-line;">
                        <?= nl2br(htmlspecialchars($kegiatan['deskripsi'] ?? '')) ?>
                    </div>
                </div>
                
                <!-- TOMBOL AKSI PREVIEW -->
                <div class="form-actions">
                    <a href="admin_kegiatan.php?mode=edit&id=<?= $kegiatan['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Kegiatan
                    </a>
                    <a href="admin_kegiatan.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
        
        <!-- ====================================================== -->
        <!-- MODE LIST (DEFAULT) -->
        <!-- ====================================================== -->
        <?php else: ?>
        <!-- STATS CARDS (KARTU STATISTIK) -->
        <div class="stats-grid">
            <!-- TOTAL KEGIATAN -->
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $totalKegiatan ?></h3>
                    <p>Total Kegiatan</p>
                </div>
            </div>
            
            <!-- KEGIATAN BERLANGSUNG -->
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-running"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $ongoingCount ?></h3>
                    <p>Berlangsung</p>
                </div>
            </div>
            
            <!-- KEGIATAN AKAN DATANG -->
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $upcomingCount ?></h3>
                    <p>Akan Datang</p>
                </div>
            </div>
            
            <!-- TOTAL KATEGORI -->
            <div class="stat-card">
                <div class="stat-icon review">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_kategori ?></h3>
                    <p>Kategori</p>
                </div>
            </div>
        </div>

        <!-- GRID UTAMA (FORM DAN TABEL) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
            <!-- FORM TAMBAH KEGIATAN BARU (HANYA DI MODE LIST) -->
            <?php if ($mode == 'list'): ?>
            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas fa-calendar-plus"></i>
                    Tambah Kegiatan Baru
                </h2>
                
                <!-- FORM TAMBAH KEGIATAN (DEFAULT VALUES) -->
                <form action="?mode=add" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="judul" class="form-label required">
                            <i class="fas fa-heading"></i> Judul Kegiatan
                        </label>
                        <input type="text" id="judul" name="judul" class="form-control" required 
                               placeholder="Masukkan judul kegiatan">
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi" class="form-label">
                            <i class="fas fa-align-left"></i> Deskripsi
                        </label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3" 
                                  placeholder="Masukkan deskripsi kegiatan"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tanggal" class="form-label required">
                                <i class="fas fa-calendar-day"></i> Tanggal
                            </label>
                            <input type="date" id="tanggal" name="tanggal" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="waktu" class="form-label">
                                <i class="fas fa-clock"></i> Waktu
                            </label>
                            <input type="time" id="waktu" name="waktu" class="form-control" value="09:00">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="lokasi" class="form-label required">
                            <i class="fas fa-map-marker-alt"></i> Lokasi
                        </label>
                        <input type="text" id="lokasi" name="lokasi" class="form-control" required 
                               value="Klaten, Jawa Tengah" 
                               placeholder="Masukkan lokasi kegiatan">
                    </div>
                    
                    <div class="form-group">
                        <label for="kategori" class="form-label">
                            <i class="fas fa-tag"></i> Kategori
                        </label>
                        <select id="kategori" name="kategori" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="pameran">Pameran & Expo</option>
                            <option value="pelatihan">Pelatihan & Workshop</option>
                            <option value="sosial">Kegiatan Sosial</option>
                            <option value="industri">Event Industri</option>
                            <option value="internal">Kegiatan Internal</option>
                            <option value="seminar">Seminar</option>
                            <option value="workshop">Workshop</option>
                            <option value="lomba">Lomba/Pertandingan</option>
                            <option value="rapat">Rapat/Meeting</option>
                            <option value="umum" selected>Kegiatan Umum</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="gambar" class="form-label">
                            <i class="fas fa-image"></i> Gambar Kegiatan
                        </label>
                        <div class="file-upload">
                            <input type="file" id="gambar" name="gambar" accept="image/*">
                            <label for="gambar" class="file-upload-label">
                                <div>
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: var(--accent); margin-bottom: 10px;"></i>
                                    <div style="font-weight: 500; color: var(--accent); margin-bottom: 5px;">Klik untuk upload gambar</div>
                                    <div style="font-size: 13px; color: var(--text-light);">Format: JPG, PNG, GIF, WebP (Max: 5MB)</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i>
                            Simpan Kegiatan
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- TABEL DAFTAR KEGIATAN -->
            <div class="table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list"></i> Daftar Kegiatan
                    </div>
                    
                    <!-- CHECKBOX PILIH SEMUA -->
                    <?php if ($totalKegiatan > 0): ?>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="selectAll" class="check-all">
                            <label for="selectAll" style="font-weight: 600; color: var(--text-dark);">Pilih Semua</label>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- BULK ACTIONS (TERSEMBUNYI AWALNYA) -->
                <div class="bulk-actions" id="bulkActions">
                    <div class="bulk-info">
                        <span class="bulk-count" id="selectedCount">0 item terpilih</span>
                        <span style="color: var(--text-light); font-size: 14px;">Pilih aksi yang ingin dilakukan:</span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <!-- FORM HAPUS MASSAL -->
                        <form method="POST" action="" id="bulkDeleteForm" style="display: inline;">
                            <button type="submit" name="hapus_masal" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Hapus Terpilih
                            </button>
                            <!-- INPUT HIDDEN UNTUK MENYIMPAN ID YANG DIPILIH -->
                            <input type="hidden" name="pilih_hapus[]" id="bulkIds">
                        </form>
                        <!-- TOMBOL BATAL PILIHAN -->
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </div>
                
                <!-- TABEL KEGIATAN -->
                <?php if ($totalKegiatan > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <!-- KOLOM CHECKBOX -->
                                    <th class="checkbox-cell">
                                        <input type="checkbox" id="checkAllHeader" class="check-all">
                                    </th>
                                    <th>No</th>
                                    <th>Gambar</th>
                                    <th>Judul Kegiatan</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="kegiatanTable">
                                <!-- LOOP MELALUI SETIAP KEGIATAN -->
                                <?php 
                                $no = 1;
                                mysqli_data_seek($resultKegiatan, 0); // Reset pointer hasil query
                                while($row = mysqli_fetch_assoc($resultKegiatan)): 
                                    $gambar = getLocalImage($row['gambar'], 'https://via.placeholder.com/70x60?text=No+Image');
                                    $tanggal = $row['tanggal'];
                                    $status = getStatusBadge($tanggal);
                                ?>
                                    <tr data-id="<?= $row['id'] ?>">
                                        <!-- CHECKBOX PER ITEM -->
                                        <td class="checkbox-cell">
                                            <input type="checkbox" 
                                                   class="item-checkbox" 
                                                   data-id="<?= $row['id'] ?>">
                                        </td>
                                        <!-- NOMOR URUT -->
                                        <td><?= $no++ ?></td>
                                        <!-- THUMBNAIL GAMBAR -->
                                        <td>
                                            <img src="<?= htmlspecialchars($gambar) ?>" 
                                                 alt="<?= htmlspecialchars($row['judul']) ?>" 
                                                 class="img-thumb"
                                                 onclick="previewImage('<?= htmlspecialchars($gambar) ?>')"
                                                 title="Klik untuk preview">
                                        </td>
                                        <!-- JUDUL DAN DESKRIPSI SINGKAT -->
                                        <td>
                                            <strong><?= htmlspecialchars($row['judul']) ?></strong>
                                            <?php if (!empty($row['deskripsi'])): ?>
                                                <div style="font-size: 13px; color: var(--text-light); margin-top: 4px;">
                                                    <?= htmlspecialchars(substr($row['deskripsi'], 0, 50)) . (strlen($row['deskripsi']) > 50 ? '...' : '') ?>
                                                </div>
                                            <?php endif; ?>
                                            <!-- LOKASI -->
                                            <div style="font-size: 12px; color: var(--text-light); margin-top: 3px;">
                                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['lokasi']) ?>
                                            </div>
                                        </td>
                                        <!-- TANGGAL DAN WAKTU -->
                                        <td>
                                            <div style="display: flex; flex-direction: column;">
                                                <div style="font-weight: 500; color: var(--primary);">
                                                    <?= formatTanggalIndo($tanggal) ?>
                                                </div>
                                                <div style="font-size: 12px; color: var(--text-light);">
                                                    <i class="fas fa-clock"></i> <?= date('H:i', strtotime($row['waktu'])) ?> WIB
                                                </div>
                                                <!-- KATEGORI -->
                                                <div style="font-size: 12px; color: var(--text-light); margin-top: 3px;">
                                                    <?= formatKategori($row['kategori']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- STATUS BADGE -->
                                        <td>
                                            <?= $status ?>
                                        </td>
                                        <!-- TOMBOL AKSI -->
                                        <td>
                                            <div class="action-buttons">
                                                <!-- TOMBOL PREVIEW -->
                                                <a href="admin_kegiatan.php?mode=preview&id=<?= $row['id'] ?>" 
                                                   class="action-btn view" 
                                                   title="Preview">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <!-- TOMBOL EDIT -->
                                                <a href="admin_kegiatan.php?mode=edit&id=<?= $row['id'] ?>" 
                                                   class="action-btn edit" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <!-- TOMBOL HAPUS DENGAN KONFIRMASI -->
                                                <a href="admin_kegiatan.php?mode=delete&id=<?= $row['id'] ?>" 
                                                   class="action-btn delete" 
                                                   title="Hapus"
                                                   onclick="return confirm('Yakin ingin menghapus kegiatan ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- STATE KOSONG (BELUM ADA DATA) -->
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Belum Ada Kegiatan</h3>
                        <p>Mulai dengan menambahkan kegiatan baru menggunakan form di samping.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- ====================================================== -->
<!-- MODAL PREVIEW GAMBAR -->
<!-- ====================================================== -->
<div class="modal-overlay" id="imageModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Preview Gambar</h2>
            <button class="modal-close" onclick="closeImageModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <img src="" alt="Preview" id="previewImage" style="max-width: 100%; max-height: 70vh; object-fit: contain; margin: 0 auto; display: block;">
        </div>
    </div>
</div>

<?php 
// INCLUDE TEMPLATE AKHIR (closing tags dan JS libraries)
include 'template_end.php'; 
?>

<!-- ====================================================== -->
<!-- JAVASCRIPT FUNCTIONS -->
<!-- ====================================================== -->
<script>
    // 1. FILE UPLOAD PREVIEW
    const fileInput = document.getElementById('gambar');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update tampilan label upload dengan preview gambar
                    const label = document.querySelector('.file-upload-label');
                    if (label) {
                        label.innerHTML = `
                            <div>
                                <img src="${e.target.result}" style="max-width: 150px; max-height: 100px; border-radius: 5px; margin-bottom: 10px;">
                                <div style="font-weight: 500; color: var(--success);">Gambar dipilih</div>
                                <div style="font-size: 13px; color: var(--text-light);">${file.name}</div>
                            </div>
                        `;
                    }
                };
                reader.readAsDataURL(file); // Baca file sebagai Data URL
            }
        });
    }
    
    // 2. BULK SELECTION FUNCTIONALITY
    // Variabel global untuk bulk selection
    const selectAll = document.getElementById('selectAll');
    const checkAllHeader = document.getElementById('checkAllHeader');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const bulkIdsInput = document.getElementById('bulkIds');
    
    /**
     * Fungsi untuk update state seleksi
     */
    function updateSelection() {
        // Filter checkbox yang terpilih
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        const count = selected.length;
        
        // Update tampilan jumlah yang terpilih
        selectedCount.textContent = count + ' item terpilih';
        
        // Tampilkan/sembunyikan bulk actions
        if (count > 0) {
            bulkActions.classList.add('active'); // Tampilkan bulk actions
            
            // Update style baris yang terpilih
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (cb.checked) {
                    row.style.backgroundColor = 'var(--gray-50)'; // Highlight baris
                } else {
                    row.style.backgroundColor = ''; // Reset warna
                }
            });
            
            // Update hidden input dengan ID yang terpilih
            const ids = selected.map(cb => cb.getAttribute('data-id'));
            bulkIdsInput.value = JSON.stringify(ids);
        } else {
            bulkActions.classList.remove('active'); // Sembunyikan bulk actions
            
            // Reset style semua baris
            checkboxes.forEach(cb => {
                cb.closest('tr').style.backgroundColor = '';
            });
        }
        
        // Update state "Select All" checkbox
        const allChecked = count === checkboxes.length && checkboxes.length > 0;
        const someChecked = count > 0 && count < checkboxes.length;
        
        // Update checkbox di sidebar
        if (selectAll) {
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked; // State indeterminate (setengah terpilih)
        }
        
        // Update checkbox di header tabel
        if (checkAllHeader) {
            checkAllHeader.checked = allChecked;
            checkAllHeader.indeterminate = someChecked;
        }
    }
    
    // 3. EVENT LISTENER UNTUK "SELECT ALL"
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            // Centang/batal centang semua checkbox
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateSelection(); // Update state
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
    
    // 4. EVENT LISTENER UNTUK INDIVIDUAL CHECKBOX
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
    
    // 5. FUNGSI CLEAR SELECTION (BATAL PILIH)
    function clearSelection() {
        // Uncheck semua checkbox
        checkboxes.forEach(cb => {
            cb.checked = false;
        });
        
        // Reset state checkbox select all
        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
        
        if (checkAllHeader) {
            checkAllHeader.checked = false;
            checkAllHeader.indeterminate = false;
        }
        
        updateSelection(); // Update tampilan
    }
    
    // 6. FORM SUBMISSION UNTUK BULK DELETE
    if (bulkDeleteForm) {
        bulkDeleteForm.addEventListener('submit', function(e) {
            const selected = Array.from(checkboxes).filter(cb => cb.checked);
            
            // Validasi: minimal satu item terpilih
            if (selected.length === 0) {
                e.preventDefault();
                alert('Pilih minimal satu kegiatan untuk dihapus!');
                return;
            }
            
            // Konfirmasi penghapusan
            if (!confirm(`Hapus ${selected.length} kegiatan yang dipilih?`)) {
                e.preventDefault();
                return;
            }
            
            // Konversi JSON ke hidden inputs
            const ids = selected.map(cb => cb.getAttribute('data-id'));
            const existingInputs = this.querySelectorAll('input[name="pilih_hapus[]"]');
            existingInputs.forEach(input => input.remove()); // Hapus input lama
            
            // Buat input hidden baru untuk setiap ID
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'pilih_hapus[]';
                input.value = id;
                this.appendChild(input);
            });
        });
    }
    
    // 7. IMAGE PREVIEW MODAL FUNCTIONS
    function previewImage(src) {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('previewImage');
        
        // Set sumber gambar dan tampilkan modal
        img.src = src;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Nonaktifkan scroll
    }
    
    function closeImageModal() {
        document.getElementById('imageModal').classList.remove('active');
        document.body.style.overflow = ''; // Aktifkan kembali scroll
    }
    
    // 8. KEYBOARD SHORTCUTS
    // Tutup modal dengan tombol ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageModal();
        }
    });
    
    // 9. CLICK OUTSIDE TO CLOSE MODAL
    const imageModal = document.getElementById('imageModal');
    if (imageModal) {
        imageModal.addEventListener('click', function(e) {
            // Tutup modal jika klik di luar konten modal
            if (e.target === this) {
                closeImageModal();
            }
        });
    }
    
    // 10. AUTO-HIDE MESSAGE ALERTS
    const messageAlert = document.getElementById('messageAlert');
    if (messageAlert) {
        // Sembunyikan pesan alert setelah 5 detik
        setTimeout(() => {
            messageAlert.style.transition = 'opacity 0.3s ease';
            messageAlert.style.opacity = '0';
            setTimeout(() => {
                // Hapus elemen dari DOM setelah animasi selesai
                if (messageAlert.parentNode) {
                    messageAlert.parentNode.removeChild(messageAlert);
                }
            }, 300);
        }, 5000);
    }
    
    // 11. INITIALIZATION ON PAGE LOAD
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi seleksi
        updateSelection();
        
        // Set tanggal default ke hari ini untuk form tambah
        const dateInput = document.getElementById('tanggal');
        if (dateInput && !dateInput.value) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.value = today;
            dateInput.min = today; // Set tanggal minimum ke hari ini
        }
    });
</script>