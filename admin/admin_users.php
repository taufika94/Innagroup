<?php
// Memulai session untuk menyimpan data login user
session_start();
// Menyertakan file konfigurasi koneksi database
include 'config.php';
// Menyertakan file header untuk bagian atas halaman
include 'header.php';

// --- PROTEKSI HALAMAN ADMIN ---
// Mengecek apakah user sudah login dan memiliki role 'admin'
// Jika tidak, redirect ke halaman login untuk mencegah akses ilegal
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Mengalihkan ke halaman login
    exit; // Menghentikan eksekusi script lebih lanjut
}

// AMBIL NAMA DARI SESSION
// Mengambil username admin yang sedang login untuk ditampilkan
$username = $_SESSION['username'];

// Set judul halaman untuk SEO dan tab browser
$page_title = "Manajemen User - PT INTIM";

// Variabel untuk sistem notifikasi
$pesan = '';        // Untuk pesan sukses (akan ditampilkan dengan warna hijau)
$pesan_error = '';  // Untuk pesan error (akan ditampilkan dengan warna merah)
$user_id = 0;       // Menyimpan ID user saat mode edit
$mode = 'tambah';   // Mode default: tambah user baru
$judul_form = 'Tambah User Baru'; // Judul form yang berubah sesuai mode

// Cek mode edit - jika ada parameter 'edit' di URL
// Contoh URL: admin_users.php?edit=5
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $user_id = intval($_GET['edit']); // Konversi parameter ke integer untuk keamanan
    $mode = 'edit'; // Ubah mode dari 'tambah' menjadi 'edit'
    $judul_form = 'Edit User'; // Ubah judul form sesuai mode
    
    // Query untuk mengambil data user dari database berdasarkan ID
    $query_select = "SELECT * FROM users WHERE id = $user_id";
    $result_select = mysqli_query($conn, $query_select); // Eksekusi query
    $user_data = mysqli_fetch_assoc($result_select); // Ambil hasil sebagai array
    
    // Jika user tidak ditemukan di database
    if (!$user_data) {
        $pesan_error = "User tidak ditemukan!"; // Set pesan error
        $mode = 'tambah'; // Kembalikan ke mode tambah
        $judul_form = 'Tambah User Baru'; // Reset judul form
    }
}

// Proses Hapus Single User - dipicu oleh parameter 'hapus' di URL
// Contoh URL: admin_users.php?hapus=5
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $hapus_id = intval($_GET['hapus']); // Ambil ID user yang akan dihapus
    
    // Validasi: cegah admin menghapus akun sendiri
    if (isset($_SESSION['user_id']) && $hapus_id == $_SESSION['user_id']) {
        $pesan_error = "Tidak dapat menghapus akun sendiri!"; // Pesan error keamanan
    } else {
        // Query DELETE untuk menghapus user dari tabel users
        $delete_query = "DELETE FROM users WHERE id = $hapus_id";
        if (mysqli_query($conn, $delete_query)) {
            $pesan = "User berhasil dihapus!"; // Pesan sukses
        } else {
            $pesan_error = "Gagal menghapus user: " . mysqli_error($conn); // Pesan error dengan detail
        }
    }
    
}

// Proses Reset Password User - dipicu oleh parameter 'reset' di URL
// Contoh URL: admin_users.php?reset=5
if (isset($_GET['reset']) && is_numeric($_GET['reset'])) {
    $reset_id = intval($_GET['reset']); // Ambil ID user yang akan direset passwordnya
    
    // Hash password default 'user123' menggunakan fungsi password_hash
    $new_password = password_hash('user123', PASSWORD_DEFAULT);
    // Query UPDATE untuk mengubah password di database
    $reset_query = "UPDATE users SET password = '$new_password' WHERE id = $reset_id";
    
    if (mysqli_query($conn, $reset_query)) {
        $pesan = "Password berhasil direset ke 'user123'!"; // Pesan sukses
    } else {
        $pesan_error = "Gagal reset password: " . mysqli_error($conn); // Pesan error
    }
    
}

