    </div> <!-- Tutup admin-container -->

    <script>
        // --- LOGIKA MENU MOBILE (SIDEBAR) ---
        
        // Mengambil elemen tombol hamburger dan menu sidebar dari halaman
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        
        // Memastikan kedua elemen tersebut ada sebelum menjalankan fungsi
        if (mobileMenuBtn && sidebar) {
            // Jika tombol diklik, tambahkan/hapus class 'active' untuk memunculkan/menyembunyikan sidebar
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }

        // Fungsi untuk menutup sidebar secara otomatis saat pengguna mengklik area di luar sidebar
        document.addEventListener('click', (e) => {
            // Logika ini hanya berjalan jika ukuran lebar layar di bawah 992px (ukuran tablet/HP)
            if (window.innerWidth <= 992) {
                // Jika area yang diklik bukan bagian dari sidebar dan bukan tombol menu itu sendiri
                if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    // Maka hapus class 'active' agar sidebar tertutup kembali
                    sidebar.classList.remove('active');
                }
            }
        });

        // --- LOGIKA NOTIFIKASI (ALERT) OTOMATIS HILANG ---
        
        // Mengambil elemen pesan alert (misal: "Data berhasil disimpan")
        const messageAlert = document.getElementById('messageAlert');
        if (messageAlert) {
            // Menjalankan fungsi setelah 5 detik (5000 milidetik)
            setTimeout(() => {
                // Memberikan efek transisi memudar (opacity) selama 0.3 detik
                messageAlert.style.transition = 'opacity 0.3s ease';
                messageAlert.style.opacity = '0'; // Mengubah tampilan menjadi transparan
                
                // Setelah transisi memudar selesai, hilangkan elemen sepenuhnya dari tata letak (display: none)
                setTimeout(() => {
                    messageAlert.style.display = 'none';
                }, 300);
            }, 5000);
        }

        // --- FUNGSI-FUNGSI PENDUKUNG (UTILITY) ---

        /**
         * Fungsi untuk mengubah satuan ukuran file (Bytes) menjadi format yang mudah dibaca.
         * Contoh: 1048576 Bytes diubah menjadi "1.00 MB"
         */
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024; // Standar perhitungan 1KB = 1024 Bytes
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            // Menentukan indeks satuan (Bytes, KB, MB, atau GB)
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            // Menghitung angka akhir dan menggabungkannya dengan satuannya
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        /**
         * Fungsi keamanan untuk mencegah Cross-Site Scripting (XSS).
         * Mengubah teks mentah menjadi entitas HTML agar tag script tidak bisa dieksekusi browser.
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Fungsi untuk mengubah format tanggal standar menjadi format lokal Indonesia.
         * Contoh: "2024-12-31" menjadi "31 Desember 2024 pukul 00:00"
         */
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        /**
         * Fungsi untuk menerjemahkan status teknis dari database menjadi bahasa manusia.
         * Contoh: status 'accepted' akan ditampilkan sebagai 'Diterima' di layar.
         */
        function getStatusLabel(status) {
            const labels = {
                'pending': 'Menunggu',
                'review': 'Dalam Review',
                'interview': 'Interview',
                'accepted': 'Diterima',
                'rejected': 'Ditolak',
                'active': 'Aktif',
                'inactive': 'Nonaktif'
            };
            // Jika status tidak terdaftar di atas, tampilkan teks asli status tersebut
            return labels[status] || status;
        }
    </script>
</body>
</html>