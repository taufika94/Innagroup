
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel - PT INTIM' ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* --- Gaya untuk seksi Quick Links (Tautan Cepat) di Dashboard --- */
        .quick-links-section {
            background: var(--white);      /* Warna latar putih dari variabel CSS */
            padding: 24px;                 /* Jarak dalam kotak */
            border-radius: var(--radius-xl); /* Membuat sudut kotak sangat tumpul */
            box-shadow: var(--shadow);     /* Memberikan efek bayangan pada kotak */
            margin-top: 32px;              /* Jarak luar bagian atas */
        }
        
        /* Mengatur judul di dalam seksi Quick Links */
        .quick-links-title {
            margin-bottom: 16px;           /* Jarak bawah judul */
            color: var(--primary);         /* Warna teks utama */
            display: flex;                 /* Mengaktifkan flexbox */
            align-items: center;           /* Menyejajarkan teks & ikon secara vertikal */
            gap: 10px;                     /* Jarak antara ikon dan teks */
        }
        
        /* Mengatur tata letak tombol menu menjadi grid yang fleksibel */
        .quick-links-grid {
            display: grid;                 /* Mengaktifkan sistem Grid */
            /* Membuat kolom otomatis: minimal 200px, maksimal mengisi sisa ruang */
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;                     /* Jarak antar kotak menu */
        }
        
        /* Gaya tombol di dalam Quick Links */
        .quick-link-btn {
            justify-content: center;       /* Konten di tengah secara horizontal */
            text-align: center;            /* Teks rata tengah */
            display: flex;                 /* Mengaktifkan flexbox */
            align-items: center;           /* Konten di tengah secara vertikal */
            gap: 8px;                      /* Jarak antara ikon dan teks dalam tombol */
        }
        
        /* --- Gaya untuk Color Chips (Kotak Pilihan Warna) --- */
        .color-chip {
            display: inline-block;         /* Agar bisa diatur lebar dan tingginya */
            width: 18px;                   /* Lebar kotak warna */
            height: 18px;                  /* Tinggi kotak warna */
            border: 1px solid var(--gray-200); /* Garis tepi tipis warna abu */
            border-radius: 3px;            /* Sudut kotak agak melengkung */
            margin: 1px;                   /* Jarak antar kotak warna */
            cursor: pointer;               /* Mengubah kursor jadi jari saat diarahkan */
            transition: transform 0.2s;    /* Animasi halus saat ada perubahan ukuran */
        }
        
        /* Efek saat kursor berada di atas kotak warna */
        .color-chip:hover {
            transform: scale(1.2);         /* Kotak membesar 20% */
        }
        
        /* --- Gaya untuk Label Status Produk --- */
        .product-status {
            padding: 4px 8px;              /* Jarak dalam label */
            border-radius: 4px;            /* Sudut label melengkung */
            font-size: 12px;               /* Ukuran teks kecil */
            font-weight: 600;              /* Teks agak tebal */
        }
        
        /* Status: Produk Baru (Warna Hijau) */
        .status-new {
            background: rgba(46, 204, 113, 0.1); /* Hijau transparan */
            color: var(--success);
        }
        
        /* Status: Stok Habis (Warna Merah) */
        .status-outstock {
            background: rgba(231, 76, 60, 0.1);  /* Merah transparan */
            color: var(--danger);
        }
        
        /* Status: Stok Menipis (Warna Oranye) */
        .status-lowstock {
            background: rgba(243, 156, 18, 0.1); /* Oranye transparan */
            color: var(--warning);
        }
    </style>
</head>
<body>
    <div class="admin-container">