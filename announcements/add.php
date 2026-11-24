<?php
session_start();
if (!in_array($_SESSION['role'], ['admin','teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $body = $_POST['body'];
    $created_by = $_SESSION['user_id'];
    $selected_classes = $_POST['class_codes'] ?? [];
    if (empty($selected_classes)) {
        $error = "Anda harus memilih setidaknya satu kelas, atau 'Semua Kelas'.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO announcements (title, body, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$title, $body, $created_by]);
            $announcement_id = $pdo->lastInsertId();
            $stmt_class = $pdo->prepare("INSERT INTO announcement_classes (announcement_id, class_name) VALUES (?, ?)");
            if (in_array("Semua", $selected_classes)) {
                $stmt_class->execute([$announcement_id, "Semua"]);
            } else {
                foreach ($selected_classes as $class_name) {
                    if ($class_name !== "Semua") {
                        $stmt_class->execute([$announcement_id, $class_name]);
                    }}  }
            $pdo->commit();
            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal menyimpan pengumuman: " . $e->getMessage();
        }}}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengumuman</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <a href="list.php" class="back-link">&larr; Kembali ke Daftar</a>
            <h2>Tambah Pengumuman</h2>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="post">
                <div class="form-group"><label>Kegiatan:</label><input type="text" name="title" required></div>
                <div class="form-group"><label>Isi:</label><textarea name="body" required></textarea></div>
                <div class="form-group">
                    <label>Ditujukan ke:</label>
                    <div class="checkbox-group-container">
                        <label>
                            <input type="checkbox" name="class_codes[]" value="Semua" onchange="toggleAll(this)">
                            <strong>Semua Kelas</strong>
                        </label>
                        <hr style="border: none; border-top: 1px solid var(--color-border); margin: 5px 0;">
                        <?php foreach ($classes as $c): ?>
                            <label>
                                <input type="checkbox" name="class_codes[]" value="<?= htmlspecialchars($c) ?>" class="class-checkbox">
                                <?= htmlspecialchars($c) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit">Simpan</button>
            </form>
        </div>
    </main>
    <script>
    function toggleAll(source) {
        const checkboxes = document.querySelectorAll('.class-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = source.checked;
            checkbox.disabled = source.checked;
        });
    }
    </script>
</body>
</html>