<?php
include "config/database.php";

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $cpassword = md5($_POST['cpassword']);

    // Ambil koneksi PDO
    $db = getDatabase()->getConnection();

    // Cek apakah username sudah ada
    $stmt = $db->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        echo "<script>
        alert('Username ini telah digunakan! Mohon gunakan Username lain');
        </script>";
    } 
    // Cek apakah password dan konfirmasi sama
    else if ($password !== $cpassword) {
        echo "<script>
        alert('Password tidak sesuai! Pastikan kedua Password kamu sama');
        </script>";
    } 
    // Simpan user baru
    else {
        $sql = $db->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
        $sql->execute([$username, $password]);

        echo "<script>
        alert('Berhasil mendaftar! Silahkan masuk pada halaman Login');
        location.href='login.php';
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body style="background-color: whitesmoke;">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header">
                        <h3 class="text-center">Daftar</h3>
                        <p class="text-center">Ingin mendaftar? <br> Isi form berikut</p>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label for="Username" class="form-label">Username:</label>
                                <input type="text" id="Username" name="username" class="form-control" placeholder="Masukkan Username" required>
                            </div>
                            <div class="mb-3">
                                <label for="Password" class="form-label">Password:</label>
                                <input type="password" id="Password" name="password" class="form-control" placeholder="Masukkan Password" required>
                            </div>
                            <div class="mb-3">
                                <label for="CPassword" class="form-label">Konfirmasi Password:</label>
                                <input type="password" id="CPassword" name="cpassword" class="form-control" placeholder="Masukkan Ulang Password" required>
                            </div>
                            <div class="mb-3">
                                <input type="checkbox" class="form-check-input" id="agree" required>
                                <label class="form-check-label" for="agree">Saya menyetujui semua persyaratan</label>
                            </div>
                            <button type="submit" name="register" style="background-color: #4863A0;" class="btn text-light col-12">Daftar</button>
                        <p style="text-align: center;" class="mt-3">Sudah punya akun? <a href="./login.php">Masuk sekarang</a></p>
                        </form>
                    </div>

                    <div class="card-footer">
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
