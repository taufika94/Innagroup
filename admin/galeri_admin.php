<?php
session_start();
include 'config.php';
include 'header.php';



// Set page title
$page_title = "Kelola Galeri - PT INTIM";

// Variabel untuk pesan
$pesan = '';
$pesan_error = '';
$galeri_id = 0;
$mode = 'tambah';
$judul_form = 'Tambah Galeri Baru';

// PROTEKSI: Jika belum login, tendang ke halaman login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// AMBIL NAMA DARI SESSION
$username = $_SESSION['username'];

// Cek mode edit
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $galeri_id = intval($_GET['edit']);
    $mode = 'edit';
    $judul_form = 'Edit Galeri';
    
    // Ambil data galeri untuk diedit
    $query_select = "SELECT * FROM berita_galeri WHERE id = $galeri_id";
    $result_select = mysqli_query($conn, $query_select);
    $galeri_data = mysqli_fetch_assoc($result_select);
    
    if (!$galeri_data) {
        $pesan_error = "Galeri tidak ditemukan!";
        $mode = 'tambah';
        $judul_form = 'Tambah Galeri Baru';
    }
}

// Proses Hapus Single
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $hapus_id = intval($_GET['hapus']);
    
    // Ambil data sebelum dihapus
    $query_hapus = "SELECT * FROM berita_galeri WHERE id = $hapus_id";
    $result_hapus = mysqli_query($conn, $query_hapus);
    $data_hapus = mysqli_fetch_assoc($result_hapus);
    
    if ($data_hapus) {
        // Hapus file gambar fisik dari server
        $gambar_path = $data_hapus['gambar'];
        if (!empty($gambar_path) && file_exists($gambar_path)) {
            // Cek apakah file masih digunakan oleh galeri lain
            $check_query = "SELECT COUNT(*) as count FROM berita_galeri WHERE gambar = '$gambar_path' AND id != $hapus_id";
            $check_result = mysqli_query($conn, $check_query);
            $check_data = mysqli_fetch_assoc($check_result);
            
            // Hapus file hanya jika tidak digunakan oleh galeri lain
            if ($check_data['count'] == 0) {
                unlink($gambar_path);
            }
        }
        
        // Hapus dari database
        $delete_query = "DELETE FROM berita_galeri WHERE id = $hapus_id";
        if (mysqli_query($conn, $delete_query)) {
            $pesan = "Galeri berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus galeri: " . mysqli_error($conn);
        }
    } else {
        $pesan_error = "Galeri tidak ditemukan!";
    }
    
    // Redirect untuk refresh data
    header("Location: galeri_admin.php?pesan=" . urlencode($pesan) . "&error=" . urlencode($pesan_error));
    exit;
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
            
            // Hapus file gambar fisik
            $res_gambar = mysqli_query($conn, "SELECT gambar FROM berita_galeri WHERE id IN ($string_ids)");
            $files_deleted = 0;
            
            while ($g = mysqli_fetch_assoc($res_gambar)) {
                if (!empty($g['gambar']) && file_exists($g['gambar'])) {
                    // Cek apakah file masih digunakan
                    $check_query = "SELECT COUNT(*) as count FROM berita_galeri WHERE gambar = '{$g['gambar']}' AND id NOT IN ($string_ids)";
                    $check_result = mysqli_query($conn, $check_query);
                    $check_data = mysqli_fetch_assoc($check_result);
                    
                    if ($check_data['count'] == 0) {
                        if (unlink($g['gambar'])) {
                            $files_deleted++;
                        }
                    }
                }
            }
            
            // Hapus dari database
            $query_hapus = "DELETE FROM berita_galeri WHERE id IN ($string_ids)";
            if (mysqli_query($conn, $query_hapus)) {
                $pesan = "Berhasil menghapus $jumlah_hapus galeri!";
            } else {
                $pesan_error = "Gagal menghapus data: " . mysqli_error($conn);
            }
        }
    } else {
        $pesan_error = "Pilih minimal satu galeri untuk dihapus!";
    }
}

