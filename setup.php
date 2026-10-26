<?php
require 'config/koneksi.php';

$username = "admin";
$password = "rabah"; 


$query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'admin')";

if (mysqli_query($conn, $query)) {
    echo "Akun admin berhasil dibuat! <br>";
    echo "Username: admin <br> Password: rabah <br>";
    echo "<a href='login.php'>Lanjut ke halaman Login</a>";
} else {
    echo "Gagal: " . mysqli_error($conn);
}
?>