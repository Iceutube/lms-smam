<?php
session_start();
if (!in_array($_SESSION['role'], ['admin','teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id=?");
$stmt->execute([$id]);
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$announcement) {
    die("Pengumuman tidak ditemukan!");
}
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$stmt_selected_classes = $pdo->prepare("SELECT class_name FROM announcement_classes WHERE announcement_id = ?");
$stmt_selected_classes->execute([$id]);
$selected_classes = $stmt_selected_classes->fetchAll(PDO::FETCH_COLUMN);
$is_for_all = in_array("Semua", $selected_classes);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $body = $_POST['body'];
    $new_selected_classes = $_POST['class_codes'] ?? [];
    if (empty($new_selected_classes)) {
        $error = "Anda harus memilih setidaknya satu kelas, atau 'Semua Kelas'.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt_update = $pdo->prepare("UPDATE announcements SET title=?, body=? WHERE id=?");
            $stmt_update->execute([$title, $body, $id]);
            $pdo->prepare("DELETE FROM announcement_classes WHERE announcement_id = ?")->execute([$id]);
            $stmt_class = $pdo->prepare("INSERT INTO announcement_classes (announcement_id, class_name) VALUES (?, ?)");
            if (in_array("Semua", $new_selected_classes)) {
                $stmt_class->execute([$id, "Semua"]);
            } else {
                foreach ($new_selected_classes as $class_name) {
                    if ($class_name !== "Semua") {
                        $stmt_class->execute([$id, $class_name]);
                    }
                }
            }
            $pdo->commit();
            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal memperbarui pengumuman: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengumuman</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <a href="list.php" class="back-link">&larr; Kembali ke Daftar</a>
            <h2>Edit Pengumuman</h2>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="post">
                <div class="form-group"><label>Kegiatan:</label><input type="text" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" required></div>
                <div class="form-group"><label>Isi:</label><textarea name="body" required><?= htmlspecialchars($announcement['body']) ?></textarea></div>            
                <div class="form-group">
                    <label>Ditujukan ke:</label>
                    <div class="checkbox-group-container">
                        <label>
                            <input type="checkbox" name="class_codes[]" value="Semua" onchange="toggleAll(this)" <?= $is_for_all ? 'checked' : '' ?>>
                            <strong>Semua Kelas</strong>
                        </label>
                        <hr style="border: none; border-top: 1px solid var(--color-border); margin: 5px 0;">
                        <?php foreach ($classes as $c): ?>
                            <label>
                                <input type="checkbox" name="class_codes[]" value="<?= htmlspecialchars($c) ?>" class="class-checkbox"
                                    <?= in_array($c, $selected_classes) ? 'checked' : '' ?>
                                    <?= $is_for_all ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit">Update</button>
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
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.querySelector('input[value="Semua"]');
        if (selectAll.checked) {
            toggleAll(selectAll);
        }
    });
    </script>
</body>
</html>