<?php
session_start();
include 'config.php';

// 1. Proteksi: Hanya Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// AMBIL NAMA DARI SESSION
$username = $_SESSION['username'];

$errors = [];

// 2. Ambil Data Produk untuk Diedit
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$query = "SELECT * FROM produk WHERE kode_inna = '$id'";
$result = mysqli_query($conn, $query);
$produk = mysqli_fetch_assoc($result);

if (!$produk) {
    echo "<script>alert('Produk tidak ditemukan!'); window.location='admin_dashboard.php';</script>";
    exit;
}

// 3. Proses data produk untuk form edit
$kode_inna = $produk['kode_inna'];
$motif_kain = $produk['motif_kain'];
$tipe_kain = $produk['tipe_kain'];
$screen = $produk['screen'];
$deskripsi = $produk['deskripsi'];

// Parse gambar
$gambar_array = explode(",", $produk['gambar']);
$gambar_utama = !empty($gambar_array[0]) ? $gambar_array[0] : '';

// Parse warna
$warna_data = [];
if (!empty($produk['warna_kain'])) {
    $warna_array = explode(",", $produk['warna_kain']);
    foreach ($warna_array as $index => $warna_str) {
        $parts = explode(":", $warna_str);
        if (count($parts) >= 2) {
            $warna_data[] = [
                'nama' => $parts[0],
                'hex' => isset($parts[1]) ? $parts[1] : '#cccccc',
                'gambar_index' => isset($parts[2]) ? (int)$parts[2] : 0,
                'gambar_path' => isset($gambar_array[isset($parts[2]) ? (int)$parts[2] : 0]) ? $gambar_array[isset($parts[2]) ? (int)$parts[2] : 0] : ''
            ];
        }
    }
}

