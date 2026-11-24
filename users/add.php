<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $email = empty($email) ? null : $email; 
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $class = ($role === 'student') ? ($_POST['class'] ?? null) : null;
    $subject = ($role === 'teacher') ? ($_POST['subject'] ?? null) : null;
    $nis = ($role === 'student') ? ($_POST['nis'] ?? null) : null;

    if (empty($name) || empty($password) || empty($role) || ($role === 'student' && (empty($class) || empty($nis)))) {
        $error = "Semua field wajib diisi. Untuk siswa, NIS dan Kelas juga wajib diisi.";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, class, subject, nis) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $role, $class, $subject, $nis]);
            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { 
                $error = "Gagal menambah user: Email atau NIS sudah digunakan.";
            } else {
                $error = "Gagal menambah user: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

$classes = $pdo->query("SELECT name FROM classes")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT name FROM subjects")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 12px); 
            padding: 10px 12px;
            border-color: var(--color-border);
            border-radius: var(--border-radius);
        }
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 10px);
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0;
        }
    </style>
</head>
<body class="app-layout">

    <?php include '../layout/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <a href="list.php" class="back-link">&larr; Kembali ke Daftar User</a>
            <h2>Tambah User Baru</h2>
            
            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group"><label>Nama:</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Email (opsional):</label><input type="email" name="email"></div>
                <div class="form-group"><label>Password:</label><input type="password" name="password" required></div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" id="role" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Guru</option>
                        <option value="student">Siswa</option>
                    </select>
                </div>
                
                <div id="additional-fields">
                </div>
                
                <button type="submit">Simpan</button>
            </form>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function toggleFields() {
            let role = document.getElementById("role").value;
            const fieldsDiv = document.getElementById('additional-fields');
            fieldsDiv.innerHTML = ''; 

            if (role === 'student') {
                fieldsDiv.innerHTML = `
                    <div class="form-group">
                        <label>NIS:</label>
                        <input type="text" name="nis" required>
                    </div>
                    <div class="form-group">
                        <label>Kelas:</label>
                        <select name="class" class="select-search" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                `;
            } else if (role === 'teacher') {
                fieldsDiv.innerHTML = `
                    <div class="form-group">
                        <label>Mata Pelajaran:</label>
                        <select name="subject" class="select-search" required>
                            <option value="">-- Pilih Mapel --</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                `;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            toggleFields(); 
            document.getElementById('role').addEventListener('change', toggleFields);
        });
    </script>
</body>
</html>