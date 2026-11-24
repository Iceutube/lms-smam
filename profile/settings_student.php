<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($email)) {
        $error = 'Email tidak boleh kosong.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $error = 'Format email tidak valid.';
    } elseif (!empty($new_password) && ($new_password !== $confirm_password)) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        try {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
                $stmt->execute([$email, $hashed_password, $userId]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $userId]);
            }
            $message = 'Profil berhasil diperbarui!';
        } catch (PDOException $e) {
            $error = "Gagal memperbarui profil: " . $e->getMessage();
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT nis, name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Data pengguna tidak ditemukan.");
    }
} catch (PDOException $e) {
    die("Gagal mengambil data pengguna: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Style khusus untuk flash message, bisa dipindah ke style.css jika mau */
        .message { background-color: #d4edda; color: #155724; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1rem; }
    </style>
</head>
<body class="app-layout">

    <?php include '../layout/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <a href="../dashboard/dashboard_student.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
            <h2>Pengaturan Akun</h2>

            <?php if ($message): ?>
                <div class="message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="settings_student.php" method="POST">
                <div class="form-group">
                    <label for="nis">NIS</label>
                    <input type="text" id="nis" name="nis" value="<?= htmlspecialchars($user['nis'] ?? '') ?>" readonly style="background-color: #f0f2f5; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" readonly style="background-color: #f0f2f5; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--color-border);">
                
                <p style="text-align: center; color: var(--color-text-light);">Kosongkan jika tidak ingin mengubah password.</p>
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                <button type="submit">Simpan Perubahan</button>
            </form>
        </div>
    </main>

</body>
</html>