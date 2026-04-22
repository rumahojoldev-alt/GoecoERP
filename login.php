<?php
session_start();
include 'koneksi_db.php';
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];

    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email' AND password='$password'");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        
        // Simpan data di Session (Memori sementara)
        $_SESSION['user_login'] = true;
        $_SESSION['nama_user'] = $data['nama'];
        $_SESSION['role_user'] = $data['role'];

        // Jika Checkbox "Ingat Saya" dicentang, buat Cookie (Memori jangka panjang, misal 30 hari)
        if (isset($_POST['remember'])) {
            setcookie('cookie_email', $data['email'], time() + (86400 * 30), "/"); 
        }

        header("Location: index.php");
        exit;
    } else {
        $error = "Email atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login - GOECO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card border-0 shadow-lg p-4" style="width: 350px; border-radius: 15px;">
        <h3 class="fw-bold text-center mb-4" style="color: #10b981;">🚴 GOECO</h3>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger small py-2 text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="small fw-bold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="nama@lisgo.com" required>
            </div>
            <div class="mb-3">
                <label class="small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="*****" required>
            </div>
            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="rememberMe" checked>
                <label class="form-check-label small text-muted" for="rememberMe">Ingat Saya (Tetap Login)</label>
            </div>
            <button type="submit" name="login" class="btn w-100 fw-bold text-white" style="background-color: #10b981; border-radius: 8px;">LOGIN</button>
        </form>
    </div>
</body>
</html>