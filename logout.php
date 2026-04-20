<?php
session_start();

// 1. Kosongkan semua variabel session secara paksa
$_SESSION = array();

// 2. Hapus cookie bawaan server (PHPSESSID)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan rumah session-nya
session_destroy();

// 4. Bunuh 'cookie_email' dengan berbagai kemungkinan jalurnya
setcookie("cookie_email", "", time() - 3600);
setcookie("cookie_email", "", time() - 3600, "/");
setcookie("cookie_email", "", time() - 3600, "/", "", 0, 0);

// 5. Jurus pamungkas: Pindah halaman pakai kombinasi PHP Header & JavaScript
header("Location: login.php");
echo "<script>window.location.replace('login.php');</script>";
exit;
?>