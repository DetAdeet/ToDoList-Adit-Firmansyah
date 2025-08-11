<?php
include("config/database.php");
error_reporting(0);
session_start();


if (isset($_POST['submit'])) {
    $Username = $_POST['Username'];
    $Password = md5($_POST['Password']);
    $sql = "SELECT * FROM user WHERE Username = '$Username' AND Password = '$Password'";
    $result = mysqli_query($koneksi, $sql);
    if ($result->num_rows > 0) {
       $row = mysqli_fetch_assoc($result);

       $_SESSION['Username'] = $row['Username'];
       header("Location: home.php");

    } else {
        echo "<script>alert('Username atau Password anda salah')(</script>";
    }
    
} else {
    # code...
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body style="background-color: whitesmoke;">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow" style="background-color: whitesmoke;">
                    <div class="card-header">
                        <h3 class="text-center">Login</h3>
                        
                    </div>
                    <div class="card-body">
                        <form action="home.php" method="post">
                            <div class="mb-3">
                                <label for="Username" class="form-label">Username:</label>
                                <input type="text" id="Username" name="Username" class="form-control" placeholder="Masukkan Username" required>
                            </div>
                            <div class="mb-3">
                                <label for="Password" class="form-label">Password:</label>
                                <input type="password" id="Password" name="Password" class="form-control" placeholder="Masukkan Password" required>
                            </div>
                            <button type="submit" name="submit" style="background-color: #4863A0;" class="btn text-light col-12">Login</button>
                        </form>
                    </div>
                    
                    <div class="card-footer">
                        
                        <p style="text-align: center;" class="mt-3">Belum Punya Account? <a href="register.php">Register</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>