<?php
// Memulai session PHP untuk menyimpan data user antar halaman
session_start();
// Menyertakan file konfigurasi database
include 'config.php';

// 1. PROTEKSI AKSES: Hanya Admin yang boleh mengakses
// Mengecek apakah user sudah login dan memiliki role 'admin'
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika bukan admin, redirect ke halaman login
    header("Location: ../login.php");
    exit; // Hentikan eksekusi script
}

// Mengambil username dari session untuk ditampilkan
$username = $_SESSION['username'];

// Deklarasi array untuk menyimpan pesan error
$errors = [];

// 2. LOGIKA SIMPAN DATA: Jika form disubmit dengan tombol 'simpan'
if (isset($_POST['simpan'])) {
    // Mengambil dan membersihkan data dari form input
    // mysqli_real_escape_string() mencegah SQL injection
    // trim() menghapus spasi di awal dan akhir string
    $kode   = trim(mysqli_real_escape_string($conn, $_POST['kode_inna']));
    $motif  = trim(mysqli_real_escape_string($conn, $_POST['motif_kain']));
    $tipe   = trim(mysqli_real_escape_string($conn, $_POST['tipe_kain']));
    $screen = trim(mysqli_real_escape_string($conn, $_POST['screen']));
    // ?? '' adalah null coalescing operator, memberikan nilai default jika tidak ada
    $deskripsi = trim(mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? ''));

    // Mengambil data array warna dari form
    // ?? [] memberikan array kosong jika tidak ada data
    $warna_names = $_POST['warna_list'] ?? []; // Nama-nama warna
    $warna_hexs  = $_POST['warna_hex'] ?? [];  // Kode HEX warna
    $target_dir  = "../uploads/"; // Direktori untuk menyimpan file upload

    // --- VALIDASI DASAR DATA INPUT ---
    // Validasi 1: Cek apakah field wajib sudah diisi
    if (empty($kode)) $errors[] = "Kode INNA wajib diisi.";
    if (empty($motif)) $errors[] = "Motif Kain wajib diisi.";
    if (empty($tipe)) $errors[] = "Tipe Kain wajib diisi.";
    // Validasi screen harus angka
    if ($screen === "" || !is_numeric($screen)) $errors[] = "Data Screen harus berupa angka.";
    
    // Validasi file upload utama
    // $_FILES['gambar_utama']['error'] == 0 berarti upload sukses
    if (!isset($_FILES['gambar_utama']) || $_FILES['gambar_utama']['error'] != 0) {
        $errors[] = "Foto Produk Utama (Sampul) wajib diunggah.";
    }

    // --- VALIDASI: CEK APAKAH ADA VARIASI WARNA ---
    // Produk harus memiliki minimal 1 variasi warna
    if (empty($warna_names)) {
        $errors[] = "Variasi warna wajib diisi. Minimal tambahkan satu variasi warna.";
    }

    // --- VALIDASI TAMBAHAN: CEK DUPLIKASI KODE HEX WARNA ---
    // Mencegah warna dengan kode HEX yang sama dimasukkan berkali-kali
    if (!empty($warna_names)) {
        $seen_colors = []; // Array untuk melacak warna yang sudah dilihat
        foreach ($warna_hexs as $key => $hex) {
            $color_key = strtolower(trim($hex)); // Normalisasi: lowercase dan trim
            if (in_array($color_key, $seen_colors)) {
                $errors[] = "Warna dengan kode HEX '" . $color_key . "' sudah ditambahkan. Hapus duplikat.";
                break; // Hentikan loop setelah menemukan duplikat
            }
            $seen_colors[] = $color_key; // Tambahkan ke array yang sudah dilihat
        }
    }

    // --- VALIDASI TAMBAHAN: CEK DUPLIKASI NAMA WARNA ---
    // Mencegah nama warna yang sama digunakan berkali-kali
    if (!empty($warna_names)) {
        $seen_names = []; // Array untuk melacak nama yang sudah dilihat
        foreach ($warna_names as $key => $name) {
            $name_lower = strtolower(trim($name)); // Normalisasi: lowercase dan trim
            if (in_array($name_lower, $seen_names)) {
                $errors[] = "Nama warna '" . htmlspecialchars($name) . "' sudah digunakan. Gunakan nama lain.";
                break; // Hentikan loop setelah menemukan duplikat
            }
            $seen_names[] = $name_lower; // Tambahkan ke array yang sudah dilihat
        }
    }

    // --- JIKA TIDAK ADA ERROR, PROSES PENYIMPANAN ---
    if (empty($errors)) {
        try {
            // --- VALIDASI DUPLIKASI KODE PRODUK ---
            // Cek apakah kode INNA sudah ada di database
            $check_query = "SELECT kode_inna FROM produk WHERE kode_inna = '$kode'";
            $check_result = mysqli_query($conn, $check_query);
            
            // Jika ada hasil query (jumlah baris > 0), berarti kode sudah terdaftar
            if (mysqli_num_rows($check_result) > 0) {
                throw new Exception("Kode INNA '$kode' sudah terdaftar di database. Gunakan kode lain.");
            }

            // --- PROSES UPLOAD FILE GAMBAR ---
            // Buat direktori upload jika belum ada
            // 0777 = permission read, write, execute untuk semua user
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            // Deklarasi array untuk menyimpan data
            $final_images = []; // Array untuk path gambar yang unik
            $warna_combined = []; // Array untuk warna dengan referensi gambar
            $image_hash_map = []; // Map untuk melacak duplikasi gambar menggunakan hash
            
            // Definisikan tipe file yang diizinkan untuk upload
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            // A. FOTO UTAMA (COVER) - Selalu disimpan di index 0
            // pathinfo() mengambil informasi file, PATHINFO_EXTENSION mengambil ekstensi
            $ext_main = strtolower(pathinfo($_FILES['gambar_utama']['name'], PATHINFO_EXTENSION));
            // Buat nama file unik dengan timestamp dan kode produk
            $file_main = time() . "_MAIN_" . $kode . "." . $ext_main;
            $path_main = $target_dir . $file_main;
            
            // move_uploaded_file() memindahkan file dari temporary ke lokasi permanen
            if (move_uploaded_file($_FILES['gambar_utama']['tmp_name'], $path_main)) {
                $final_images[0] = $path_main; // Index 0 untuk gambar cover
                // md5_file() menghasilkan hash unik dari file untuk deteksi duplikat
                $image_hash_map[$path_main] = md5_file($path_main);
            }

            // B. VARIAN WARNA (WAJIB - minimal 1 warna)
            if (!empty($warna_names)) {
                $warna_index = 0; // Counter untuk varian warna
                $uploaded_warna_images = []; // Array untuk validasi duplikasi gambar varian
                
                // Loop melalui setiap varian warna
                foreach ($warna_names as $key => $nama) {
                    // Bersihkan data input
                    $current_hex = trim(mysqli_real_escape_string($conn, $warna_hexs[$key]));
                    $current_name = trim(mysqli_real_escape_string($conn, $nama));
                    
                    $gambar_index = 0; // Default ke gambar cover (index 0) jika tidak ada gambar khusus
                    
                    // Cek apakah ada file gambar untuk varian ini
                    // $_FILES['gambar_warna']['error'][$key] == 0 berarti upload sukses
                    if (isset($_FILES['gambar_warna']['name'][$key]) && $_FILES['gambar_warna']['error'][$key] == 0) {
                        $ext_var = strtolower(pathinfo($_FILES['gambar_warna']['name'][$key], PATHINFO_EXTENSION));
                        $file_var = time() . "_VAR_" . $warna_index . "_" . $kode . "." . $ext_var;
                        $path_var = $target_dir . $file_var;
                        
                        // Validasi ukuran file (maksimal 5MB)
                        if ($_FILES['gambar_warna']['size'][$key] > 5 * 1024 * 1024) {
                            throw new Exception("Gambar untuk warna '$current_name' terlalu besar. Maksimal 5MB.");
                        }
                        
                        // Validasi tipe file
                        if (!in_array($ext_var, $allowed_types)) {
                            throw new Exception("Format gambar untuk warna '$current_name' tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.");
                        }
                        
                        // Pindahkan file upload
                        if (move_uploaded_file($_FILES['gambar_warna']['tmp_name'][$key], $path_var)) {
                            $current_hash = md5_file($path_var);
                            $duplicate_found = false;
                            $duplicate_path = '';
                            
                            // Cek duplikasi dengan gambar yang sudah ada
                            foreach ($image_hash_map as $existing_path => $existing_hash) {
                                if ($current_hash === $existing_hash) {
                                    $duplicate_found = true;
                                    $duplicate_path = $existing_path;
                                    break;
                                }
                            }
                            
                            if ($duplicate_found) {
                                // Hapus file duplikat yang baru saja diupload
                                unlink($path_var);
                                // Cari index gambar yang sudah ada di array
                                $gambar_index = array_search($duplicate_path, $final_images);
                            } else {
                                // Cek duplikasi gambar dengan gambar varian lain
                                foreach ($uploaded_warna_images as $uploaded_data) {
                                    if ($current_hash === $uploaded_data['hash']) {
                                        throw new Exception("Gambar untuk warna '$current_name' sama dengan gambar untuk warna '" . $uploaded_data['nama'] . "'.");
                                    }
                                }
                                
                                // Tambahkan gambar baru ke array
                                $next_index = count($final_images);
                                $final_images[$next_index] = $path_var;
                                $image_hash_map[$path_var] = $current_hash;
                                $gambar_index = $next_index;
                                
                                // Simpan info gambar varian untuk validasi duplikasi
                                $uploaded_warna_images[] = [
                                    'nama' => $current_name,
                                    'hash' => $current_hash,
                                    'path' => $path_var
                                ];
                            }
                        } else {
                            throw new Exception("Gagal mengupload gambar untuk warna '$current_name'.");
                        }
                    }
                    
                    // Format data warna: nama:hex:index_gambar
                    $warna_combined[] = $current_name . ":" . $current_hex . ":" . $gambar_index;
                    $warna_index++;
                }
            }

            // --- SIMPAN KE DATABASE ---
            // Gabungkan array menjadi string untuk disimpan di database
            $all_images_str = implode(",", $final_images);
            $all_colors_str = implode(",", $warna_combined);
            
            // Query INSERT untuk menyimpan data produk
            $query = "INSERT INTO produk (kode_inna, gambar, motif_kain, tipe_kain, warna_kain, screen, deskripsi) 
                      VALUES ('$kode', '$all_images_str', '$motif', '$tipe', '$all_colors_str', '$screen', '$deskripsi')";
            
            // Eksekusi query
            if (mysqli_query($conn, $query)) {
                // Tampilkan alert JavaScript dan redirect
                echo "<script>
                    alert('Produk berhasil ditambahkan!');
                    window.location='admin_dashboard.php';
                </script>";
                exit; // Hentikan eksekusi script
            } else {
                throw new Exception("Gagal menyimpan ke database: " . mysqli_error($conn));
            }

        } catch (mysqli_sql_exception $e) {
            // Tangani error SQL khusus
            if ($e->getCode() == 1062) {
                $errors[] = "Gagal simpan: Kode INNA '$kode' sudah digunakan.";
            } else {
                $errors[] = "Kesalahan Database: " . $e->getMessage();
            }
        } catch (Exception $e) {
            // Tangani error umum
            $errors[] = $e->getMessage();
        }
    }
}
?>

