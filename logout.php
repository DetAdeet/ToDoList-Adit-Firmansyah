<?php
session_start();
session_destroy();
session_unset();

echo "<script>
alert('Logout Berhasil');
location.href='login.php';
</script>";

?>