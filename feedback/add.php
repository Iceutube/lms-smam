<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_anon = isset($_POST['anonymous']);
    $user_id = $is_anon ? null : $_SESSION['user_id'];
    $category = $_POST['category'];
    $message = $_POST['message'];

    $stmt = $pdo->prepare("INSERT INTO feedback (user_id, category, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $category, $message]);

    header("Location: ../dashboard/dashboard_" . $_SESSION['role'] . ".php?status=feedback_sent");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Style untuk checkbox kustom */
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: 500;
        }
        .checkbox-label input[type="checkbox"] {
            width: auto;
            height: auto;
            margin-right: 10px;
        }
    </style>
</head>
<body class="app-layout">

    <?php include '../layout/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <h2>Kirim Feedback</h2>
            <form method="post">
                <div class="form-group">
                    <label>Kategori:</label>
                    <select name="category" required>
                        <option value="app">Aplikasi</option>
                        <option value="school">Sekolah</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pesan:</label>
                    <textarea name="message" required></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="anonymous"> Kirim sebagai anonim
                    </label>
                </div>
                <button type="submit">Kirim</button>
            </form>
        </div>
    </main>

</body>
</html>