<!-- ====================================================== -->
<!-- BAGIAN HTML: TAMPILAN FORM TAMBAH PRODUK -->
<!-- ====================================================== -->

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk Kain - PT INTIM</title>
    <!-- Load Font Awesome untuk icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* GLOBAL STYLES */
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #f8f9fa; 
            padding: 20px; 
        }
        .container { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            max-width: 800px; 
            margin: auto; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        .header { 
            border-bottom: 2px solid #800000; 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
            color: #800000; 
        }
        
        /* STYLE UNTUK PESAN ERROR */
        .error-box { 
            background: #fee2e2; 
            border: 1px solid #ef4444; 
            color: #b91c1c; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
        }
        
        /* STYLE UNTUK PESAN WARNING */
        .warning-box { 
            background: #fff3cd; 
            border: 1px solid #ffc107; 
            color: #856404; 
            padding: 10px; 
            border-radius: 6px; 
            margin-bottom: 15px; 
            font-size: 14px; 
        }
        
        /* STYLE UNTUK PESAN SUKSES */
        .success-box { 
            background: #d1e7dd; 
            border: 1px solid #0f5132; 
            color: #0f5132; 
            padding: 10px; 
            border-radius: 6px; 
            margin-bottom: 15px; 
            font-size: 14px; 
        }
        
        /* STYLE UNTUK FORM GROUP */
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            font-weight: bold; 
            display: block; 
            margin-bottom: 5px; 
        }
        input[type="text"], input[type="number"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-sizing: border-box; 
        }
        
        /* STYLE UNTUK BAGIAN FORM (SECTION BOX) */
        .section-box { 
            background: #f9f9f9; 
            padding: 15px; 
            border-radius: 8px; 
            border: 1px solid #eee; 
            margin-bottom: 20px; 
            position: relative;
        }
        .required-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        
        /* STYLE UNTUK TOMBOL SIMPAN */
        .btn-save { 
            width: 100%; 
            padding: 15px; 
            background: #800000; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            transition: 0.3s; 
            margin-top: 20px;
        }
        .btn-save:hover { 
            background: #a00000; 
        }
        .btn-save:disabled { 
            background: #cccccc; 
            cursor: not-allowed; 
        }
        
        /* STYLE UNTUK CIRCLE COLOR (BULATAN WARNA) */
        .color-circle { 
            width: 25px; 
            height: 25px; 
            border-radius: 50%; 
            cursor: pointer; 
            border: 1px solid #ddd; 
            display: inline-block;
            transition: transform 0.2s;
        }
        .color-circle:hover { 
            transform: scale(1.1); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        /* STYLE UNTUK ITEM VARIAN WARNA */
        .varian-item { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            background: white; 
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 8px; 
            border: 1px solid #ddd; 
            transition: all 0.3s;
        }
        .varian-item:hover { 
            border-color: #800000; 
            background: #fef5f5;
        }
        .varian-item.duplicate-warning { 
            border-color: #ffc107; 
            background: #fff3cd;
        }
        .varian-item.duplicate-error { 
            border-color: #dc3545; 
            background: #f8d7da;
        }
        
        /* STYLE UNTUK INFO BADGE */
        .info-badge { 
            background: #e3f2fd; 
            color: #1565c0; 
            padding: 10px; 
            border-radius: 6px; 
            font-size: 13px; 
            margin: 10px 0; 
            line-height: 1.5;
        }
        .warning-badge {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            margin: 10px 0;
            line-height: 1.5;
            border-left: 4px solid #ffc107;
        }
        
        /* STYLE UNTUK TOMBOL HAPUS */
        .delete-btn { 
            border: none; 
            background: none; 
            color: #dc3545; 
            cursor: pointer; 
            padding: 5px;
            border-radius: 4px;
        }
        .delete-btn:hover { 
            background: #f8d7da; 
        }
        
        /* STYLE UNTUK PESAN "TIDAK ADA VARIAN" */
        .no-varian-message {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
            border: 1px dashed #dee2e6;
        }
        
        /* STYLE UNTUK INPUT GROUP WARNA */
        .color-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        /* STYLE UNTUK ASTERISK WAJIB DIISI */
        .required-asterisk {
            color: #dc3545;
        }
        
        /* STYLE UNTUK PESAN MINIMAL WARNA */
        .minimal-message {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 8px 12px;
            border-radius: 6px;
            margin: 10px 0;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* STYLE UNTUK COUNTER WARNA */
        .color-counter {
            background: #800000;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
        }
    </style>
</head>
<body>

<!-- CONTAINER UTAMA -->
<div class="container">
    <!-- HEADER HALAMAN -->
    <div class="header">
        <h2><i class="fas fa-plus-circle"></i> Tambah Produk Kain</h2>
    </div>

    <!-- TAMPILKAN ERROR JIKA ADA -->
    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <strong><i class="fas fa-exclamation-triangle"></i> Terjadi Kesalahan:</strong>
            <ul style="margin-top: 5px; padding-left: 20px;">
                <?php foreach ($errors as $error): ?> 
                    <li><?= htmlspecialchars($error) ?></li> 
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- FORM TAMBAH PRODUK -->
    <form method="POST" enctype="multipart/form-data" id="productForm" onsubmit="return validateForm()">
        <!-- INPUT KODE INNA -->
        <div class="form-group">
            <label for="kode_inna">
                Kode INNA <span class="required-asterisk">*</span>
            </label>
            <input type="text" id="kode_inna" name="kode_inna" 
                   value="<?= htmlspecialchars($_POST['kode_inna'] ?? '') ?>" 
                   placeholder="Misal: 12Vbv" required>
        </div>

        <!-- BAGIAN FOTO SAMPUL UTAMA -->
        <div class="section-box" style="border-left: 5px solid #800000;">
            <label style="color: #800000;">
                <i class="fas fa-image"></i> 1. Foto Sampul Utama <span class="required-asterisk">*</span>
            </label>
            <div class="info-badge">
                <i class="fas fa-info-circle"></i> 
                Gambar ini akan menjadi gambar utama (cover) produk. Format yang didukung: JPG, PNG, GIF, WEBP. Maksimal 5MB.
            </div>
            <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                <!-- PREVIEW GAMBAR UTAMA -->
                <img src="https://via.placeholder.com/100?text=SAMPUL" id="prev_utama" 
                     style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:1px solid #ddd;">
                <div>
                    <!-- INPUT FILE GAMBAR UTAMA -->
                    <input type="file" name="gambar_utama" id="gambar_utama" required 
                           accept="image/jpeg,image/png,image/gif,image/webp" 
                           onchange="previewImg(this, 'prev_utama'); validateFileSize(this, 5)">
                    <small id="fileSizeError" style="color: red; display: none;"></small>
                </div>
            </div>
        </div>

        <!-- BAGIAN VARIASI WARNA (WAJIB) -->
        <div class="section-box">
            <div class="required-badge">WAJIB</div>
            <label>
                <i class="fas fa-palette"></i> 2. Variasi Warna <span class="required-asterisk">*</span>
                <!-- COUNTER JUMLAH WARNA -->
                <span id="colorCounter" class="color-counter">0 warna</span>
            </label>
            
            <!-- PERINGATAN WAJIB DIISI -->
            <div class="warning-badge">
                <i class="fas fa-exclamation-circle"></i> 
                <strong>WAJIB DIISI:</strong> Setiap produk harus memiliki minimal 1 variasi warna. 
                Jika produk hanya memiliki 1 warna, tambahkan warna tersebut.
            </div>
            
            <!-- INFO JUMLAH WARNA SAAT INI -->
            <div class="minimal-message">
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                Minimal: <strong>1 warna</strong> | Saat ini: <strong><span id="currentColorCount">0</span> warna</strong>
            </div>
            
            <!-- PESAN WARNING DUPLIKASI (DISEMBUNYIKAN AWALNYA) -->
            <div class="warning-box" id="duplicateWarning" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="warningMessage"></span>
            </div>
            
            <!-- AREA UNTUK MEMILIH WARNA -->
            <div style="display:flex; align-items:center; gap:15px; margin: 10px 0; background: #eee; padding: 12px; border-radius: 8px;">
                <!-- PRESET WARNA STANDAR -->
                <div>
                    <small style="display:block; margin-bottom:5px; font-weight:bold;">Preset Warna:</small>
                    <div style="display:flex; gap:8px;">
                        <?php 
                        // Array preset warna standar
                        $presets = [
                            '#ffffff' => 'Putih', 
                            '#000000' => 'Hitam', 
                            '#ff0000' => 'Merah', 
                            '#0000ff' => 'Biru', 
                            '#008000' => 'Hijau',
                            '#ffff00' => 'Kuning',
                            '#800080' => 'Ungu',
                            '#ffa500' => 'Orange',
                            '#a52a2a' => 'Coklat',
                            '#808080' => 'Abu-abu'
                        ];
                        foreach($presets as $hex => $nama): ?>
                            <!-- BULATAN WARNA YANG BISA DIKLIK -->
                            <div class="color-circle" style="background:<?= $hex ?>" 
                                 title="<?= $nama ?>" 
                                 onclick="addVarian('<?= $hex ?>', '<?= $nama ?>')"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- INPUT WARNA KUSTOM -->
                <div style="border-left: 2px solid #ccc; padding-left: 15px; flex: 1;">
                    <small style="display:block; margin-bottom:5px; font-weight:bold;">Warna Kustom:</small>
                    <div class="color-input-group">
                        <!-- INPUT COLOR HTML5 -->
                        <input type="color" id="customColor" value="#800000" 
                               style="width:35px; height:35px; border:none; cursor:pointer; background:none;">
                        <!-- INPUT NAMA WARNA KUSTOM -->
                        <input type="text" id="customColorName" placeholder="Nama warna (misal: Merah Maroon)" 
                               style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        <!-- TOMBOL TAMBAH WARNA KUSTOM -->
                        <button type="button" onclick="addCustomVarian()" 
                                style="padding: 6px 12px; cursor:pointer; background:#800000; color:white; border:none; border-radius:4px; font-size:12px;">
                            <i class="fas fa-plus"></i> Tambah Warna
                        </button>
                    </div>
                </div>
            </div>

            <!-- INFORMASI TAMBAHAN TENTANG VARIAN WARNA -->
            <div class="info-badge">
                <i class="fas fa-info-circle"></i> 
                • Setiap warna akan ditampilkan sebagai tombol pilih warna di halaman detail<br>
                • Foto untuk varian warna bersifat <strong>opsional</strong> (bisa diupload nanti)<br>
                • Sistem akan mendeteksi dan mencegah warna atau gambar duplikat<br>
                • Format gambar: JPG, PNG, GIF, WEBP. Maksimal 5MB per gambar.
            </div>

            <!-- DAFTAR VARIAN WARNA YANG SUDAH DITAMBAHKAN -->
            <div id="varian-list">
                <?php 
                // Tampilkan varian warna yang sudah ada dari form sebelumnya (jika ada)
                if (isset($_POST['warna_list']) && !empty($_POST['warna_list'])): ?>
                    <?php foreach($_POST['warna_list'] as $i => $nama): ?>
                        <div class="varian-item">
                            <!-- PREVIEW WARNA -->
                            <div style="width:20px; height:20px; background:<?= $_POST['warna_hex'][$i] ?>; border-radius:4px; border:1px solid #ccc;"></div>
                            <div style="flex:1">
                                <!-- INPUT NAMA WARNA -->
                                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                                    <span style="font-size: 12px; color: #666;">Warna #<?= $i+1 ?></span>
                                    <input type="text" name="warna_list[<?= $i ?>]" value="<?= htmlspecialchars($nama) ?>" 
                                           style="width:150px; padding:5px; font-size:12px; border:1px solid #ddd; border-radius:4px;" 
                                           placeholder="Nama Warna" oninput="checkDuplicates()" required>
                                    <!-- HIDDEN INPUT UNTUK KODE HEX -->
                                    <input type="hidden" name="warna_hex[<?= $i ?>]" value="<?= $_POST['warna_hex'][$i] ?>">
                                </div>
                                <!-- INPUT GAMBAR OPSIONAL UNTUK WARNA -->
                                <small style="display:block; color:#666; margin-top:3px; font-size:11px;">
                                    <i class="fas fa-image"></i> Foto untuk warna ini (opsional):
                                </small>
                                <input type="file" name="gambar_warna[<?= $i ?>]" 
                                       style="display:block; font-size:10px; margin-top:2px;"
                                       accept="image/jpeg,image/png,image/gif,image/webp"
                                       onchange="validateFileSize(this, 5); checkImageDuplicates()">
                            </div>
                            <!-- TOMBOL HAPUS VARIAN -->
                            <button type="button" onclick="removeVarian(this)" class="delete-btn">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- PESAN JIKA BELUM ADA VARIAN WARNA -->
                    <div class="no-varian-message" id="noVarianMessage">
                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        <strong>BELUM ADA VARIASI WARNA!</strong> Silakan tambahkan minimal 1 variasi warna.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- TOMBOL TAMBAH VARIAN MANUAL -->
            <div style="text-align: center; margin-top: 10px;">
                <button type="button" onclick="addEmptyVarian()" 
                        style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-plus"></i> Tambah Varian Manual
                </button>
            </div>
        </div>

        <!-- INPUT MOTIF KAIN -->
        <div class="form-group">
            <label for="motif_kain">
                Motif Kain <span class="required-asterisk">*</span>
            </label>
            <input type="text" id="motif_kain" name="motif_kain" 
                   value="<?= htmlspecialchars($_POST['motif_kain'] ?? '') ?>" required>
        </div>

        <!-- GRID UNTUK TIPE KAIN, SCREEN, DAN DESKRIPSI -->
        <div style="display: flex; gap: 10px;">
            <!-- INPUT TIPE KAIN -->
            <div class="form-group" style="flex: 2;">
                <label for="tipe_kain">
                    Tipe Kain <span class="required-asterisk">*</span>
                </label>
                <input type="text" id="tipe_kain" name="tipe_kain" 
                       value="<?= htmlspecialchars($_POST['tipe_kain'] ?? '') ?>" required>
            </div>
            <!-- INPUT SCREEN -->
            <div class="form-group" style="flex: 1;">
                <label for="screen">
                    Screen <span class="required-asterisk">*</span>
                </label>
                <input type="number" id="screen" name="screen" 
                       value="<?= htmlspecialchars($_POST['screen'] ?? '') ?>" required>
            </div>
            <!-- INPUT DESKRIPSI (OPSIONAL) -->
            <div class="form-group">
                <label for="deskripsi">
                    Deskripsi Produk
                </label>
                <textarea id="deskripsi" name="deskripsi" rows="4" class="form-control" 
                        placeholder="Masukkan deskripsi lengkap produk (karakteristik, kelebihan, rekomendasi penggunaan, dll)..."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                <small style="color: #666; font-size: 12px;">
                    <i class="fas fa-info-circle"></i> Opsional. Gunakan untuk memberikan informasi detail tentang produk.
                </small>
            </div>
        </div>

        <!-- TOMBOL SIMPAN PRODUK -->
        <button type="submit" name="simpan" class="btn-save" id="submitBtn">
            <i class="fas fa-save"></i> SIMPAN PRODUK
        </button>
    </form>
</div>

<!-- ====================================================== -->
<!-- BAGIAN JAVASCRIPT: FUNGSIONALITAS CLIENT-SIDE -->
<!-- ====================================================== -->

<script>
// VARIABEL GLOBAL
let vIdx = <?= isset($_POST['warna_list']) ? count($_POST['warna_list']) : 0 ?>; // Counter untuk index warna
let colorMap = new Map(); // Map untuk melacak warna HEX yang sudah ada
let nameMap = new Map(); // Map untuk melacak nama warna yang sudah ada

// Inisialisasi colorMap dari data yang sudah ada (jika form di-refresh)
<?php if (isset($_POST['warna_hex'])): ?>
    <?php foreach($_POST['warna_hex'] as $i => $hex): ?>
        colorMap.set('<?= strtolower($hex) ?>', <?= $i ?>);
    <?php endforeach; ?>
<?php endif; ?>

<?php if (isset($_POST['warna_list'])): ?>
    <?php foreach($_POST['warna_list'] as $i => $name): ?>
        nameMap.set('<?= strtolower(htmlspecialchars($name)) ?>', <?= $i ?>);
    <?php endforeach; ?>
<?php endif; ?>

// FUNGSI: UPDATE COUNTER WARNA
function updateColorCounter() {
    const colorCount = document.querySelectorAll('.varian-item').length;
    document.getElementById('colorCounter').textContent = colorCount + ' warna';
    document.getElementById('currentColorCount').textContent = colorCount;
    
    // Update state tombol submit
    updateSubmitButton();
}

// FUNGSI: PREVIEW GAMBAR SEBELUM UPLOAD
function previewImg(input, targetId) {
    // Cek apakah ada file yang dipilih
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        // Event handler ketika file selesai dibaca
        reader.onload = (e) => { 
            document.getElementById(targetId).src = e.target.result; 
        }
        // Baca file sebagai Data URL
        reader.readAsDataURL(input.files[0]);
    }
}

// FUNGSI: VALIDASI UKURAN FILE
function validateFileSize(input, maxSizeMB) {
    const file = input.files[0];
    if (file) {
        const fileSize = file.size / 1024 / 1024; // Konversi ke MB
        if (fileSize > maxSizeMB) {
            const errorElement = input.nextElementSibling || input.parentElement.querySelector('#fileSizeError');
            if (errorElement) {
                errorElement.textContent = `Ukuran file terlalu besar (${fileSize.toFixed(2)}MB). Maksimal ${maxSizeMB}MB.`;
                errorElement.style.display = 'block';
                input.value = ''; // Reset input file
            }
            return false;
        }
    }
    return true;
}

// FUNGSI: TAMBAH VARIAN WARNA KUSTOM
function addCustomVarian() {
    const hex = document.getElementById('customColor').value;
    const nameInput = document.getElementById('customColorName');
    let name = nameInput.value.trim();
    
    // Jika nama kosong, gunakan default
    if (!name) {
        name = 'Warna ' + hex.toUpperCase();
    }
    
    addVarian(hex, name);
    nameInput.value = ''; // Reset input nama
}

// FUNGSI: TAMBAH VARIAN WARNA
function addVarian(hex, name) {
    // Normalisasi input
    const normalizedHex = hex.toLowerCase().trim();
    const normalizedName = name.toLowerCase().trim();
    
    // CEK DUPLIKASI: Kode HEX
    if (colorMap.has(normalizedHex)) {
        showDuplicateWarning(`Warna dengan kode HEX ${hex} sudah ditambahkan.`);
        return; // Hentikan jika ada duplikat
    }
    
    // CEK DUPLIKASI: Nama warna
    if (nameMap.has(normalizedName)) {
        showDuplicateWarning(`Nama warna "${name}" sudah digunakan.`);
        return; // Hentikan jika ada duplikat
    }
    
    const list = document.getElementById('varian-list');
    const noVarianMessage = document.getElementById('noVarianMessage');
    
    // Sembunyikan pesan "tidak ada varian" jika ada
    if (noVarianMessage) {
        noVarianMessage.style.display = 'none';
    }
    
    const colorNumber = vIdx + 1;
    // Template HTML untuk varian baru
    const html = `
        <div class="varian-item" data-hex="${normalizedHex}" data-name="${normalizedName}">
            <!-- Preview warna -->
            <div style="width:20px; height:20px; background:${hex}; border-radius:4px; border:1px solid #ccc;"></div>
            <div style="flex:1">
                <!-- Input nama warna -->
                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                    <span style="font-size: 12px; color: #666;">Warna #${colorNumber}</span>
                    <input type="text" name="warna_list[${vIdx}]" value="${name}" 
                           style="width:150px; padding:5px; font-size:12px; border:1px solid #ddd; border-radius:4px;" 
                           placeholder="Nama Warna" oninput="checkDuplicates()" required>
                    <!-- Hidden input untuk kode HEX -->
                    <input type="hidden" name="warna_hex[${vIdx}]" value="${hex}">
                </div>
                <!-- Input file gambar (opsional) -->
                <small style="display:block; color:#666; margin-top:3px; font-size:11px;">
                    <i class="fas fa-image"></i> Foto untuk warna ini (opsional):
                </small>
                <input type="file" name="gambar_warna[${vIdx}]" 
                       style="display:block; font-size:10px; margin-top:2px;"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       onchange="validateFileSize(this, 5); checkImageDuplicates()">
            </div>
            <!-- Tombol hapus varian -->
            <button type="button" onclick="removeVarian(this)" class="delete-btn">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    `;
    
    // Tambahkan HTML ke dalam list
    list.insertAdjacentHTML('beforeend', html);
    
    // Tambahkan ke maps untuk tracking
    colorMap.set(normalizedHex, vIdx);
    nameMap.set(normalizedName, vIdx);
    
    vIdx++; // Increment counter
    updateColorCounter(); // Update counter tampilan
    hideDuplicateWarning(); // Sembunyikan warning jika ada
}

// FUNGSI: TAMBAH VARIAN DENGAN WARNA RANDOM
function addEmptyVarian() {
    // Generate warna random dalam format HEX
    const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
    addVarian(randomColor, 'Warna ' + (vIdx + 1));
}

// FUNGSI: HAPUS VARIAN WARNA
function removeVarian(button) {
    const item = button.closest('.varian-item');
    const hex = item.dataset.hex;
    const name = item.dataset.name;
    
    // Hapus dari maps tracking
    if (hex) colorMap.delete(hex);
    if (name) nameMap.delete(name);
    
    item.remove(); // Hapus elemen dari DOM
    
    // Cek apakah masih ada varian
    const list = document.getElementById('varian-list');
    const varianItems = list.querySelectorAll('.varian-item');
    
    // Tampilkan pesan "tidak ada varian" jika semua varian dihapus
    if (varianItems.length === 0) {
        const noVarianMessage = document.getElementById('noVarianMessage');
        if (noVarianMessage) {
            noVarianMessage.style.display = 'block';
        }
    }
    
    updateColorCounter(); // Update counter
    hideDuplicateWarning(); // Sembunyikan warning
}

// FUNGSI: CEK DUPLIKASI WARNA
function checkDuplicates() {
    const items = document.querySelectorAll('.varian-item');
    const hexMap = new Map(); // Map lokal untuk HEX
    const nameMap = new Map(); // Map lokal untuk nama
    let hasDuplicate = false; // Flag untuk duplikasi
    
    // Loop melalui semua varian
    items.forEach((item, index) => {
        const hexInput = item.querySelector('input[name^="warna_hex"]');
        const nameInput = item.querySelector('input[name^="warna_list"]');
        
        if (hexInput && nameInput) {
            const hex = hexInput.value.toLowerCase().trim();
            const name = nameInput.value.toLowerCase().trim();
            
            // Reset class warning/error
            item.classList.remove('duplicate-warning', 'duplicate-error');
            
            // CEK DUPLIKASI KODE HEX
            if (hexMap.has(hex)) {
                // Tandai kedua item yang duplicate
                item.classList.add('duplicate-error');
                hexMap.get(hex).classList.add('duplicate-error');
                hasDuplicate = true;
                showDuplicateWarning(`Warna dengan kode HEX ${hexInput.value} muncul lebih dari satu kali.`);
            } else {
                hexMap.set(hex, item);
            }
            
            // CEK DUPLIKASI NAMA WARNA (kecuali jika kosong)
            if (nameMap.has(name) && name !== '') {
                // Tandai sebagai warning (bukan error)
                item.classList.add('duplicate-warning');
                nameMap.get(name).classList.add('duplicate-warning');
                hasDuplicate = true;
                showDuplicateWarning(`Nama warna "${nameInput.value}" digunakan lebih dari satu kali.`);
            } else {
                nameMap.set(name, item);
            }
        }
    });
    
    // Sembunyikan warning jika tidak ada duplikasi
    if (!hasDuplicate) {
        hideDuplicateWarning();
    }
    
    return !hasDuplicate; // Return true jika tidak ada duplikasi
}

// FUNGSI: CEK DUPLIKASI GAMBAR (PLACEHOLDER - bisa dikembangkan)
function checkImageDuplicates() {
    // Implementasi deteksi duplikasi gambar bisa ditambahkan di sini
    // Misalnya dengan menggunakan FileReader untuk membaca hash file
}

// FUNGSI: TAMPILKAN PESAN WARNING DUPLIKASI
function showDuplicateWarning(message) {
    const warningBox = document.getElementById('duplicateWarning');
    const messageSpan = document.getElementById('warningMessage');
    
    if (warningBox && messageSpan) {
        messageSpan.textContent = message;
        warningBox.style.display = 'block';
    }
    
    updateSubmitButton(); // Update state tombol submit
}

// FUNGSI: SEMBUNYIKAN PESAN WARNING
function hideDuplicateWarning() {
    const warningBox = document.getElementById('duplicateWarning');
    if (warningBox) {
        warningBox.style.display = 'none';
    }
    
    updateSubmitButton(); // Update state tombol submit
}

// FUNGSI: UPDATE STATE TOMBOL SUBMIT
function updateSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    const colorCount = document.querySelectorAll('.varian-item').length;
    const hasDuplicates = document.querySelectorAll('.duplicate-error, .duplicate-warning').length > 0;
    const hasRequiredColors = colorCount >= 1;
    
    if (submitBtn) {
        // Disable tombol jika ada masalah
        submitBtn.disabled = hasDuplicates || !hasRequiredColors;
        
        // Customize tampilan tombol berdasarkan state
        if (!hasRequiredColors) {
            submitBtn.title = 'Tambahkan minimal 1 variasi warna';
            submitBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> TAMBAHKAN WARNA TERLEBIH DAHULU';
            submitBtn.style.background = '#6c757d';
        } else if (hasDuplicates) {
            submitBtn.title = 'Harap perbaiki duplikasi warna terlebih dahulu';
            submitBtn.innerHTML = '<i class="fas fa-save"></i> PERBAIKI DUPLIKASI WARNA';
            submitBtn.style.background = '#dc3545';
        } else {
            submitBtn.title = '';
            submitBtn.innerHTML = '<i class="fas fa-save"></i> SIMPAN PRODUK';
            submitBtn.style.background = '#800000';
        }
    }
}

