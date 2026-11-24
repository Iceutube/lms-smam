<?php
session_start();
if (!in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT name FROM subjects ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$isTeacher = ($_SESSION['role'] === 'teacher');
$teacherSubject = $isTeacher ? ($_SESSION['subject'] ?? null) : null;
$upload_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $subject = $isTeacher ? $teacherSubject : $_POST['subject'];
    $created_by = $_SESSION['user_id'];
    $selected_classes = $_POST['class_codes'] ?? [];
    $file_url = null;
    $link_url = $_POST['link_url'] ?? null;
    if (empty($selected_classes)) {
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
                $original_filename = basename($_FILES['file_upload']['name']);
                $file_name = $original_filename;
                $upload_path = $upload_dir . $file_name;
                $counter = 1;
                while (file_exists($upload_path)) {
                    $filename_without_ext = pathinfo($original_filename, PATHINFO_FILENAME);
                    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
                    $file_name = $filename_without_ext . '_' . $counter++ . '.' . $extension;
                    $upload_path = $upload_dir . $file_name;
                }
                if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $upload_path)) {
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
                $stmt = $pdo->prepare("INSERT INTO materials (title, description, file_url, link_url, subject, created_by) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $file_url, $link_url, $subject, $created_by]);
                $material_id = $pdo->lastInsertId();
                $stmt_class = $pdo->prepare("INSERT INTO material_classes (material_id, class_name) VALUES (?, ?)");
                foreach ($selected_classes as $class_name) {
                    $stmt_class->execute([$material_id, $class_name]);
                }
                $pdo->commit();
                header("Location: list.php");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $upload_error = "Gagal menyimpan materi: " . $e->getMessage();
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
    <title>Tambah Materi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <a href="list.php" class="back-link">&larr; Kembali ke Daftar Materi</a>
            <h2>Tambah Materi</h2>
            <?php if ($upload_error): ?>
                <p class="error"><?= htmlspecialchars($upload_error) ?></p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group"><label>Materi: <input type="text" name="title" required></label></div>
                <div class="form-group"><label>Sub Materi: <textarea name="description"></textarea></label></div>
                <div class="form-group"><label>Unggah File (PDF/PPT): <input type="file" name="file_upload" accept=".pdf,.ppt,.pptx"></label></div>
                <div class="form-group"><label>Atau Link URL (opsional): <input type="text" name="link_url" placeholder="https://contoh.com"></label></div>
                <div class="form-group">
                    <label>Ditujukan ke Kelas (bisa pilih lebih dari satu):</label>
                    <div class="checkbox-group-container">
                        <?php foreach ($classes as $c): ?>
                            <label>
                                <input type="checkbox" name="class_codes[]" value="<?= htmlspecialchars($c) ?>">
                                <?= htmlspecialchars($c) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mata Pelajaran:</label>
                    <select name="subject" <?= $isTeacher ? 'disabled' : '' ?> required>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>" <?= ($isTeacher && $s === $teacherSubject) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isTeacher): ?>
                        <input type="hidden" name="subject" value="<?= htmlspecialchars($teacherSubject) ?>">
                    <?php endif; ?>
                </div>
                <button type="submit">Simpan</button>
            </form>
        </div>
    </main>
</body>
</html>