// 4. Logika Update
if (isset($_POST['update'])) {
    $kode   = trim(mysqli_real_escape_string($conn, $_POST['kode_inna']));
    $motif  = trim(mysqli_real_escape_string($conn, $_POST['motif_kain']));
    $tipe   = trim(mysqli_real_escape_string($conn, $_POST['tipe_kain']));
    $screen = trim(mysqli_real_escape_string($conn, $_POST['screen']));
    $deskripsi = trim(mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? ''));
    
    $warna_names = $_POST['warna_list'] ?? []; 
    $warna_hexs  = $_POST['warna_hex'] ?? [];  
    $target_dir  = "../uploads/";

    // --- VALIDASI DASAR ---
    if (empty($kode)) $errors[] = "Kode INNA wajib diisi.";
    if (empty($motif)) $errors[] = "Motif Kain wajib diisi.";
    if (empty($tipe)) $errors[] = "Tipe Kain wajib diisi.";
    if ($screen === "" || !is_numeric($screen)) $errors[] = "Data Screen harus berupa angka.";

    // --- VALIDASI: VARIASI WARNA WAJIB DIISI ---
    if (empty($warna_names)) {
        $errors[] = "Variasi warna wajib diisi. Minimal tambahkan satu variasi warna.";
    }

    // --- VALIDASI TAMBAHAN: CEK DUPLIKASI WARNA ---
    if (!empty($warna_names)) {
        $seen_colors = [];
        foreach ($warna_hexs as $key => $hex) {
            $color_key = strtolower(trim($hex));
            if (in_array($color_key, $seen_colors)) {
                $errors[] = "Warna dengan kode HEX '" . $color_key . "' sudah ditambahkan. Hapus duplikat.";
                break;
            }
            $seen_colors[] = $color_key;
        }
    }

    // --- VALIDASI TAMBAHAN: CEK DUPLIKASI NAMA WARNA ---
    if (!empty($warna_names)) {
        $seen_names = [];
        foreach ($warna_names as $key => $name) {
            $name_lower = strtolower(trim($name));
            if (in_array($name_lower, $seen_names)) {
                $errors[] = "Nama warna '" . htmlspecialchars($name) . "' sudah digunakan. Gunakan nama lain.";
                break;
            }
            $seen_names[] = $name_lower;
        }
    }

    if (empty($errors)) {
        try {
            // --- PROSES UPLOAD FILE ---
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            $final_images = []; // Array untuk gambar unik
            $warna_combined = []; // Array untuk warna dengan referensi gambar
            $image_hash_map = []; // Map untuk tracking duplikasi gambar
            
            // Definisikan tipe file yang diizinkan
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            // A. Foto Utama (Cover) - Selalu index 0
            // Cek apakah ada upload gambar utama baru
            $gambar_utama_path = $gambar_utama; // Default gunakan yang lama
            
            if (isset($_FILES['gambar_utama']) && $_FILES['gambar_utama']['error'] == 0) {
                // Upload gambar utama baru
                $ext_main = strtolower(pathinfo($_FILES['gambar_utama']['name'], PATHINFO_EXTENSION));
                $file_main = time() . "_MAIN_" . $kode . "." . $ext_main;
                $path_main = $target_dir . $file_main;
                
                if (move_uploaded_file($_FILES['gambar_utama']['tmp_name'], $path_main)) {
                    $gambar_utama_path = $path_main;
                }
            }
            
            $final_images[0] = $gambar_utama_path; // Index 0 untuk cover
            $image_hash_map[$gambar_utama_path] = file_exists($gambar_utama_path) ? md5_file($gambar_utama_path) : '';

            // B. Varian Warna (WAJIB - minimal 1 warna)
            if (!empty($warna_names)) {
                $warna_index = 0;
                $uploaded_warna_images = []; // Untuk validasi duplikasi gambar varian
                
                foreach ($warna_names as $key => $nama) {
                    $current_hex = trim(mysqli_real_escape_string($conn, $warna_hexs[$key]));
                    $current_name = trim(mysqli_real_escape_string($conn, $nama));
                    
                    $gambar_index = 0; // Default ke gambar cover
                    
                    // Cek apakah ada file gambar untuk varian ini
                    if (isset($_FILES['gambar_warna']['name'][$key]) && $_FILES['gambar_warna']['error'][$key] == 0) {
                        $ext_var = strtolower(pathinfo($_FILES['gambar_warna']['name'][$key], PATHINFO_EXTENSION));
                        $file_var = time() . "_VAR_" . $warna_index . "_" . $kode . "." . $ext_var;
                        $path_var = $target_dir . $file_var;
                        
                        // Cek ukuran file
                        if ($_FILES['gambar_warna']['size'][$key] > 5 * 1024 * 1024) {
                            throw new Exception("Gambar untuk warna '$current_name' terlalu besar. Maksimal 5MB.");
                        }
                        
                        // Cek tipe file
                        if (!in_array($ext_var, $allowed_types)) {
                            throw new Exception("Format gambar untuk warna '$current_name' tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.");
                        }
                        
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
                                // Hapus file duplikat
                                unlink($path_var);
                                // Cari index gambar yang sudah ada
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
                    } else {
                        // Jika tidak upload gambar baru, cek apakah ada gambar lama
                        if (isset($warna_data[$key]) && !empty($warna_data[$key]['gambar_path'])) {
                            $gambar_lama = $warna_data[$key]['gambar_path'];
                            if (file_exists($gambar_lama)) {
                                // Cari index gambar lama di array final_images
                                $existing_index = array_search($gambar_lama, $final_images);
                                if ($existing_index !== false) {
                                    $gambar_index = $existing_index;
                                } else {
                                    // Tambahkan gambar lama ke array
                                    $next_index = count($final_images);
                                    $final_images[$next_index] = $gambar_lama;
                                    $image_hash_map[$gambar_lama] = md5_file($gambar_lama);
                                    $gambar_index = $next_index;
                                }
                            }
                        }
                    }
                    
                    // Format: nama:hex:index_gambar
                    $warna_combined[] = $current_name . ":" . $current_hex . ":" . $gambar_index;
                    $warna_index++;
                }
            }

            // --- UPDATE KE DATABASE ---
            $all_images_str = implode(",", $final_images);
            $all_colors_str = implode(",", $warna_combined);
            
            // PERBAIKAN: Hapus koma sebelum WHERE
            $query = "UPDATE produk SET 
                      kode_inna = '$kode', 
                      gambar = '$all_images_str', 
                      motif_kain = '$motif', 
                      tipe_kain = '$tipe', 
                      warna_kain = '$all_colors_str', 
                      screen = '$screen',
                      deskripsi = '$deskripsi'
                      WHERE kode_inna = '$id'";
            
            if (mysqli_query($conn, $query)) {
                echo "<script>
                    alert('Produk berhasil diperbarui!');
                    window.location='admin_dashboard.php';
                </script>";
                exit;
            } else {
                throw new Exception("Gagal memperbarui database: " . mysqli_error($conn));
            }

        } catch (mysqli_sql_exception $e) {
            $errors[] = "Kesalahan Database: " . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk Kain - PT INTIM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        .error-box { 
            background: #fee2e2; 
            border: 1px solid #ef4444; 
            color: #b91c1c; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
        }
        .warning-box { 
            background: #fff3cd; 
            border: 1px solid #ffc107; 
            color: #856404; 
            padding: 10px; 
            border-radius: 6px; 
            margin-bottom: 15px; 
            font-size: 14px; 
        }
        .success-box { 
            background: #d1e7dd; 
            border: 1px solid #0f5132; 
            color: #0f5132; 
            padding: 10px; 
            border-radius: 6px; 
            margin-bottom: 15px; 
            font-size: 14px; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            font-weight: bold; 
            display: block; 
            margin-bottom: 5px; 
        }
        input[type="text"], input[type="number"], textarea { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', sans-serif;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
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
        .no-varian-message {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
            border: 1px dashed #dee2e6;
        }
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
        .required-asterisk {
            color: #dc3545;
        }
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
        .color-counter {
            background: #800000;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
        }
        .current-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        .deskripsi-tips {
            background: #f0f8ff;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            border-left: 3px solid #4a90e2;
            font-size: 12px;
            color: #2c5282;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2><i class="fas fa-edit"></i> Edit Produk Kain</h2>
        <div style="font-size: 14px; color: #666; margin-top: 5px;">
            Kode Produk: <strong><?= htmlspecialchars($kode_inna) ?></strong>
        </div>
    </div>

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

    <form method="POST" enctype="multipart/form-data" id="productForm" onsubmit="return validateForm()">
        <input type="hidden" name="old_kode" value="<?= htmlspecialchars($kode_inna) ?>">
        
        <div class="form-group">
            <label for="kode_inna">
                Kode INNA <span class="required-asterisk">*</span>
            </label>
            <input type="text" id="kode_inna" name="kode_inna" 
                   value="<?= htmlspecialchars($_POST['kode_inna'] ?? $kode_inna) ?>" 
                   placeholder="Misal: 12Vbv" required>
        </div>

        <div class="section-box" style="border-left: 5px solid #800000;">
            <label style="color: #800000;">
                <i class="fas fa-image"></i> 1. Foto Sampul Utama
            </label>
            <div class="info-badge">
                <i class="fas fa-info-circle"></i> 
                • Gambar ini akan menjadi gambar utama (cover) produk<br>
                • Kosongkan jika tidak ingin mengubah gambar utama<br>
                • Format yang didukung: JPG, PNG, GIF, WEBP. Maksimal 5MB.
            </div>
            <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                <?php if (!empty($gambar_utama)): ?>
                    <div>
                        <img src="<?= htmlspecialchars($gambar_utama) ?>" class="current-image" id="prev_utama">
                        <div style="font-size: 12px; color: #666;">Gambar saat ini</div>
                    </div>
                <?php else: ?>
                    <img src="https://via.placeholder.com/100?text=SAMPUL" id="prev_utama" 
                         class="current-image">
                <?php endif; ?>
                <div>
                    <input type="file" name="gambar_utama" id="gambar_utama" 
                           accept="image/jpeg,image/png,image/gif,image/webp" 
                           onchange="previewImg(this, 'prev_utama'); validateFileSize(this, 5)">
                    <small id="fileSizeError" style="color: red; display: none;"></small>
                    <?php if (!empty($gambar_utama)): ?>
                        <div style="margin-top: 5px; font-size: 12px; color: #666;">
                            <i class="fas fa-info-circle"></i> Kosongkan jika tidak ingin mengubah
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="section-box">
            <div class="required-badge">WAJIB</div>
            <label>
                <i class="fas fa-palette"></i> 2. Variasi Warna <span class="required-asterisk">*</span>
                <span id="colorCounter" class="color-counter"><?= count($warna_data) ?> warna</span>
            </label>
            
            <div class="warning-badge">
                <i class="fas fa-exclamation-circle"></i> 
                <strong>WAJIB DIISI:</strong> Setiap produk harus memiliki minimal 1 variasi warna. 
                Jika produk hanya memiliki 1 warna, tambahkan warna tersebut.
            </div>
            
            <div class="minimal-message">
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                Minimal: <strong>1 warna</strong> | Saat ini: <strong><span id="currentColorCount"><?= count($warna_data) ?></span> warna</strong>
            </div>
            
            <div class="warning-box" id="duplicateWarning" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="warningMessage"></span>
            </div>
            
            <div style="display:flex; align-items:center; gap:15px; margin: 10px 0; background: #eee; padding: 12px; border-radius: 8px;">
                <div>
                    <small style="display:block; margin-bottom:5px; font-weight:bold;">Preset Warna:</small>
                    <div style="display:flex; gap:8px;">
                        <?php 
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
                        foreach($presets as $h => $n): ?>
                            <div class="color-circle" style="background:<?= $h ?>" 
                                 title="<?= $n ?>" 
                                 onclick="addVarian('<?= $h ?>', '<?= $n ?>')"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="border-left: 2px solid #ccc; padding-left: 15px; flex: 1;">
                    <small style="display:block; margin-bottom:5px; font-weight:bold;">Warna Kustom:</small>
                    <div class="color-input-group">
                        <input type="color" id="customColor" value="#800000" 
                               style="width:35px; height:35px; border:none; cursor:pointer; background:none;">
                        <input type="text" id="customColorName" placeholder="Nama warna (misal: Merah Maroon)" 
                               style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                        <button type="button" onclick="addCustomVarian()" 
                                style="padding: 6px 12px; cursor:pointer; background:#800000; color:white; border:none; border-radius:4px; font-size:12px;">
                            <i class="fas fa-plus"></i> Tambah Warna
                        </button>
                    </div>
                </div>
            </div>

            <div class="info-badge">
                <i class="fas fa-info-circle"></i> 
                • Setiap warna akan ditampilkan sebagai tombol pilih warna di halaman detail<br>
                • Foto untuk varian warna bersifat <strong>opsional</strong> (kosongkan jika tidak ingin mengubah)<br>
                • Sistem akan mendeteksi dan mencegah warna atau gambar duplikat<br>
                • Format gambar: JPG, PNG, GIF, WEBP. Maksimal 5MB per gambar.
            </div>

            <div id="varian-list">
                <?php if (!empty($warna_data)): ?>
                    <?php foreach($warna_data as $index => $warna): ?>
                        <div class="varian-item" data-hex="<?= strtolower($warna['hex']) ?>" data-name="<?= strtolower($warna['nama']) ?>">
                            <div style="width:20px; height:20px; background:<?= $warna['hex'] ?>; border-radius:4px; border:1px solid #ccc;"></div>
                            <div style="flex:1">
                                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                                    <span style="font-size: 12px; color: #666;">Warna #<?= $index+1 ?></span>
                                    <input type="text" name="warna_list[<?= $index ?>]" value="<?= htmlspecialchars($warna['nama']) ?>" 
                                           style="width:150px; padding:5px; font-size:12px; border:1px solid #ddd; border-radius:4px;" 
                                           placeholder="Nama Warna" oninput="checkDuplicates()" required>
                                    <input type="hidden" name="warna_hex[<?= $index ?>]" value="<?= $warna['hex'] ?>">
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                                    <?php if (!empty($warna['gambar_path'])): ?>
                                        <div>
                                            <img src="<?= htmlspecialchars($warna['gambar_path']) ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                                            <div style="font-size: 10px; color: #666;">Gambar saat ini</div>
                                        </div>
                                    <?php endif; ?>
                                    <div style="flex: 1;">
                                        <small style="display:block; color:#666; font-size:11px;">
                                            <i class="fas fa-image"></i> Foto untuk warna ini (kosongkan jika tidak ingin mengubah):
                                        </small>
                                        <input type="file" name="gambar_warna[<?= $index ?>]" 
                                               style="display:block; font-size:10px; margin-top:2px;"
                                               accept="image/jpeg,image/png,image/gif,image/webp"
                                               onchange="validateFileSize(this, 5); checkImageDuplicates()">
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="removeVarian(this)" class="delete-btn">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-varian-message" id="noVarianMessage">
                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        <strong>BELUM ADA VARIASI WARNA!</strong> Silakan tambahkan minimal 1 variasi warna.
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 10px;">
                <button type="button" onclick="addEmptyVarian()" 
                        style="padding: 8px 15px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-plus"></i> Tambah Varian Manual
                </button>
            </div>
        </div>

        <div class="form-group">
            <label for="motif_kain">
                Motif Kain <span class="required-asterisk">*</span>
            </label>
            <input type="text" id="motif_kain" name="motif_kain" 
                   value="<?= htmlspecialchars($_POST['motif_kain'] ?? $motif_kain) ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div class="form-group">
                <label for="tipe_kain">
                    Tipe Kain <span class="required-asterisk">*</span>
                </label>
                <input type="text" id="tipe_kain" name="tipe_kain" 
                       value="<?= htmlspecialchars($_POST['tipe_kain'] ?? $tipe_kain) ?>" required>
            </div>
            <div class="form-group">
                <label for="screen">
                    Screen <span class="required-asterisk">*</span>
                </label>
                <input type="number" id="screen" name="screen" 
                       value="<?= htmlspecialchars($_POST['screen'] ?? $screen) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="deskripsi">
                Deskripsi Produk
            </label>
            <div class="deskripsi-tips">
                <i class="fas fa-lightbulb"></i> <strong>Tips:</strong> Jelaskan karakteristik kain, kelebihan, rekomendasi penggunaan, perawatan, dll.
            </div>
            <textarea id="deskripsi" name="deskripsi" rows="6" 
                      placeholder="Contoh: Kain jenis ini terbuat dari katun premium dengan karakteristik:
• Bahan nyaman dan menyerap keringat
• Cocok untuk pakaian sehari-hari
• Perawatan mudah, bisa dicuci dengan mesin"><?= htmlspecialchars($_POST['deskripsi'] ?? $deskripsi) ?></textarea>
            <small style="color: #666; font-size: 12px;">
                <i class="fas fa-info-circle"></i> Opsional. Gunakan untuk memberikan informasi detail tentang produk.
            </small>
        </div>

        <button type="submit" name="update" class="btn-save" id="submitBtn">
            <i class="fas fa-save"></i> UPDATE PRODUK
        </button>
        <a href="admin_dashboard.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </form>
</div>

<script>
/* Inisialisasi counter untuk varian warna */
let vIdx = <?= count($warna_data) ?>;
/* Map untuk tracking warna HEX unik */
let colorMap = new Map(); // Untuk tracking warna HEX
/* Map untuk tracking nama warna unik */
let nameMap = new Map(); // Untuk tracking nama warna

// Inisialisasi colorMap dari data yang sudah ada
<?php if (!empty($warna_data)): ?>
    <?php foreach($warna_data as $index => $warna): ?>
        /* Tambahkan data warna lama ke map untuk tracking duplikasi */
        colorMap.set('<?= strtolower($warna['hex']) ?>', <?= $index ?>);
        nameMap.set('<?= strtolower(htmlspecialchars($warna['nama'])) ?>', <?= $index ?>);
    <?php endforeach; ?>
<?php endif; ?>

/* Fungsi untuk update counter warna yang ditampilkan */
function updateColorCounter() {
    const colorCount = document.querySelectorAll('.varian-item').length;
    document.getElementById('colorCounter').textContent = colorCount + ' warna';
    document.getElementById('currentColorCount').textContent = colorCount;
    
    // Update submit button state
    updateSubmitButton();
}

/* Fungsi untuk preview gambar sebelum upload */
function previewImg(input, targetId) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = (e) => { 
            document.getElementById(targetId).src = e.target.result; 
        }
        reader.readAsDataURL(input.files[0]);
    }
}

/* Validasi ukuran file tidak melebihi batas maksimal */
function validateFileSize(input, maxSizeMB) {
    const file = input.files[0];
    if (file) {
        const fileSize = file.size / 1024 / 1024; // in MB
        if (fileSize > maxSizeMB) {
            const errorElement = input.nextElementSibling || input.parentElement.querySelector('#fileSizeError');
            if (errorElement) {
                errorElement.textContent = `Ukuran file terlalu besar (${fileSize.toFixed(2)}MB). Maksimal ${maxSizeMB}MB.`;
                errorElement.style.display = 'block';
                input.value = '';
            }
            return false;
        }
    }
    return true;
}

// Fungsi untuk varian warna
/* Fungsi untuk menambahkan varian warna kustom dari input pengguna */
function addCustomVarian() {
    const hex = document.getElementById('customColor').value;
    const nameInput = document.getElementById('customColorName');
    let name = nameInput.value.trim();
    
    if (!name) {
        name = 'Warna ' + hex.toUpperCase();
    }
    
    addVarian(hex, name);
    nameInput.value = '';
}

/* Fungsi utama untuk menambahkan varian warna dengan validasi duplikasi */
function addVarian(hex, name) {
    const normalizedHex = hex.toLowerCase().trim();
    const normalizedName = name.toLowerCase().trim();
    
    // Cek duplikasi warna HEX
    if (colorMap.has(normalizedHex)) {
        showDuplicateWarning(`Warna dengan kode HEX ${hex} sudah ditambahkan.`);
        return;
    }
    
    // Cek duplikasi nama warna
    if (nameMap.has(normalizedName)) {
        showDuplicateWarning(`Nama warna "${name}" sudah digunakan.`);
        return;
    }
    
    const list = document.getElementById('varian-list');
    const noVarianMessage = document.getElementById('noVarianMessage');
    
    // Sembunyikan pesan "tidak ada varian"
    if (noVarianMessage) {
        noVarianMessage.style.display = 'none';
    }
    
    const colorNumber = vIdx + 1;
    const html = `
        <div class="varian-item" data-hex="${normalizedHex}" data-name="${normalizedName}">
            <div style="width:20px; height:20px; background:${hex}; border-radius:4px; border:1px solid #ccc;"></div>
            <div style="flex:1">
                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                    <span style="font-size: 12px; color: #666;">Warna #${colorNumber}</span>
                    <input type="text" name="warna_list[${vIdx}]" value="${name}" 
                           style="width:150px; padding:5px; font-size:12px; border:1px solid #ddd; border-radius:4px;" 
                           placeholder="Nama Warna" oninput="checkDuplicates()" required>
                    <input type="hidden" name="warna_hex[${vIdx}]" value="${hex}">
                </div>
                <small style="display:block; color:#666; margin-top:3px; font-size:11px;">
                    <i class="fas fa-image"></i> Foto untuk warna ini (opsional):
                </small>
                <input type="file" name="gambar_warna[${vIdx}]" 
                       style="display:block; font-size:10px; margin-top:2px;"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       onchange="validateFileSize(this, 5); checkImageDuplicates()">
            </div>
            <button type="button" onclick="removeVarian(this)" class="delete-btn">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    `;
    
    list.insertAdjacentHTML('beforeend', html);
    
    // Tambahkan ke maps untuk tracking
    colorMap.set(normalizedHex, vIdx);
    nameMap.set(normalizedName, vIdx);
    
    vIdx++;
    updateColorCounter();
    hideDuplicateWarning();
}

/* Fungsi untuk menambahkan varian warna dengan warna acak */
function addEmptyVarian() {
    // Generate random color
    const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
    addVarian(randomColor, 'Warna ' + (vIdx + 1));
}

/* Fungsi untuk menghapus varian warna dari daftar */
function removeVarian(button) {
    const item = button.closest('.varian-item');
    const hex = item.dataset.hex;
    const name = item.dataset.name;
    
    // Hapus dari maps
    if (hex) colorMap.delete(hex);
    if (name) nameMap.delete(name);
    
    item.remove();
    
    // Cek apakah masih ada varian
    const list = document.getElementById('varian-list');
    const varianItems = list.querySelectorAll('.varian-item');
    
    if (varianItems.length === 0) {
        const noVarianMessage = document.getElementById('noVarianMessage');
        if (noVarianMessage) {
            noVarianMessage.style.display = 'block';
        }
    }
    
    updateColorCounter();
    hideDuplicateWarning();
}

/* Fungsi untuk mengecek duplikasi warna HEX dan nama warna */
function checkDuplicates() {
    const items = document.querySelectorAll('.varian-item');
    const hexMap = new Map();
    const nameMap = new Map();
    let hasDuplicate = false;
    
    items.forEach((item, index) => {
        const hexInput = item.querySelector('input[name^="warna_hex"]');
        const nameInput = item.querySelector('input[name^="warna_list"]');
        
        if (hexInput && nameInput) {
            const hex = hexInput.value.toLowerCase().trim();
            const name = nameInput.value.toLowerCase().trim();
            
            // Reset warning
            item.classList.remove('duplicate-warning', 'duplicate-error');
            
            // Cek HEX duplicate
            if (hexMap.has(hex)) {
                item.classList.add('duplicate-error');
                hexMap.get(hex).classList.add('duplicate-error');
                hasDuplicate = true;
                showDuplicateWarning(`Warna dengan kode HEX ${hexInput.value} muncul lebih dari satu kali.`);
            } else {
                hexMap.set(hex, item);
            }
            
            // Cek nama duplicate
            if (nameMap.has(name) && name !== '') {
                item.classList.add('duplicate-warning');
                nameMap.get(name).classList.add('duplicate-warning');
                hasDuplicate = true;
                showDuplicateWarning(`Nama warna "${nameInput.value}" digunakan lebih dari satu kali.`);
            } else {
                nameMap.set(name, item);
            }
        }
    });
    
    if (!hasDuplicate) {
        hideDuplicateWarning();
    }
    
    return !hasDuplicate;
}

/* Fungsi untuk mengecek duplikasi gambar (placeholder) */
function checkImageDuplicates() {
    // Implementasi deteksi duplikasi gambar bisa ditambahkan di sini
}

/* Fungsi untuk menampilkan peringatan duplikasi */
function showDuplicateWarning(message) {
    const warningBox = document.getElementById('duplicateWarning');
    const messageSpan = document.getElementById('warningMessage');
    
    if (warningBox && messageSpan) {
        messageSpan.textContent = message;
        warningBox.style.display = 'block';
    }
    
    updateSubmitButton();
}

/* Fungsi untuk menyembunyikan peringatan duplikasi */
function hideDuplicateWarning() {
    const warningBox = document.getElementById('duplicateWarning');
    if (warningBox) {
        warningBox.style.display = 'none';
    }
    
    updateSubmitButton();
}

/* Fungsi untuk mengupdate state tombol submit berdasarkan validasi */
function updateSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    const colorCount = document.querySelectorAll('.varian-item').length;
    const hasDuplicates = document.querySelectorAll('.duplicate-error, .duplicate-warning').length > 0;
    const hasRequiredColors = colorCount >= 1;
    
    if (submitBtn) {
        submitBtn.disabled = hasDuplicates || !hasRequiredColors;
        
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
            submitBtn.innerHTML = '<i class="fas fa-save"></i> UPDATE PRODUK';
            submitBtn.style.background = '#800000';
        }
    }
}