// FUNGSI: VALIDASI FORM SEBELUM DISUBMIT
function validateForm() {
    // 1. Validasi file utama
    const mainImage = document.getElementById('gambar_utama');
    if (!mainImage.files || mainImage.files.length === 0) {
        alert('Foto utama wajib diupload.');
        return false;
    }
    
    // 2. Validasi minimal 1 variasi warna
    const colorCount = document.querySelectorAll('.varian-item').length;
    if (colorCount < 1) {
        alert('Variasi warna wajib diisi. Minimal tambahkan 1 variasi warna.');
        return false;
    }
    
    // 3. Validasi nama warna tidak boleh kosong
    const colorNames = document.querySelectorAll('input[name^="warna_list"]');
    let emptyColorName = false;
    colorNames.forEach(input => {
        if (!input.value.trim()) {
            emptyColorName = true;
            input.style.borderColor = '#dc3545'; // Highlight error
        } else {
            input.style.borderColor = '#ddd'; // Reset
        }
    });
    
    if (emptyColorName) {
        alert('Semua nama warna harus diisi.');
        return false;
    }
    
    // 4. Validasi duplikasi warna
    if (!checkDuplicates()) {
        alert('Masih terdapat duplikasi warna. Harap perbaiki sebelum menyimpan.');
        return false;
    }
    
    // 5. Validasi ukuran file utama
    if (!validateFileSize(mainImage, 5)) {
        return false;
    }
    
    // 6. Validasi ukuran file varian warna
    const varianFiles = document.querySelectorAll('input[name^="gambar_warna"]');
    for (let fileInput of varianFiles) {
        if (fileInput.files.length > 0 && !validateFileSize(fileInput, 5)) {
            return false;
        }
    }
    
    return true; // Semua validasi lolos
}

// INISIALISASI SAAT DOKUMEN DIMUAT
document.addEventListener('DOMContentLoaded', function() {
    updateColorCounter(); // Update counter awal
    checkDuplicates(); // Cek duplikasi awal
    updateSubmitButton(); // Update state tombol submit awal
    
    // Inisialisasi maps dari data yang sudah ada di form
    const items = document.querySelectorAll('.varian-item');
    items.forEach((item, index) => {
        const hexInput = item.querySelector('input[name^="warna_hex"]');
        const nameInput = item.querySelector('input[name^="warna_list"]');
        
        if (hexInput && nameInput) {
            const hex = hexInput.value.toLowerCase().trim();
            const name = nameInput.value.toLowerCase().trim();
            
            // Simpan data ke atribut dataset
            item.dataset.hex = hex;
            item.dataset.name = name;
            
            // Tambahkan ke maps global
            colorMap.set(hex, index);
            nameMap.set(name, index);
        }
    });
});
</script>
</body>
</html>