// Proses Form Submission (Tambah/Edit User) - dipicu oleh POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Ambil dan sanitasi input dari form untuk mencegah SQL Injection
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    // Validasi input wajib diisi
    if (empty($username) || empty($role)) {
        $pesan_error = "Username dan Role wajib diisi!"; // Validasi gagal
    } else {
        // MODE TAMBAH USER BARU
        if ($mode === 'tambah') {
            // Cek apakah username sudah ada di database (unik constraint)
            $check_query = "SELECT id FROM users WHERE username = '$username'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $pesan_error = "Username sudah digunakan!"; // Username duplikat
            } else {
                // Hash password default menggunakan algoritma bcrypt
                $password = password_hash('user123', PASSWORD_DEFAULT);
                // Query INSERT untuk menambah user baru
                $query = "INSERT INTO users (username, password, role, created_at) 
                         VALUES ('$username', '$password', '$role', NOW())";
                
                if (mysqli_query($conn, $query)) {
                    $pesan = "User berhasil ditambahkan! Password default: 'user123'"; // Sukses
                    $_POST = []; // Kosongkan array POST untuk membersihkan form
                } else {
                    $pesan_error = "Gagal menyimpan ke database: " . mysqli_error($conn); // Error DB
                }
            }
        }
        // MODE EDIT USER YANG SUDAH ADA
        elseif ($mode === 'edit' && $user_id > 0) {
            // Cek duplikasi username, kecuali untuk user yang sedang diedit
            $check_query = "SELECT id FROM users WHERE username = '$username' AND id != $user_id";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $pesan_error = "Username sudah digunakan oleh user lain!"; // Username terpakai
            } else {
                // Query UPDATE untuk mengubah data user
                $query = "UPDATE users SET 
                username = '$username',
                role = '$role',
                updated_at = NOW()
                WHERE id = $user_id";

                
                if (mysqli_query($conn, $query)) {
                    $pesan = "User berhasil diperbarui!"; // Sukses update
                    // Refresh data user dari database untuk ditampilkan di form
                    $result_select = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
                    $user_data = mysqli_fetch_assoc($result_select);
                } else {
                    $pesan_error = "Gagal memperbarui database: " . mysqli_error($conn); // Error DB
                }
            }
        }
    }
}

// Logika Pencarian & Filter untuk Tabel User
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : ''; // Keyword pencarian
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : ''; // Filter berdasarkan role
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest'; // Urutan sorting, default: terbaru

// Query dasar untuk mengambil data user dengan placeholder WHERE 1=1
$query_users = "SELECT * FROM users WHERE 1=1"; // 1=1 memudahkan penambahan kondisi WHERE

// Tambahkan kondisi pencarian jika ada keyword
if ($search != '') {
    $query_users .= " AND username LIKE '%$search%'"; // Pencarian partial match
}

// Tambahkan filter role jika dipilih
if ($role_filter != '') {
    $query_users .= " AND role = '$role_filter'"; // Filter eksak match
}

// Logika Sorting berdasarkan pilihan user
if ($sort_order == 'newest') {
    $query_users .= " ORDER BY created_at DESC"; // Terbaru ke terlama
} elseif ($sort_order == 'oldest') {
    $query_users .= " ORDER BY created_at ASC"; // Terlama ke terbaru
} elseif ($sort_order == 'name') {
    $query_users .= " ORDER BY username ASC"; // Alfabet A-Z
}

// Eksekusi query untuk mengambil data user dengan filter dan sorting
$result_users = mysqli_query($conn, $query_users);

// Ambil pesan notifikasi dari URL parameter (setelah redirect)
if (isset($_GET['pesan']) && !empty($_GET['pesan'])) {
    $pesan = urldecode($_GET['pesan']); // Decode pesan sukses yang dikirim via URL
}
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $pesan_error = urldecode($_GET['error']); // Decode pesan error yang dikirim via URL
}

