<?php
require '../includes/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR nis = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['class'] = $user['class'] ?? null;
        $_SESSION['subject'] = $user['subject'] ?? null;
        if ($user['role'] === 'admin') {
            header("Location: ../dashboard/dashboard_admin.php");
        } elseif ($user['role'] === 'teacher') {
            header("Location: ../dashboard/dashboard_teacher.php");
        } else {
            header("Location: ../dashboard/dashboard_student.php");
        }
        exit;
    }

    $error = "Email/NIS atau password salah.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke LMS SMAM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">
    <main class="login-container">
        <div class="login-brand">
            <img src="../assets/img/Logo_Smam.png" alt="Logo SMAM">
            <h1>LMS SMAM</h1>
            <p>Masuk untuk mengakses ruang belajar Anda.</p>
        </div>

        <?php if ($error): ?>
            <div class="error" role="alert"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="identifier">Email atau NIS</label>
                <input type="text" id="identifier" name="identifier" autocomplete="username" placeholder="Masukkan email atau NIS" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required>
                    <button class="password-toggle" type="button" aria-label="Tampilkan password" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                </div>
            </div>
            <button type="submit"><i class="fas fa-right-to-bracket"></i> Masuk</button>
        </form>
    </main>
    <script>
    const passwordToggle = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('password');
    passwordToggle.addEventListener('click', () => {
        const showingPassword = passwordInput.type === 'text';
        passwordInput.type = showingPassword ? 'password' : 'text';
        passwordToggle.setAttribute('aria-pressed', String(!showingPassword));
        passwordToggle.setAttribute('aria-label', showingPassword ? 'Tampilkan password' : 'Sembunyikan password');
        passwordToggle.innerHTML = `<i class="fas ${showingPassword ? 'fa-eye' : 'fa-eye-slash'}" aria-hidden="true"></i>`;
    });
    </script>
</body>
</html>
