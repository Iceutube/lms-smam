<?php
session_start();
if (!in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM materials WHERE id=?");
$stmt->execute([$id]);
$material = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$material) {
    die("Materi tidak ditemukan!");
}
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT name FROM subjects ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$stmt_selected_classes = $pdo->prepare("SELECT class_name FROM material_classes WHERE material_id = ?");
$stmt_selected_classes->execute([$id]);
$selected_classes = $stmt_selected_classes->fetchAll(PDO::FETCH_COLUMN);
$upload_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $link_url = $_POST['link_url'] ?? null;
    $subject = $_POST['subject'] ?? $material['subject'];
    $new_selected_classes = $_POST['class_codes'] ?? [];
    $file_url = $material['file_url'];
    if (empty($new_selected_classes)) {
        $upload_error = "Anda harus memilih setidaknya satu kelas.";
    } else {
        if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
            $allowed_types = ['application/pdf', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
            $file_type = mime_content_type($_FILES['file_upload']['tmp_name']);
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_name = uniqid('file_', true) . '.' . pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
                $upload_path = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $upload_path)) {
                    if ($file_url && file_exists('../' . $file_url)) {
                        unlink('../' . $file_url);
                    }
                    $file_url = 'uploads/' . $file_name;
                } else {
                    $upload_error = "Gagal memindahkan file yang diunggah.";
                }
            } else {
                $upload_error = "Tipe file tidak diizinkan. Hanya PDF dan PPT.";
            }
        }
        if (!$upload_error) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE materials 
                                        SET title=?, description=?, file_url=?, link_url=?, subject=? 
                                        WHERE id=?");
                $stmt->execute([$title, $description, $file_url, $link_url, $subject, $id]);
                $pdo->prepare("DELETE FROM material_classes WHERE material_id = ?")->execute([$id]);
                $stmt_class = $pdo->prepare("INSERT INTO material_classes (material_id, class_name) VALUES (?, ?)");
                foreach ($new_selected_classes as $class_name) {
                    $stmt_class->execute([$id, $class_name]);
                }
                $pdo->commit();
                header("Location: list.php");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $upload_error = "Gagal memperbarui materi: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Materi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <a href="list.php" class="back-link">&larr; Kembali ke Daftar Materi</a>
            <h2>Edit Materi</h2>
            <?php if ($upload_error): ?>
                <p class="error"><?= htmlspecialchars($upload_error) ?></p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group"><label>Judul: <input type="text" name="title" value="<?= htmlspecialchars($material['title']) ?>" required></label></div>
                <div class="form-group"><label>Deskripsi: <textarea name="description"><?= htmlspecialchars($material['description']) ?></textarea></label></div>
                <div class="form-group"><label>Unggah File Baru (PDF/PPT): <input type="file" name="file_upload" accept=".pdf,.ppt,.pptx"></label></div>
                <div class="form-group"><label>Link URL (opsional): <input type="text" name="link_url" value="<?= htmlspecialchars($material['link_url']) ?>" placeholder="https://contoh.com"></label></div>
                <div class="form-group">
                    <label>Ditujukan ke Kelas (bisa pilih lebih dari satu):</label>
                    <div class="checkbox-group-container">
                        <?php foreach ($classes as $c): ?>
                            <label>
                                <input type="checkbox" name="class_codes[]" value="<?= htmlspecialchars($c) ?>" <?= in_array($c, $selected_classes) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mata Pelajaran:</label>
                    <select name="subject" required>
                        <?php foreach ($subjects as $subj): ?>
                            <option value="<?= htmlspecialchars($subj) ?>" <?= $material['subject'] == $subj ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subj) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Update</button>
            </form>
        </div>
    </main>
</body>
</html>