// Proses Upload/Edit Galeri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $tanggal = date('Y-m-d');
    
    // Handle file upload untuk mode tambah
    if ($mode === 'tambah') {
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['gambar']['tmp_name'];
            $file_name = basename($_FILES['gambar']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validasi ekstensi file
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_ext)) {
                // Generate unique filename
                $new_filename = uniqid('galeri_') . '.' . $file_ext;
                $upload_path = '../uploads/galeri/' . $new_filename;
                
                // Create directory if not exists
                if (!file_exists('../uploads/galeri')) {
                    mkdir('../uploads/galeri', 0777, true);
                }
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Insert ke database
                    $query = "INSERT INTO berita_galeri (judul, deskripsi, gambar, kategori, tanggal) 
                             VALUES ('$judul', '$deskripsi', '$upload_path', '$kategori', '$tanggal')";
                    
                    if (mysqli_query($conn, $query)) {
                        $pesan = "Galeri berhasil ditambahkan!";
                        $_POST = []; // Clear form
                    } else {
                        $pesan_error = "Gagal menyimpan ke database: " . mysqli_error($conn);
                    }
                } else {
                    $pesan_error = "Gagal mengupload gambar.";
                }
            } else {
                $pesan_error = "Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.";
            }
        } else {
            $pesan_error = "Silakan pilih gambar.";
        }
    }
    // Handle edit mode
    elseif ($mode === 'edit' && $galeri_id > 0) {
        // Jika ada file baru diupload
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['gambar']['tmp_name'];
            $file_name = basename($_FILES['gambar']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_ext)) {
                // Hapus file lama jika ada
                if (!empty($galeri_data['gambar']) && file_exists($galeri_data['gambar'])) {
                    // Cek apakah file masih digunakan
                    $check_query = "SELECT COUNT(*) as count FROM berita_galeri WHERE gambar = '{$galeri_data['gambar']}' AND id != $galeri_id";
                    $check_result = mysqli_query($conn, $check_query);
                    $check_data = mysqli_fetch_assoc($check_result);
                    
                    if ($check_data['count'] == 0) {
                        unlink($galeri_data['gambar']);
                    }
                }
                
                // Upload file baru
                $new_filename = uniqid('galeri_') . '.' . $file_ext;
                $upload_path = '../uploads/galeri/' . $new_filename;
                
                if (!file_exists('../uploads/galeri')) {
                    mkdir('../uploads/galeri', 0777, true);
                }
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $gambar_path = $upload_path;
                } else {
                    $pesan_error = "Gagal mengupload gambar baru.";
                    $gambar_path = $galeri_data['gambar'];
                }
            } else {
                $pesan_error = "Format file tidak didukung.";
                $gambar_path = $galeri_data['gambar'];
            }
        } else {
            // Gunakan gambar lama
            $gambar_path = $galeri_data['gambar'];
        }
        
        if (empty($pesan_error)) {
            // Update database
            $query = "UPDATE berita_galeri SET 
                     judul = '$judul',
                     deskripsi = '$deskripsi',
                     gambar = '$gambar_path',
                     kategori = '$kategori',
                     tanggal = '$tanggal'
                     WHERE id = $galeri_id";
            
            if (mysqli_query($conn, $query)) {
                $pesan = "Galeri berhasil diperbarui!";
                // Refresh data
                $result_select = mysqli_query($conn, "SELECT * FROM berita_galeri WHERE id = $galeri_id");
                $galeri_data = mysqli_fetch_assoc($result_select);
            } else {
                $pesan_error = "Gagal memperbarui database: " . mysqli_error($conn);
            }
        }
    }
}

// Query untuk mengambil data galeri
$query_galeri = "SELECT * FROM berita_galeri ORDER BY tanggal DESC";
$result_galeri = mysqli_query($conn, $query_galeri);

// Query untuk kategori unik
$query_kategori = "SELECT DISTINCT kategori FROM berita_galeri WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori";
$result_kategori = mysqli_query($conn, $query_kategori);

// Ambil pesan dari URL
if (isset($_GET['pesan']) && !empty($_GET['pesan'])) {
    $pesan = urldecode($_GET['pesan']);
}
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $pesan_error = urldecode($_GET['error']);
}

// Hitung statistik
$total_galeri = mysqli_num_rows($result_galeri);
$galeri_hari_ini = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM berita_galeri WHERE DATE(tanggal) = CURDATE()"));
$galeri_bulan_ini = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM berita_galeri WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())"));
$total_kategori = mysqli_num_rows($result_kategori);
?>

<?php include 'template_start.php'; ?>