/* Validasi utama form sebelum submit ke server */
function validateForm() {
    // Validasi minimal 1 variasi warna
    const colorCount = document.querySelectorAll('.varian-item').length;
    if (colorCount < 1) {
        alert('Variasi warna wajib diisi. Minimal tambahkan 1 variasi warna.');
        return false;
    }
    
    // Validasi nama warna tidak kosong
    const colorNames = document.querySelectorAll('input[name^="warna_list"]');
    let emptyColorName = false;
    colorNames.forEach(input => {
        if (!input.value.trim()) {
            emptyColorName = true;
            input.style.borderColor = '#dc3545';
        } else {
            input.style.borderColor = '#ddd';
        }
    });
    
    if (emptyColorName) {
        alert('Semua nama warna harus diisi.');
        return false;
    }
    
    // Validasi duplikasi warna
    if (!checkDuplicates()) {
        alert('Masih terdapat duplikasi warna. Harap perbaiki sebelum menyimpan.');
        return false;
    }
    
    // Validasi ukuran file utama (jika diupload)
    const mainImage = document.getElementById('gambar_utama');
    if (mainImage.files.length > 0 && !validateFileSize(mainImage, 5)) {
        return false;
    }
    
    // Validasi ukuran file varian warna
    const varianFiles = document.querySelectorAll('input[name^="gambar_warna"]');
    for (let fileInput of varianFiles) {
        if (fileInput.files.length > 0 && !validateFileSize(fileInput, 5)) {
            return false;
        }
    }
    
    return true;
}

// Inisialisasi
/* Inisialisasi fungsi saat halaman selesai dimuat */
document.addEventListener('DOMContentLoaded', function() {
    updateColorCounter();
    checkDuplicates();
    updateSubmitButton();
    
    // Jika ada data dari form sebelumnya, update maps
    const items = document.querySelectorAll('.varian-item');
    items.forEach((item, index) => {
        const hexInput = item.querySelector('input[name^="warna_hex"]');
        const nameInput = item.querySelector('input[name^="warna_list"]');
        
        if (hexInput && nameInput) {
            const hex = hexInput.value.toLowerCase().trim();
            const name = nameInput.value.toLowerCase().trim();
            
            item.dataset.hex = hex;
            item.dataset.name = name;
            
            colorMap.set(hex, index);
            nameMap.set(name, index);
        }
    });
});
</script>
</body>
</html>