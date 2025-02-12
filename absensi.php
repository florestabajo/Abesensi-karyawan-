<?php
session_start();
include 'includes/config.php';

// Pastikan karyawan sudah login
if (!isset($_SESSION['karyawan_id'])) {
    header('Location: login.php'); // Jika belum login, arahkan ke halaman login
    exit();
}

$karyawan_id = $_SESSION['karyawan_id'];

// Query untuk mengambil data karyawan berdasarkan karyawan_id
$sql_karyawan = "SELECT nama, image FROM karyawan WHERE id = '$karyawan_id'";
$result_karyawan = $conn->query($sql_karyawan);
$karyawan = $result_karyawan->fetch_assoc();
$karyawan_name = $karyawan['nama']; // Nama karyawan yang login
$gambar_foto = $karyawan['image']; // Foto profil karyawan

// Menangani aksi absensi
if (isset($_POST['absen'])) {
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];
    $gambar_izin = "";

    // Cek apakah karyawan sudah absen pada hari ini
    $sql_check_absen_today = "SELECT * FROM absensi WHERE karyawan_id = '$karyawan_id' AND DATE(waktu_absen) = CURDATE()";
    $result_check_absen = $conn->query($sql_check_absen_today);

    if ($result_check_absen->num_rows > 0) {
        // Jika sudah hadir, dan absensi sudah ada, maka update jam pulang
        $absen = $result_check_absen->fetch_assoc();
        if (is_null($absen['waktu_pulang']) && $status == 'hadir') {
            $waktu_pulang = date('Y-m-d H:i:s');
            $sql_update_pulang = "UPDATE absensi SET waktu_pulang = '$waktu_pulang' WHERE id = '{$absen['id']}'";
            if ($conn->query($sql_update_pulang) === TRUE) {
                $message = "<p class='message success'>Jam pulang berhasil dicatat!</p>";
            } else {
                $message = "<p class='message error'>Error: " . $conn->error . "</p>";
            }
        } else {
            $message = "<p class='message error'>Anda sudah melakukan absensi hari ini!</p>";
        }
    } else {
        // Cek jika status izin, pastikan keterangan diisi
        if ($status == 'izin') {
            if (empty($keterangan)) {
                $message = "<p class='message error'>Keterangan wajib diisi jika status absensi adalah Izin!</p>";
            } elseif (isset($_FILES['gambar_izin']) && $_FILES['gambar_izin']['error'] == 0) {
                $gambar_izin = "images/" . basename($_FILES['gambar_izin']['name']);
                move_uploaded_file($_FILES['gambar_izin']['tmp_name'], $gambar_izin);
            } else {
                $message = "<p class='message error'>Gambar izin wajib diupload!</p>";
            }
        }

        // Jika tidak ada error, simpan absensi
        if (!isset($message)) {
            $sql = "INSERT INTO absensi (karyawan_id, status, keterangan, gambar_izin) VALUES ('$karyawan_id', '$status', '$keterangan', '$gambar_izin')";
            if ($conn->query($sql) === TRUE) {
                $message = "<p class='message success'>Absensi berhasil!</p>";
            } else {
                $message = "<p class='message error'>Error: " . $conn->error . "</p>";
            }
        }
    }
}

$sql_last_absen = "SELECT status, keterangan, gambar_izin, waktu_absen, waktu_pulang FROM absensi WHERE karyawan_id = '$karyawan_id' ORDER BY waktu_absen DESC LIMIT 1";
$result_last_absen = $conn->query($sql_last_absen);
$last_absen = null;
if ($result_last_absen->num_rows > 0) {
    $last_absen = $result_last_absen->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Karyawan</title>
    <link rel="stylesheet" href="css/absesnsi.css">
    <script src="js/scripts.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>Selamat datang, <?php echo $karyawan_name; ?>!</h1>
            <section class="profile">
                <div class="profile-container">
                    <?php if ($gambar_foto): ?>
                        <img src="uploads/<?php echo $gambar_foto; ?>" alt="Foto Profil" class="profile-image">
                    <?php else: ?>
                        <p>Foto profil tidak tersedia.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <nav>
            <ul>
                <li><a href="absensi.php"><i class="fas fa-calendar-check"></i> Absensi</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <h2>Form Absensi</h2>
            <form method="post" action="absensi.php" enctype="multipart/form-data">
                <select name="status" required>
                    <option value="hadir">Hadir</option>
                    <option value="izin">Izin</option>
                </select>
                <textarea name="keterangan" placeholder="Tulis keterangan jika izin..." rows="3"></textarea>
                <input type="file" name="gambar_izin">
                <button type="submit" name="absen"><i class="fas fa-check-circle"></i> Absen</button>
            </form>

            <?php if (isset($message)) echo $message; ?>
        </section>

        <?php if ($last_absen): ?>
        <section class="last-absen">
            <h3>Absensi Terakhir</h3>
            <p>Status: <?php echo ucfirst($last_absen['status']); ?></p>
            <p>Keterangan: <?php echo $last_absen['keterangan'] ? $last_absen['keterangan'] : '-'; ?></p>
            <p>Waktu Absen: <?php echo $last_absen['waktu_absen']; ?></p>
            <?php if ($last_absen['waktu_pulang']): ?>
                <p>Jam Pulang: <?php echo $last_absen['waktu_pulang']; ?></p>
            <?php endif; ?>
            <?php if ($last_absen['gambar_izin']): ?>
                <p>Gambar Izin: <a href="<?php echo $last_absen['gambar_izin']; ?>" target="_blank">Lihat Gambar</a></p>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
