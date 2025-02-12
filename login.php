<?php
session_start();
include 'includes/config.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM karyawan WHERE email = '$email' AND role = 'karyawan'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $karyawan = $result->fetch_assoc();
        if (password_verify($password, $karyawan['password'])) {
            $_SESSION['karyawan_id'] = $karyawan['id'];
            header('Location: absensi.php');
            exit();
        } else {
            $error_message = "Password salah!";
        }
    } else {
        $error_message = "Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absensi</title>
    <link rel="stylesheet" href="css/login/login.css">
    <script src="js/login.js" defer></script>
</head>
<body>
    <div class="container">
        <h1>Login Karyawan</h1>

        <?php if (isset($error_message)): ?>
            <p class="message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>
