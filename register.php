<?php
include 'includes/config.php';

$message = "";

if (isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = "user";

    // Validasi sederhana
    if (empty($username) || empty($password)) {
        $message = "Username dan password wajib diisi!";
    } else {

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Cek username sudah ada atau belum
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $message = "Username sudah digunakan!";
        } else {

            // Insert user baru
            $query = "INSERT INTO users (username, password, role)
                      VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $username,
                $hashed_password,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {
                $message = "Registrasi berhasil! <a href='login.php'>Login</a>";
            } else {
                $message = "Registrasi gagal, silakan coba lagi.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Akun</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            display: flex;
            justify-content: center;
            padding-top: 60px;
        }
        .card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            width: 320px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 15px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #27ae60;
        }
        .msg {
            color: #e74c3c;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Daftar Akun</h2>

    <?php if ($message) echo "<p class='msg'>$message</p>"; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="register">Daftar</button>
    </form>

    <p style="font-size: 13px; text-align:center;">
        Sudah punya akun? <a href="login.php">Login</a>
    </p>
</div>

</body>
</html>