<main class="main-content">
    <header class="top-header">
        <div>
            <h1 class="page-title">Kelola Galeri</h1>
            <p style="color: var(--text-light); font-size: 14px;">Upload dan kelola gambar galeri</p>
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
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $total_galeri ?></h3>
                    <p>Total Galeri</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $galeri_hari_ini ?></h3>
                    <p>Hari Ini</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h3><?= $galeri_bulan_ini ?></h3>
                    <p>Bulan Ini</p>
                </div>
            </div>
            
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

        <!-- Main Content Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
            <!-- Form Upload/Edit -->
            <div class="form-container">
                <h2 class="form-title">
                    <i class="fas <?= $mode === 'edit' ? 'fa-edit' : 'fa-upload' ?>"></i>
                    <?= $judul_form ?>
                </h2>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="<?= $mode ?>">
                    
                    <div class="form-group">
                        <label for="judul" class="form-label required">
                            <i class="fas fa-heading"></i> Judul Gambar
                        </label>
                        <input type="text" id="judul" name="judul" class="form-control" required 
                               value="<?= $mode === 'edit' ? htmlspecialchars($galeri_data['judul']) : (isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : '') ?>"
                               placeholder="Masukkan judul gambar">
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi" class="form-label">
                            <i class="fas fa-align-left"></i> Deskripsi
                        </label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="4" 
                                  placeholder="Masukkan deskripsi gambar"><?= $mode === 'edit' ? htmlspecialchars($galeri_data['deskripsi']) : (isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="kategori" class="form-label required">
                            <i class="fas fa-tag"></i> Kategori
                        </label>
                        <select id="kategori" name="kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Kegiatan Perusahaan" <?= ($mode === 'edit' && $galeri_data['kategori'] == 'Kegiatan Perusahaan') || (isset($_POST['kategori']) && $_POST['kategori'] == 'Kegiatan Perusahaan') ? 'selected' : '' ?>>Kegiatan Perusahaan</option>
                            <option value="Produksi" <?= ($mode === 'edit' && $galeri_data['kategori'] == 'Produksi') || (isset($_POST['kategori']) && $_POST['kategori'] == 'Produksi') ? 'selected' : '' ?>>Produksi</option>
                            <option value="Event" <?= ($mode === 'edit' && $galeri_data['kategori'] == 'Event') || (isset($_POST['kategori']) && $_POST['kategori'] == 'Event') ? 'selected' : '' ?>>Event</option>
                            <option value="Tim" <?= ($mode === 'edit' && $galeri_data['kategori'] == 'Tim') || (isset($_POST['kategori']) && $_POST['kategori'] == 'Tim') ? 'selected' : '' ?>>Tim</option>
                            <option value="Fasilitas" <?= ($mode === 'edit' && $galeri_data['kategori'] == 'Fasilitas') || (isset($_POST['kategori']) && $_POST['kategori'] == 'Fasilitas') ? 'selected' : '' ?>>Fasilitas</option>
                            <?php 
                            mysqli_data_seek($result_kategori, 0);
                            while($kat = mysqli_fetch_assoc($result_kategori)): 
                                if (!empty($kat['kategori']) && !in_array($kat['kategori'], ['Kegiatan Perusahaan', 'Produksi', 'Event', 'Tim', 'Fasilitas'])) {
                                    $selected = ($mode === 'edit' && $galeri_data['kategori'] == $kat['kategori']) || (isset($_POST['kategori']) && $_POST['kategori'] == $kat['kategori']);
                            ?>
                                <option value="<?= htmlspecialchars($kat['kategori']) ?>" <?= $selected ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['kategori']) ?>
                                </option>
                            <?php 
                                }
                            endwhile; 
                            ?>
                            <option value="Lainnya" <?= ($mode === 'edit' && $galeri_data['kategori'] == 'Lainnya') || (isset($_POST['kategori']) && $_POST['kategori'] == 'Lainnya') ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="gambar" class="form-label">
                            <i class="fas fa-image"></i> Gambar <?= $mode === 'tambah' ? '<span class="required">*</span>' : '' ?>
                        </label>
                        <div class="file-upload">
                            <input type="file" id="gambar" name="gambar" accept="image/*" <?= $mode === 'tambah' ? 'required' : '' ?>>
                            <label for="gambar" class="file-upload-label">
                                <div>
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 40px; color: var(--accent); margin-bottom: 10px;"></i>
                                    <div style="font-weight: 500; color: var(--accent); margin-bottom: 5px;">Klik untuk upload gambar</div>
                                    <div style="font-size: 13px; color: var(--text-light);">Format: JPG, PNG, GIF, WebP (Max: 5MB)</div>
                                </div>
                            </label>
                        </div>
                        
                        <?php if ($mode === 'edit' && !empty($galeri_data['gambar']) && file_exists($galeri_data['gambar'])): ?>
                        <div style="margin-top: 15px;">
                            <div style="font-size: 13px; color: var(--text-light); margin-bottom: 5px;">Gambar saat ini:</div>
                            <img src="<?= htmlspecialchars($galeri_data['gambar']) ?>" 
                                 alt="Current Image" 
                                 class="img-thumb"
                                 style="cursor: pointer; max-width: 200px;"
                                 onclick="previewImage('<?= htmlspecialchars($galeri_data['gambar']) ?>')">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="submit" class="btn <?= $mode === 'edit' ? 'btn-success' : 'btn-primary' ?>">
                            <i class="fas <?= $mode === 'edit' ? 'fa-save' : 'fa-upload' ?>"></i>
                            <?= $mode === 'edit' ? 'Update Galeri' : 'Upload Galeri' ?>
                        </button>
                        
                        <?php if ($mode === 'edit'): ?>
                            <a href="galeri_admin.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Daftar Galeri -->
            <div class="table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-list"></i> Daftar Galeri
                    </div>
                    
                    <?php if ($total_galeri > 0): ?>
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
                
                <?php if ($total_galeri > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" id="checkAllHeader" class="check-all">
                                    </th>
                                    <th>No</th>
                                    <th>Gambar</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="galeriTable">
                                <?php 
                                $no = 1;
                                mysqli_data_seek($result_galeri, 0);
                                while($row = mysqli_fetch_assoc($result_galeri)): 
                                    $gambar = file_exists($row['gambar']) ? $row['gambar'] : 'https://via.placeholder.com/70x60?text=No+Image';
                                    $tanggal = date('d M Y', strtotime($row['tanggal']));
                                ?>
                                    <tr data-id="<?= $row['id'] ?>">
                                        <td class="checkbox-cell">
                                            <input type="checkbox" 
                                                   class="item-checkbox" 
                                                   data-id="<?= $row['id'] ?>">
                                        </td>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <img src="<?= htmlspecialchars($gambar) ?>" 
                                                 alt="<?= htmlspecialchars($row['judul']) ?>" 
                                                 class="img-thumb"
                                                 onclick="previewImage('<?= htmlspecialchars($gambar) ?>')"
                                                 title="Klik untuk preview">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['judul']) ?></strong>
                                            <?php if (!empty($row['deskripsi'])): ?>
                                                <div style="font-size: 13px; color: var(--text-light); margin-top: 4px;">
                                                    <?= htmlspecialchars(substr($row['deskripsi'], 0, 50)) . (strlen($row['deskripsi']) > 50 ? '...' : '') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-active">
                                                <?= htmlspecialchars($row['kategori'] ?? 'Tanpa Kategori') ?>
                                            </span>
                                        </td>
                                        <td><?= $tanggal ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="galeri_admin.php?edit=<?= $row['id'] ?>" 
                                                   class="action-btn edit">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <button type="button" 
                                                        class="action-btn delete"
                                                        onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')">
                                                    <i class="fas fa-trash"></i> Hapus
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
                        <i class="fas fa-images"></i>
                        <h3>Belum Ada Galeri</h3>
                        <p>Mulai upload gambar pertama Anda menggunakan form di samping.</p>
                    </div>
                <?php endif; ?>
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
                    Apakah Anda yakin ingin menghapus galeri ini?
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

