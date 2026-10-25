<?php
// Mulai session untuk mengakses data login
session_start();

// Hubungkan ke database
include 'config.php';

// Cek apakah user sudah login dan role-nya admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika bukan admin, redirect ke halaman login
    header("Location: ../login.php");
    exit;
}

// AMBIL NAMA DARI SESSION
$username = $_SESSION['username'];

// Cek apakah parameter ID produk ada di URL
if (!isset($_GET['id'])) {
    // Jika tidak ada ID, kembali ke dashboard admin
    header("Location: admin_dashboard.php");
    exit;
}

// Ambil dan amankan ID produk dari URL
$id = mysqli_real_escape_string($conn, $_GET['id']);

// Query untuk mengambil data produk berdasarkan ID
$query = "SELECT * FROM produk WHERE kode_inna = '$id'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

// Cek apakah produk ditemukan
if (!$row) {
    // Jika produk tidak ditemukan, tampilkan alert dan redirect
    echo "<script>alert('Produk tidak ditemukan!'); window.location='admin_dashboard.php';</script>";
    exit;
}

// --- LOGIKA PENGOLAHAN DATA ---

// Ambil gambar unik dari database
// 1. Pisahkan string gambar berdasarkan koma
$raw_images = explode(",", $row['gambar']);
// 2. Bersihkan spasi, hapus elemen kosong, dan ambil nilai unik
$images = array_unique(array_filter(array_map('trim', $raw_images))); 
// 3. Reset index array
$images = array_values($images); 

// Ambil dan proses data warna
$warna_data = []; // Array untuk menyimpan data warna
$color_image_map = []; // Map warna ke index gambar (untuk JavaScript)

// Cek apakah ada data warna_kain
if (!empty($row['warna_kain'])) {
    // Pisahkan data warna berdasarkan koma
    $warna_array = explode(",", $row['warna_kain']);
    
    // Proses setiap data warna
    foreach ($warna_array as $index => $w) {
        // Pisahkan data warna menjadi bagian-bagian (nama:hex:index_gambar)
        $parts = explode(":", $w);
        
        // Pastikan format data benar (minimal ada nama dan hex)
        if (count($parts) >= 2) {
            $nama = trim($parts[0]); // Nama warna
            $hex = trim($parts[1]);  // Kode hex warna
            // Ambil index gambar (jika ada), default 0
            $gambar_index = isset($parts[2]) ? (int)trim($parts[2]) : 0;
            
            // Validasi index gambar agar tidak out of bounds
            if ($gambar_index < 0 || $gambar_index >= count($images)) {
                $gambar_index = 0;
            }
            
            // Simpan data warna ke array
            $warna_data[] = [
                'nama' => htmlspecialchars($nama),          // Nama warna (aman dari XSS)
                'hex' => htmlspecialchars($hex),            // Kode hex (aman dari XSS)
                'gambar_index' => $gambar_index            // Index gambar terkait
            ];
            
            // Simpan mapping untuk JavaScript
            $color_image_map[] = $gambar_index;
        }
    }
}

// Filter warna unik (tidak ada duplikasi)
$unique_warna_data = [];
$seen_colors = []; // Untuk melacak warna yang sudah diproses

foreach ($warna_data as $warna) {
    // Buat key unik berdasarkan hex dan nama
    $color_key = strtolower($warna['hex'] . '_' . $warna['nama']);
    
    // Cek apakah warna sudah ada
    if (!in_array($color_key, $seen_colors)) {
        $unique_warna_data[] = $warna;      // Tambahkan ke array unik
        $seen_colors[] = $color_key;        // Tandai sebagai sudah diproses
    }
}

