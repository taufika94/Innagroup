<?php
// 1. Memulai sesi PHP agar kita bisa menyimpan data login pengguna di seluruh halaman
session_start();

// 2. Mengambil file koneksi database
include 'includes/config.php';

// 3. Mengecek apakah tombol login sudah ditekan
if (isset($_POST['login'])) {
    // Menampung data yang dikirim dari form
    $username = $_POST['username'];
    $password = $_POST['password'];

    /* CATATAN PENTING: 
       Baris $_SESSION['user_id'] = $user_data['id'] di bagian atas kode asli Anda 
       sebaiknya dihapus karena variabel $user_data belum didefinisikan sebelum query.
    */

    // 4. Mencari user di database berdasarkan username yang diinput
    // Tips Keamanan: Sebaiknya gunakan Prepared Statements seperti pada register.php
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    
    // 5. Jika user ditemukan (jumlah baris == 1)
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result); // Mengambil data user hasil query
        
        // 6. VERIFIKASI PASSWORD: Membandingkan password input dengan password hash di database
        if (password_verify($password, $row['password'])) {
            
            // 7. MENYIMPAN DATA KE SESI: Data ini akan dibawa ke halaman lain
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['user_id'] = $row['id']; // Mengambil ID langsung dari baris database ($row)

            // 8. REDIRECT BERDASARKAN ROLE: 
            // Jika admin ke folder admin, jika user ke folder user
            if ($row['role'] == "admin") {
                header("Location: admin/admin_dashboard.php");
            } else {
                header("Location: user/user_dashboard.php");
            }
            exit; // Menghentikan skrip setelah redirect
        }
    }
    
    // 9. Jika username tidak ditemukan atau password salah, set variabel error
    $error = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - PT INTIM</title>
    <style>
        /* CSS untuk mempercantik tampilan login */
        body {
            font-family: Arial, sans-serif;
            /* Membuat background gradasi merah marun */
            background: linear-gradient(135deg, #800000, #a52a2a);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            width: 350px;
        }
        h2 { text-align: center; color: #800000; margin-bottom: 30px; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box; /* Agar padding tidak merusak lebar input */
        }
        button {
            width: 100%;
            padding: 12px;
            background: #800000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover { background: #a52a2a; }
        .error { color: red; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login PT INTIM</h2>
        
        <?php if(isset($error)): ?>
            <div class="error">Username/Password Salah!</div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Masuk</button>
        </form>
        
        <div class="register-link">
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
        </div>
    </div>
</body>
</html>