// Hitung statistik user untuk ditampilkan di dashboard
$total_users = mysqli_num_rows($result_users); // Total semua user (setelah filter)
$total_admin = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'")); // Hitung admin
$total_user = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE role = 'user'")); // Hitung user biasa
$users_today = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM users WHERE DATE(created_at) = CURDATE()")); // User hari ini

// Fungsi untuk format tanggal Indonesia yang lebih user-friendly
function tgl_indo($datetime) {
    // Handle nilai datetime kosong atau invalid
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') return '-';
    
    // Array nama bulan singkat dalam Bahasa Indonesia
    $bulan = array(
        'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    );
    
    $timestamp = strtotime($datetime); // Konversi string ke timestamp UNIX
    // Format: "01 Jan 2023 14:30"
    return date('d', $timestamp) . ' ' . $bulan[(int)date('m', $timestamp) - 1] . ' ' . date('Y', $timestamp) . ' ' . date('H:i', $timestamp);
}
?>

<?php include 'template_start.php'; // Menyertakan template awal yang berisi CSS, JS, meta tags ?>

<main class="main-content">
    <header class="top-header">
        <div>
            <h1 class="page-title">Manajemen User</h1>
            <p style="color: var(--text-light); font-size: 14px;">Kelola akun pengguna dan administrator PT INTIM</p>
        </div>
        
        <div class="header-actions">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="content-area">
        <!-- Flash Messages untuk menampilkan notifikasi sukses/error -->
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

        <!-- Stats Cards: Dashboard visual statistik user -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_users ?></h3>
                    <p>Total User</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_admin ?></h3>
                    <p>Admin</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_user ?></h3>
                    <p>Regular User</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon review">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $users_today ?></h3>
                    <p>Ditambah Hari Ini</p>
                </div>
            </div>
        </div>

        <!-- Action Bar: Area untuk pencarian dan tombol aksi utama -->
        <div class="action-bar" style="margin-bottom: 20px;">
            <div class="search-box">
                <form action="" method="GET">
                    <input type="text" name="search" class="search-input" placeholder="Cari username..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="btn-group">
                <a href="?add=true" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah User
                </a>
            </div>
        </div>

        <!-- Filter Section: Filter dan sorting data tabel -->
        <div class="filter-section" style="margin-bottom: 20px;">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label class="filter-label">Role</label>
                    <select name="role" class="filter-select" onchange="this.form.submit()">
                        <option value="">Semua Role</option>
                        <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="user" <?= $role_filter == 'user' ? 'selected' : '' ?>>User</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Urutkan</label>
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="newest" <?= $sort_order == 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="oldest" <?= $sort_order == 'oldest' ? 'selected' : '' ?>>Terlama</option>
                        <option value="name" <?= $sort_order == 'name' ? 'selected' : '' ?>>A-Z Username</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <div class="filter-buttons">
                        <span style="font-size: 14px; color: var(--text-light);">
                            Total: <b><?= $total_users ?></b>
                        </span>
                        <?php if ($search != '' || $role_filter != ''): ?>
                            <a href="admin_users.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Hapus Filter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Main Content Grid: Layout form dan tabel berdampingan -->
        <div style="display: flex; flex-direction: column; gap: 30px; margin-bottom: 40px;">
            <!-- Form Tambah/Edit User -->
            <?php if (isset($_GET['add']) || $mode === 'edit'): ?>
            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas <?= $mode === 'edit' ? 'fa-edit' : 'fa-user-plus' ?>"></i>
                    <?= $mode === 'edit' ? 'Edit User' : 'Tambah User Baru' ?>
                </h2>
                
                <form action="" method="POST">
                    <input type="hidden" name="mode" value="<?= $mode ?>">
                    
                    <div class="form-group">
                        <label for="username" class="form-label required">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" id="username" name="username" class="form-control" required 
                               value="<?= $mode === 'edit' ? htmlspecialchars($user_data['username']) : (isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '') ?>"
                               placeholder="Masukkan username">
                        <div class="form-text">Username harus unik</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role" class="form-label required">
                            <i class="fas fa-user-tag"></i> Role
                        </label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="user" <?= ($mode === 'edit' && $user_data['role'] == 'user') || (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= ($mode === 'edit' && $user_data['role'] == 'admin') || (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    
                    <?php if ($mode === 'tambah'): ?>
                    <div class="alert alert-info" style="background: var(--info-light); color: var(--info); padding: 12px; border-radius: var(--radius); border-left: 4px solid var(--info); margin-bottom: 20px;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Informasi:</strong> Password default untuk user baru adalah <strong>user123</strong>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn <?= $mode === 'edit' ? 'btn-success' : 'btn-primary' ?>">
                            <i class="fas <?= $mode === 'edit' ? 'fa-save' : 'fa-user-plus' ?>"></i>
                            <?= $mode === 'edit' ? 'Update User' : 'Simpan User' ?>
                        </button>
                        
                        <a href="admin_users.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Daftar User dalam bentuk Tabel -->
            <div class="table-container" <?php if (isset($_GET['add']) || $mode === 'edit'): ?>style="grid-column: span 2;"<?php endif; ?>>
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list"></i> Daftar User
                    </div>
                </div>
                
                <?php if ($total_users > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="usersTable">
                                <?php 
                                $no = 1;
                                mysqli_data_seek($result_users, 0); // Reset pointer ke awal result set
                                while($row = mysqli_fetch_assoc($result_users)): 
                                    $tanggal = tgl_indo($row['created_at']); // Format tanggal Indonesia
                                    $current_session_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; // ID user yang login
                                    $is_current_user = ($row['id'] == $current_session_id); // Cek apakah ini user yang sedang login
                                ?>
                                    <tr data-id="<?= $row['id'] ?>">
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 36px; height: 36px; background: <?= $row['role'] == 'admin' ? 'var(--primary)' : 'var(--accent)'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                                    <?= strtoupper(substr($row['username'], 0, 1)) ?> <!-- Avatar huruf pertama -->
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; font-size: 14px;">
                                                        <?= htmlspecialchars($row['username']) ?>
                                                        <?php if ($is_current_user): ?>
                                                            <span style="font-size: 11px; background: var(--primary-light); color: var(--primary); padding: 2px 6px; border-radius: 10px; margin-left: 8px;">Anda</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $row['role'] == 'admin' ? 'status-active' : 'status-review' ?>">
                                                <?= $row['role'] == 'admin' ? 'Admin' : 'User' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; font-size: 14px; color: var(--text-light);">
                                                <?= $tanggal ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- Tombol Edit: Redirect ke mode edit dengan ID user -->
                                                <a href="admin_users.php?edit=<?= $row['id'] ?>" 
                                                   class="action-btn edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <!-- Tombol Reset Password: Panggil fungsi JavaScript -->
                                                <button type="button" 
                                                        class="action-btn status"
                                                        onclick="confirmReset(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['username'])) ?>')"
                                                        title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if (!$is_current_user): ?>
                                                    <!-- Tombol Hapus: Hanya tampil untuk user lain -->
                                                    <button type="button" 
                                                            class="action-btn delete"
                                                            onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['username'])) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: var(--text-light); font-size: 12px; padding: 0 8px;">Tidak dapat hapus</span>
                                                <?php endif; ?>
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
                        <h3>Belum Ada User</h3>
                        <p>Mulai tambah user pertama Anda.</p>
                        <?php if (!isset($_GET['add']) && $mode !== 'edit'): ?>
                            <a href="?add=true" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Tambah User
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal: Popup konfirmasi sebelum menghapus -->
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
                    Apakah Anda yakin ingin menghapus user ini?
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

<!-- Reset Password Confirmation Modal: Popup konfirmasi reset password -->
<div class="modal-overlay" id="resetModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Reset Password</h2>
            <button class="modal-close" onclick="closeResetModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 70px; color: var(--warning); margin-bottom: 20px;">
                    <i class="fas fa-key"></i>
                </div>
                <h3 id="resetMessage" style="margin-bottom: 15px; color: var(--text-dark);">
                    Reset password user?
                </h3>
                <p style="color: var(--text-light); margin-bottom: 20px;">
                    Password akan direset ke: <strong>user123</strong>
                </p>
                <p style="color: var(--text-light); font-size: 14px; margin-bottom: 30px;">
                    Pastikan untuk memberitahukan password baru kepada user terkait.
                </p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button onclick="closeResetModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <a href="#" id="confirmResetBtn" class="btn btn-warning">
                        <i class="fas fa-key"></i> Ya, Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'template_end.php'; // Menyertakan template akhir (footer, JS scripts) ?>

<script>
    // Delete Confirmation Modal Logic
    let currentDeleteId = null; // Menyimpan ID user yang akan dihapus
    
    function confirmDelete(id, username) {
        currentDeleteId = id;
        const modal = document.getElementById('deleteModal');
        const message = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        // Set pesan konfirmasi dengan nama user
        message.textContent = `Apakah Anda yakin ingin menghapus user "${username}"?`;
        // Set URL untuk tombol konfirmasi
        confirmBtn.href = `admin_users.php?hapus=${id}`;
        
        // Tampilkan modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Nonaktifkan scroll
    }
    
    // Reset Password Confirmation Modal Logic
    let currentResetId = null; // Menyimpan ID user yang akan direset
    
    function confirmReset(id, username) {
        currentResetId = id;
        const modal = document.getElementById('resetModal');
        const message = document.getElementById('resetMessage');
        const confirmBtn = document.getElementById('confirmResetBtn');
        
        // Set pesan konfirmasi dengan nama user
        message.textContent = `Reset password user "${username}"?`;
        // Set URL untuk tombol konfirmasi
        confirmBtn.href = `admin_users.php?reset=${id}`;
        
        // Tampilkan modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Nonaktifkan scroll
    }
    
    // Fungsi untuk menutup modal delete
    function closeModal() {
        document.getElementById('deleteModal').classList.remove('active');
        document.body.style.overflow = ''; // Aktifkan kembali scroll
        currentDeleteId = null; // Reset ID
    }
    
    // Fungsi untuk menutup modal reset
    function closeResetModal() {
        document.getElementById('resetModal').classList.remove('active');
        document.body.style.overflow = ''; // Aktifkan kembali scroll
        currentResetId = null; // Reset ID
    }
    
    // Initialize saat halaman selesai dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide messages setelah 5 detik
        setTimeout(function() {
            const alerts = document.querySelectorAll('.message-alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0'; // Efek fade out
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert); // Hapus dari DOM
                    }
                }, 300); // Tunggu 300ms untuk animasi
            });
        }, 5000); // Tunggu 5 detik sebelum hide
        
        // Scroll otomatis ke form jika dalam mode edit atau add
        <?php if ($mode === 'edit' || isset($_GET['add'])): ?>
            document.querySelector('.form-container')?.scrollIntoView({ 
                behavior: 'smooth', // Animasi scroll smooth
                block: 'start' // Posisi scroll ke atas form
            });
        <?php endif; ?>
    });
    
    // Prevent form resubmission on refresh (Post/Redirect/Get pattern)
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Close modals on ESC key press
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeResetModal();
        }
    });
    
    // Close modal ketika klik di luar area modal
    const deleteModal = document.getElementById('deleteModal');
    const resetModal = document.getElementById('resetModal');
    
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) { // Cek jika klik di overlay (bukan di konten modal)
                closeModal();
            }
        });
    }
    
    if (resetModal) {
        resetModal.addEventListener('click', function(e) {
            if (e.target === this) { // Cek jika klik di overlay (bukan di konten modal)
                closeResetModal();
            }
        });
    }
</script>