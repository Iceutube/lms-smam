<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User tidak ditemukan.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $email = empty($email) ? null : $email;
    $role = $_POST['role'] ?? '';
    $class = ($role === 'student') ? ($_POST['class'] ?? null) : null;
    $subject = ($role === 'teacher') ? ($_POST['subject'] ?? null) : null;
    $nis = ($role === 'student') ? ($_POST['nis'] ?? null) : null;
    $password = $_POST['password'] ?? '';

    $sql = "UPDATE users SET name=?, email=?, role=?, class=?, subject=?, nis=?";
    $params = [$name, $email, $role, $class, $subject, $nis];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT); 
        $sql .= ", password=?";
        $params[] = $hashedPassword;
    }

    $sql .= " WHERE id=?";
    $params[] = $id;

    if (empty($name) || empty($role) || ($role === 'student' && empty($nis))) {
        $error = "Nama dan Role wajib diisi. Untuk siswa, NIS juga wajib diisi.";
    } else {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { 
                $error = "Gagal mengupdate user: Email atau NIS sudah digunakan.";
            } else {
                $error = "Gagal mengupdate user: " . htmlspecialchars($e->getMessage());
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
    <title>Edit User</title>
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
            <h2>Edit User</h2>
            
            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group"><label>Nama:</label><input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></div>
                <div class="form-group"><label>Email (opsional):</label><input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>"></div>
                <div class="form-group"><label>Password Baru (kosongkan jika tidak diganti):</label><input type="password" name="password"></label></div>
                
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" id="role" required>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="teacher" <?= $user['role'] === 'teacher' ? 'selected' : '' ?>>Guru</option>
                        <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Siswa</option>
                    </select>
                </div>
                
                <div id="additional-fields">
                    </div>
                
                <button type="submit">Simpan Perubahan</button>
            </form>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const currentUserRole = '<?= $user['role'] ?>';
        const currentUserClass = '<?= htmlspecialchars($user['class'] ?? '') ?>';
        const currentUserSubject = '<?= htmlspecialchars($user['subject'] ?? '') ?>';
        const currentUserNis = '<?= htmlspecialchars($user['nis'] ?? '') ?>';
        
        const classesOptions = `
            <option value="">-- Pilih Kelas --</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($class) ?></option>
            <?php endforeach; ?>
        `;
        
        const subjectsOptions = `
            <option value="">-- Pilih Mapel --</option>
            <?php foreach ($subjects as $subject): ?>
                <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
            <?php endforeach; ?>
        `;

        function toggleFields() {
            let role = document.getElementById("role").value;
            const fieldsDiv = document.getElementById('additional-fields');
            fieldsDiv.innerHTML = '';

            if (role === 'student') {
                fieldsDiv.innerHTML = `
                    <div class="form-group">
                        <label>NIS:</label>
                        <input type="text" name="nis" value="${currentUserNis}" required>
                    </div>
                    <div class="form-group">
                        <label>Kelas:</label>
                        <select name="class" class="select-search" required>
                            ${classesOptions}
                        </select>
                    </div>
                `;
                $('[name="class"]').val(currentUserClass);

            } else if (role === 'teacher') {
                fieldsDiv.innerHTML = `
                    <div class="form-group">
                        <label>Mata Pelajaran:</label>
                        <select name="subject" class="select-search" required>
                            ${subjectsOptions}
                        </select>
                    </div>
                `;
                $('[name="subject"]').val(currentUserSubject);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            toggleFields(); 
            document.getElementById('role').addEventListener('change', toggleFields);
        });
    </script>
</body>
</html>