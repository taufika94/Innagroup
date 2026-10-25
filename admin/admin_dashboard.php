<?php
// 1. Inisialisasi Session & Koneksi
session_start(); // Memulai session PHP untuk menyimpan data pengguna

include 'config.php'; // Menghubungkan ke database (berisi koneksi MySQL)
include 'header.php'; // Memasukkan file header 

// PROTEKSI: Jika belum login, tendang ke halaman login
if (!isset($_SESSION['username'])) { // Cek apakah session username ada
    header("Location: ../login.php"); // Redirect ke halaman login
    exit; // Hentikan eksekusi skrip
}

// AMBIL NAMA DARI SESSION
$username = $_SESSION['username']; // Simpan username dari session ke variabel

// Set page title
$page_title = "Admin Dashboard - PT INTIM"; // Judul halaman untuk tag <title>

// 2. Logika Hapus Massal (Bulk Delete)
if (isset($_POST['hapus_masal'])) { // Cek jika form hapus massal disubmit
    if (!empty($_POST['pilih_hapus'])) { // Cek jika ada produk yang dipilih
        $ids = $_POST['pilih_hapus']; // Ambil array ID produk yang dipilih
        
        // Sanitasi ID untuk keamanan SQL Injection
        $ids_clean = array_map(function($id) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $id) . "'"; // Escape setiap ID
        }, $ids);
        
        $string_ids = implode(',', $ids_clean); // Gabungkan ID menjadi string

        // Hapus file gambar fisik dari folder uploads sebelum hapus data di database
        $res_gambar = mysqli_query($conn, "SELECT gambar FROM produk WHERE kode_inna IN ($string_ids)");
        while ($g = mysqli_fetch_assoc($res_gambar)) { // Loop setiap produk
            $files = explode(',', $g['gambar']); // Pisahkan string gambar menjadi array
            foreach ($files as $file) { // Loop setiap file gambar
                $file_path = trim($file); // Hilangkan spasi di awal/akhir
                if (!empty($file_path) && file_exists($file_path)) { // Cek jika file ada
                    unlink($file_path); // Hapus file dari server
                }
            }
        }

        // Jalankan Query Hapus
        $query_hapus = "DELETE FROM produk WHERE kode_inna IN ($string_ids)";
        if (mysqli_query($conn, $query_hapus)) { // Jika query berhasil
            echo "<script>alert('Produk terpilih berhasil dihapus!'); window.location='admin_dashboard.php';</script>";
            exit; // Hentikan eksekusi
        } else { // Jika query gagal
            echo "<script>alert('Gagal menghapus data: " . mysqli_error($conn) . "');</script>";
        }
    } else { // Jika tidak ada produk yang dipilih
        echo "<script>alert('Pilih minimal satu produk untuk dihapus!');</script>";
    }
}

// 3. Logika Pencarian & Filter
$search = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : ''; // Ambil parameter pencarian
$motif_filter = isset($_GET['motif']) ? mysqli_real_escape_string($conn, $_GET['motif']) : ''; // Filter motif
$tipe_filter = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, $_GET['tipe']) : ''; // Filter tipe

$query_sql = "SELECT * FROM produk WHERE 1=1"; // Query dasar dengan kondisi selalu benar

if ($search != '') { // Jika ada pencarian
    $query_sql .= " AND (motif_kain LIKE '%$search%' OR kode_inna LIKE '%$search%')"; // Tambah kondisi pencarian
}
if ($motif_filter != '') { // Jika ada filter motif
    $query_sql .= " AND motif_kain = '$motif_filter'"; // Filter berdasarkan motif
}
if ($tipe_filter != '') { // Jika ada filter tipe
    $query_sql .= " AND tipe_kain = '$tipe_filter'"; // Filter berdasarkan tipe
}

$result = mysqli_query($conn, $query_sql); // Eksekusi query
$total_tampil = mysqli_num_rows($result); // Hitung total baris hasil query

$list_motif = mysqli_query($conn, "SELECT DISTINCT motif_kain FROM produk ORDER BY motif_kain ASC"); // Ambil daftar motif unik
$list_tipe = mysqli_query($conn, "SELECT DISTINCT tipe_kain FROM produk ORDER BY tipe_kain ASC"); // Ambil daftar tipe unik

// Hitung statistik
$total_produk = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM produk")); // Total semua produk
$total_kategori = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT tipe_kain FROM produk")); // Total kategori unik
$total_motif = mysqli_num_rows(mysqli_query($conn, "SELECT DISTINCT motif_kain FROM produk")); // Total motif unik
$produk_baru = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM produk WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")); // Produk 7 hari terakhir
?>