// Tentukan gambar utama (default ke gambar pertama atau placeholder)
$first_img = !empty($images) ? $images[0] : 'https://via.placeholder.com/400?text=No+Image';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS untuk styling halaman */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .back-btn { text-decoration: none; color: #666; font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .title { margin:0; color: #800000; }
        .main-image { width: 100%; height: 400px; overflow: hidden; border-radius: 8px; border: 1px solid #ddd; background: #f9f9f9; }
        .thumb-container { display: flex; gap: 10px; overflow-x: auto; padding: 10px 0; margin-top: 10px; }
        .thumb-img { width: 70px; height: 70px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid transparent; opacity: 0.8; transition: all 0.3s; }
        .thumb-img:hover { opacity: 1; transform: scale(1.05); }
        .thumb-img.active { border-color: #800000; opacity: 1; }
        .color-chip { display: inline-flex; align-items: center; gap: 6px; background: #f8f9fa; padding: 4px 10px; border-radius: 20px; margin: 2px; cursor: pointer; transition: all 0.2s; border: 1px solid #e9ecef; }
        .color-chip:hover { background: #e9ecef; transform: translateY(-2px); }
        .color-chip.active { background: #800000; color: white; border-color: #800000; }
        .color-dot { width: 15px; height: 15px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.1); }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .info-table td { padding: 12px 5px; border-bottom: 1px solid #eee; }
        .info-table .label { font-weight: bold; color: #888; width: 120px; }
        .action-buttons { display: flex; gap: 15px; margin-top: 30px; }
        .btn { padding: 12px; border-radius: 6px; text-decoration: none; font-weight: bold; text-align: center; flex: 1; }
        .btn-edit { background-color: #ffc107; color: #000; }
        .btn-delete { background-color: #dc3545; color: #fff; }
        .color-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; }
        .image-count { background: #e9ecef; padding: 2px 10px; border-radius: 12px; font-size: 12px; color: #666; margin-left: 10px; }
        .info-badge { background: #e3f2fd; color: #1565c0; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin: 10px 0; display: inline-flex; align-items: center; gap: 5px; }
        .deskripsi-box { margin-top: 25px; padding: 20px; background-color: #f9f9f9; border-radius: 8px; border-left: 4px solid #800000; }
        .deskripsi-content { line-height: 1.7; color: #444; font-size: 14px; white-space: pre-line; }
        .created-info { margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
        .empty-deskripsi { color: #999; font-style: italic; text-align: center; padding: 20px; background: #fff; border-radius: 6px; }
    </style>
</head>
<body>

<div class="container">
    <!-- Header dengan tombol kembali dan judul -->
    <div class="header">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
        <h2 class="title">Detail Produk</h2>
    </div>

    <!-- Konten utama dengan layout 2 kolom -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Kolom kiri: Gambar produk -->
        <div>
            <!-- Gambar utama -->
            <div class="main-image">
                <img src="<?= $first_img ?>" id="mainView" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            
            <!-- Thumbnail gambar -->
            <div class="thumb-container">
                <?php if(count($images) > 0): ?>
                    <?php foreach($images as $index => $img): ?>
                        <img src="<?= $img ?>" 
                             onclick="changeImage('<?= $img ?>', <?= $index ?>)" 
                             class="thumb-img <?= ($index === 0) ? 'active' : '' ?>"
                             data-index="<?= $index ?>">
                    <?php endforeach; ?>
                <?php else: ?>
                    <small style="color: #999;">Tidak ada gambar tersedia.</small>
                <?php endif; ?>
            </div>
            
            <!-- Info jumlah gambar -->
            <div class="info-badge">
                <i class="fas fa-images"></i> 
                <span id="imageInfo"><?= count($images) ?> gambar unik tersedia</span>
            </div>
        </div>

        <!-- Kolom kanan: Informasi produk -->
        <div>
            <!-- Tabel informasi produk -->
            <table class="info-table">
                <tr>
                    <td class="label">Kode INNA</td>
                    <td>: <strong><?= htmlspecialchars($row['kode_inna']); ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Motif Kain</td>
                    <td>: <?= htmlspecialchars($row['motif_kain']); ?></td>
                </tr>
                <tr>
                    <td class="label">Jenis Kain</td>
                    <td>: <?= htmlspecialchars($row['tipe_kain']); ?></td>
                </tr>
                <tr>
                    <td class="label">Varian Warna</td>
                    <td>
                        <!-- Pilihan warna -->
                        <div class="color-grid" id="colorSelector">
                            <?php if(!empty($unique_warna_data)): ?>
                                <?php foreach($unique_warna_data as $index => $warna): ?>
                                    <div class="color-chip <?= ($index === 0) ? 'active' : '' ?>" 
                                         onclick="selectColor(<?= $index ?>, <?= $warna['gambar_index'] ?>)"
                                         data-index="<?= $index ?>"
                                         data-image-index="<?= $warna['gambar_index'] ?>"
                                         title="<?= $warna['nama'] ?>">
                                        <div class="color-dot" style="background-color: <?= $warna['hex'] ?>;"></div>
                                        <span style="font-size: 12px;"><?= $warna['nama'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small style="color: #999;">Tidak ada varian warna.</small>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 5px; font-size: 12px; color: #666;">
                            <i class="fas fa-mouse-pointer"></i> Klik warna untuk melihat gambar terkait
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="label">Screen</td>
                    <td>: <?= htmlspecialchars($row['screen']); ?></td>
                </tr>
                <tr>
                    <td class="label">Statistik</td>
                    <td>
                        : <span class="image-count"><?= count($images) ?> gambar</span>
                        <span class="image-count"><?= count($unique_warna_data) ?> warna unik</span>
                    </td>
                </tr>
            </table>
            
            <!-- Deskripsi produk -->
            <div style="margin-top: 25px; padding: 20px; background-color: #f9f9f9; border-radius: 8px; border-left: 4px solid #800000;">
                <h4 style="color: #800000; margin-top: 0; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-align-left"></i> Deskripsi Produk
                </h4>
                <div style="line-height: 1.7; color: #444; font-size: 14px; white-space: pre-line;">
                    <?php if(!empty($row['deskripsi'])): ?>
                        <?= htmlspecialchars($row['deskripsi']) ?>
                    <?php else: ?>
                        <div style="color: #999; font-style: italic; text-align: center; padding: 20px; background: #fff; border-radius: 6px;">
                            <i class="fas fa-info-circle"></i> Belum ada deskripsi untuk produk ini.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Info created_at -->
                <?php if(!empty($row['created_at'])): ?>
                    <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; font-size: 12px; color: #666;">
                        <i class="far fa-clock"></i> Ditambahkan pada: 
                        <?= date('d F Y H:i', strtotime($row['created_at'])) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tombol aksi -->
            <div class="action-buttons">
                <a href="edit_produk.php?id=<?= $row['kode_inna']; ?>" class="btn btn-edit">
                    <i class="fas fa-edit"></i> Edit Data
                </a>
                <a href="hapus_produk.php?id=<?= $row['kode_inna']; ?>" class="btn btn-delete" onclick="return confirm('Hapus produk ini secara permanen?')">
                    <i class="fas fa-trash"></i> Hapus
                </a>
            </div>

        </div>
    </div>
</div>

<script>
// Data untuk JavaScript (dikirim dari PHP)
const images = <?= json_encode($images) ?>;              // Array gambar produk
const colorImageMap = <?= json_encode($color_image_map) ?>; // Mapping warna ke gambar
let activeColorIndex = 0; // Index warna yang aktif

// Fungsi untuk mengganti gambar utama
function changeImage(src, index) {
    // Ubah sumber gambar utama
    document.getElementById('mainView').src = src;
    
    // Update thumbnail aktif
    const thumbs = document.querySelectorAll('.thumb-img');
    thumbs.forEach(t => t.classList.remove('active'));
    document.querySelector(`.thumb-img[data-index="${index}"]`).classList.add('active');
    
    // Update info gambar
    updateImageInfo(index);
}

// Fungsi untuk memilih warna
function selectColor(colorIndex, imageIndex) {
    activeColorIndex = colorIndex;
    
    // Update tampilan warna aktif
    const colorChips = document.querySelectorAll('.color-chip');
    colorChips.forEach(c => c.classList.remove('active'));
    document.querySelector(`.color-chip[data-index="${colorIndex}"]`).classList.add('active');
    
    // Cari dan tampilkan gambar terkait dengan warna
    if (images[imageIndex]) {
        changeImage(images[imageIndex], imageIndex);
    } else if (images.length > 0) {
        // Fallback ke gambar pertama jika index tidak valid
        changeImage(images[0], 0);
    }
}

// Fungsi untuk update informasi gambar
function updateImageInfo(imageIndex) {
    const imageInfo = document.getElementById('imageInfo');
    if (imageIndex === 0) {
        imageInfo.textContent = "Gambar utama (cover)";
    } else {
        imageInfo.textContent = `Gambar detail #${imageIndex}`;
    }
}

// Inisialisasi saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    if (images.length > 0) {
        updateImageInfo(0); // Tampilkan info untuk gambar pertama
    }
    
    // Jika ada warna, highlight warna pertama
    const firstColor = document.querySelector('.color-chip');
    if (firstColor) {
        firstColor.classList.add('active');
    }
});
</script>
</body>
</html>