<!-- Image Preview Modal -->
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

<?php include 'template_end.php'; ?>

<script>
    // File Input Preview
    const fileInput = document.getElementById('gambar');
    const fileText = document.getElementById('fileText');
    
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update file upload label
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
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Bulk Selection Functionality
    const selectAll = document.getElementById('selectAll');
    const checkAllHeader = document.getElementById('checkAllHeader');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteForm = document.getElementById('bulkDeleteForm');
    const bulkIdsInput = document.getElementById('bulkIds');
    const galeriTable = document.getElementById('galeriTable');
    
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
                alert('Pilih minimal satu galeri untuk dihapus!');
                return;
            }
            
            if (!confirm(`Hapus ${selected.length} galeri yang dipilih?`)) {
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
    
    function confirmDelete(id, title) {
        currentDeleteId = id;
        const modal = document.getElementById('deleteModal');
        const message = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        message.textContent = `Apakah Anda yakin ingin menghapus galeri "${title}"?`;
        confirmBtn.href = `galeri_admin.php?hapus=${id}`;
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        document.getElementById('deleteModal').classList.remove('active');
        document.body.style.overflow = '';
        currentDeleteId = null;
    }
    
    // Image Preview Modal
    function previewImage(src) {
        const modal = document.getElementById('imageModal');
        const img = document.getElementById('previewImage');
        
        img.src = src;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeImageModal() {
        document.getElementById('imageModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Close modals on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeImageModal();
        }
    });
    
    // Close modals on outside click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                if (this.id === 'deleteModal') closeModal();
                if (this.id === 'imageModal') closeImageModal();
            }
        });
    });
    
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
        
        // Scroll to form if in edit mode
        <?php if ($mode === 'edit'): ?>
            document.querySelector('.form-container').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        <?php endif; ?>
    });
    
    // Prevent form resubmission on refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>