<?php include 'template_start.php'; ?> <!-- Masukkan template awal (HTML, head, sidebar) -->

<main class="main-content"> <!-- Kontainer utama konten -->
    <header class="top-header"> <!-- Header halaman -->
        <div>
            <h1 class="page-title">Dashboard Admin</h1> <!-- Judul halaman -->
            <p style="color: var(--text-light); font-size: 14px;">Kelola produk dan konten PT INTIM</p> <!-- Subjudul -->
        </div>
        
        <div class="header-actions"> <!-- Area aksi header -->
            <button class="mobile-menu-btn" id="mobileMenuBtn"> <!-- Tombol menu mobile -->
                <i class="fas fa-bars"></i> <!-- Icon menu -->
            </button>
        </div>
    </header>

    <div class="content-area"> <!-- Area konten utama -->
        <!-- Stats Cards -->
        <div class="stats-grid"> <!-- Grid untuk statistik -->
            <div class="stat-card"> <!-- Kartu statistik 1 -->
                <div class="stat-icon total"> <!-- Icon statistik -->
                    <i class="fas fa-box"></i> <!-- Icon kotak -->
                </div>
                <div class="stat-content"> <!-- Konten statistik -->
                    <h3><?= $total_produk ?></h3> <!-- Tampilkan total produk -->
                    <p>Total Produk</p> <!-- Label -->
                </div>
            </div>
            
            <div class="stat-card"> <!-- Kartu statistik 2 -->
                <div class="stat-icon active">
                    <i class="fas fa-tags"></i> <!-- Icon tag -->
                </div>
                <div class="stat-content">
                    <h3><?= $total_kategori ?></h3> <!-- Tampilkan total kategori -->
                    <p>Kategori</p>
                </div>
            </div>
            
            <div class="stat-card"> <!-- Kartu statistik 3 -->
                <div class="stat-icon pending">
                    <i class="fas fa-palette"></i> <!-- Icon palet -->
                </div>
                <div class="stat-content">
                    <h3><?= $total_motif ?></h3> <!-- Tampilkan total motif -->
                    <p>Motif</p>
                </div>
            </div>
            
            <div class="stat-card"> <!-- Kartu statistik 4 -->
                <div class="stat-icon review">
                    <i class="fas fa-clock"></i> <!-- Icon jam -->
                </div>
                <div class="stat-content">
                    <h3><?= $produk_baru ?></h3> <!-- Tampilkan produk baru -->
                    <p>Produk Baru (7 hari)</p>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar"> <!-- Baris untuk aksi utama -->
            <div class="search-box"> <!-- Kotak pencarian -->
                <form action="" method="GET"> <!-- Form pencarian -->
                    <input type="text" name="cari" class="search-input" placeholder="Cari motif atau kode..." 
                           value="<?= htmlspecialchars($search) ?>"> <!-- Input pencarian -->
                    <button type="submit" class="search-btn"> <!-- Tombol submit -->
                        <i class="fa fa-search"></i> <!-- Icon cari -->
                    </button>
                </form>
            </div>
            
            <div class="btn-group"> <!-- Grup tombol -->
                <a href="tambah_produk.php" class="btn btn-success"> <!-- Tombol tambah produk -->
                    <i class="fas fa-plus"></i> Tambah Produk
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section"> <!-- Bagian filter -->
            <form method="GET" action="" class="filter-form"> <!-- Form filter -->
                <div class="filter-group"> <!-- Grup filter motif -->
                    <label class="filter-label">Model Kain</label> <!-- Label -->
                    <select name="motif" class="filter-select" onchange="this.form.submit()"> <!-- Dropdown motif -->
                        <option value="">Semua Model</option> <!-- Opsi default -->
                        <?php mysqli_data_seek($list_motif, 0); // Reset pointer hasil query motif ?>
                        <?php while($m = mysqli_fetch_assoc($list_motif)): ?> <!-- Loop semua motif -->
                            <option value="<?= $m['motif_kain'] ?>" 
                                    <?= $motif_filter == $m['motif_kain'] ? 'selected' : '' ?>> <!-- Cek selected -->
                                <?= $m['motif_kain'] ?> <!-- Tampilkan nama motif -->
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group"> <!-- Grup filter tipe -->
                    <label class="filter-label">Jenis Kain</label>
                    <select name="tipe" class="filter-select" onchange="this.form.submit()"> <!-- Dropdown tipe -->
                        <option value="">Semua Jenis</option>
                        <?php mysqli_data_seek($list_tipe, 0); // Reset pointer hasil query tipe ?>
                        <?php while($t = mysqli_fetch_assoc($list_tipe)): ?> <!-- Loop semua tipe -->
                            <option value="<?= $t['tipe_kain'] ?>" 
                                    <?= $tipe_filter == $t['tipe_kain'] ? 'selected' : '' ?>>
                                <?= $t['tipe_kain'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group"> <!-- Grup info filter -->
                    <div class="filter-buttons">
                        <span style="font-size: 14px; color: var(--text-light);">
                            Total: <b><?= $total_tampil ?></b> <!-- Tampilkan total hasil -->
                        </span>
                        <?php if ($search != '' || $motif_filter != '' || $tipe_filter != ''): ?> <!-- Jika ada filter aktif -->
                            <a href="admin_dashboard.php" class="btn btn-secondary btn-sm"> <!-- Tombol reset filter -->
                                <i class="fas fa-times"></i> Hapus Filter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <form action="" method="POST" id="formBulkDelete"> <!-- Form untuk hapus massal -->
            <div class="table-container"> <!-- Kontainer tabel -->
                <div class="table-header"> <!-- Header tabel -->
                    <div class="table-title">Daftar Produk</div> <!-- Judul tabel -->
                    <div class="total-count">Total: <?= $total_tampil ?> produk</div> <!-- Total produk -->
                </div>
                
                <div class="bulk-actions" id="bulkActions"> <!-- Aksi massal -->
                    <div class="bulk-info">
                        <span class="bulk-count" id="selectedCount">0 item terpilih</span> <!-- Counter item terpilih -->
                        <span style="color: var(--text-light); font-size: 14px;">Pilih aksi:</span> <!-- Instruksi -->
                    </div>
                    <button type="submit" name="hapus_masal" class="btn btn-danger btn-sm" id="btnBulkDelete" 
                            onclick="return confirm('Hapus semua produk yang dipilih?')"> <!-- Tombol hapus -->
                        <i class="fas fa-trash"></i> Hapus Terpilih
                    </button>
                </div>
                
                <div class="table-responsive"> <!-- Tabel responsif -->
                    <table class="data-table"> <!-- Tabel data -->
                        <thead> <!-- Header tabel -->
                            <tr>
                                <th class="checkbox-cell"> <!-- Kolom checkbox -->
                                    <input type="checkbox" id="checkAllHeader" class="check-all"> <!-- Checkbox pilih semua -->
                                </th>
                                <th>Kode INNA</th> <!-- Kolom kode -->
                                <th>Gambar</th> <!-- Kolom gambar -->
                                <th>Motif</th> <!-- Kolom motif -->
                                <th>Tipe Kain</th> <!-- Kolom tipe -->
                                <th>Warna</th> <!-- Kolom warna -->
                                <th>Screen</th> <!-- Kolom screen -->
                                <th>Aksi</th> <!-- Kolom aksi -->
                            </tr>
                        </thead>
                        <tbody> <!-- Body tabel -->
                            <?php if($total_tampil > 0): ?> <!-- Jika ada data -->
                                <?php mysqli_data_seek($result, 0); // Reset pointer hasil query ?>
                                <?php while($row = mysqli_fetch_assoc($result)) : ?> <!-- Loop setiap produk -->
                                <tr> <!-- Baris produk -->
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="pilih_hapus[]" 
                                               value="<?= $row['kode_inna']; ?>" class="item-checkbox"> <!-- Checkbox per item -->
                                    </td>
                                    <td style="font-weight: bold;"><?= htmlspecialchars($row['kode_inna']); ?></td> <!-- Kode produk -->
                                    <td>
                                        <?php
                                        $gambar_array = explode(",", $row['gambar']); // Pisahkan string gambar
                                        $thumbnail = trim($gambar_array[0]); // Ambil gambar pertama
                                        echo (!empty($thumbnail) && file_exists($thumbnail)) ? 
                                            '<img src="'.$thumbnail.'" class="img-thumb" alt="Gambar Produk">' : // Tampilkan gambar
                                            '<small style="color:var(--text-light);">No Image</small>'; // Tampilkan "No Image"
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['motif_kain']); ?></td> <!-- Nama motif -->
                                    <td><?= htmlspecialchars($row['tipe_kain']); ?></td> <!-- Tipe kain -->
                                    <td>
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;"> <!-- Kontainer warna -->
                                            <?php 
                                            if (!empty($row['warna_kain'])) { // Jika ada data warna
                                                $warna_data = explode(",", $row['warna_kain']); // Pisahkan warna
                                                foreach ($warna_data as $w) { // Loop setiap warna
                                                    $parts = explode(":", $w); // Pisahkan nama dan hex
                                                    if (isset($parts[1])) { // Jika format benar
                                                        $nama = htmlspecialchars(trim($parts[0])); // Nama warna
                                                        $hex = htmlspecialchars(trim($parts[1])); // Kode hex warna
                                                        echo '<div title="'.$nama.'" 
                                                              style="background-color: '.$hex.'; 
                                                              width: 18px; height: 18px; 
                                                              border: 1px solid #ddd; 
                                                              border-radius: 3px;"></div>'; // Kotak warna
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['screen']); ?></td> <!-- Screen -->
                                    <td>
                                        <div class="action-buttons"> <!-- Kontainer tombol aksi -->
                                            <a href="admin_detail_produk.php?id=<?= $row['kode_inna']; ?>" class="action-btn view">
                                                <i class="fas fa-eye"></i> Detail <!-- Tombol detail -->
                                            </a>
                                            <a href="edit_produk.php?id=<?= $row['kode_inna']; ?>" class="action-btn edit">
                                                <i class="fas fa-edit"></i> Edit <!-- Tombol edit -->
                                            </a>
                                            <a href="hapus_produk.php?id=<?= $row['kode_inna']; ?>" class="action-btn delete" 
                                               onclick="return confirm('Hapus produk ini?')"> <!-- Konfirmasi hapus -->
                                                <i class="fas fa-trash"></i> Hapus <!-- Tombol hapus -->
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?> <!-- Jika tidak ada data -->
                                <tr>
                                    <td colspan="8" class="empty-state"> <!-- Sel kosong -->
                                        <i class="fas fa-box-open" style="font-size: 60px;"></i> <!-- Icon kotak kosong -->
                                        <h3>Tidak Ada Produk</h3> <!-- Judul -->
                                        <p>Tidak ada data produk yang ditemukan.</p> <!-- Pesan -->
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</main>

<?php include 'template_end.php'; ?> <!-- Masukkan template akhir (footer, script) -->

<script>
    // Bulk Selection Functionality - JavaScript untuk seleksi massal
    const checkAllHeader = document.getElementById('checkAllHeader'); // Checkbox "pilih semua"
    const checkboxes = document.querySelectorAll('.item-checkbox'); // Semua checkbox item
    const bulkActions = document.getElementById('bulkActions'); // Kontainer aksi massal
    const selectedCount = document.getElementById('selectedCount'); // Element counter
    const btnBulkDelete = document.getElementById('btnBulkDelete'); // Tombol hapus massal

    function updateSelection() { // Fungsi update status seleksi
        const selected = Array.from(checkboxes).filter(cb => cb.checked); // Array checkbox tercentang
        const count = selected.length; // Hitung jumlah terpilih
        
        selectedCount.textContent = count + ' item terpilih'; // Update teks counter
        
        if (count > 0) { // Jika ada yang terpilih
            bulkActions.classList.add('active'); // Tampilkan aksi massal
            btnBulkDelete.style.display = 'inline-flex'; // Tampilkan tombol hapus
            
            checkboxes.forEach(cb => { // Loop semua checkbox
                const row = cb.closest('tr'); // Dapatkan baris tabel
                if (cb.checked) {
                    row.style.backgroundColor = 'var(--gray-50)'; // Highlight baris terpilih
                } else {
                    row.style.backgroundColor = ''; // Reset warna baris
                }
            });
        } else { // Jika tidak ada yang terpilih
            bulkActions.classList.remove('active'); // Sembunyikan aksi massal
            btnBulkDelete.style.display = 'none'; // Sembunyikan tombol hapus
            
            checkboxes.forEach(cb => {
                cb.closest('tr').style.backgroundColor = ''; // Reset semua baris
            });
        }
        
        const allChecked = count === checkboxes.length && checkboxes.length > 0; // Cek jika semua terpilih
        checkAllHeader.checked = allChecked; // Update checkbox "pilih semua"
    }
    
    checkAllHeader.addEventListener('change', function() { // Event listener checkbox "pilih semua"
        checkboxes.forEach(cb => {
            cb.checked = this.checked; // Set semua checkbox sesuai dengan "pilih semua"
        });
        updateSelection(); // Update status
    });
    
    checkboxes.forEach(cb => { // Event listener untuk setiap checkbox item
        cb.addEventListener('change', updateSelection); // Update saat checkbox diubah
    });
    
    // Initialize - Jalankan saat halaman siap
    document.addEventListener('DOMContentLoaded', updateSelection);
</script>