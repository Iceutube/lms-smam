<?php
session_start();
if (!in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$isTeacher = ($_SESSION['role'] === 'teacher');
$teacherSubject = $isTeacher ? ($_SESSION['subject'] ?? null) : null;
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT name FROM subjects ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$error = '';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $subject = $isTeacher ? $teacherSubject : $_POST['subject'];
    $created_by = $_SESSION['user_id'];
    $quiz_type = $_POST['quiz_type'];
    $external_link = ($quiz_type === 'external') ? $_POST['external_link'] : null;
    $duration = ($quiz_type === 'internal') ? $_POST['duration'] : 0;
    $selected_classes = $_POST['class_codes'] ?? [];
    if (empty($selected_classes)) {
        $error = "Anda harus memilih setidaknya satu kelas.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                "INSERT INTO quizzes (title, description, subject, duration, created_by, quiz_type, external_link) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$title, $description, $subject, $duration, $created_by, $quiz_type, $external_link]);
            $quiz_id = $pdo->lastInsertId();
            $stmt_class = $pdo->prepare("INSERT INTO quiz_classes (quiz_id, class_name) VALUES (?, ?)");
            foreach ($selected_classes as $class_name) {
                $stmt_class->execute([$quiz_id, $class_name]);
            }
            $pdo->commit();

            if ($quiz_type === 'internal') {
                header("Location: edit.php?id=$quiz_id&new=1");
            } else {
                header("Location: list.php?status=added_link");
            }
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal membuat kuis: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kuis Baru</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .quiz-type-selector { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .quiz-type-selector label { flex: 1; padding: 1.5rem; border: 2px solid var(--color-border); border-radius: var(--border-radius); cursor: pointer; text-align: center; transition: all 0.2s ease; }
        .quiz-type-selector label i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }
        .quiz-type-selector input[type="radio"] { display: none; }
        .quiz-type-selector input[type="radio"]:checked + label { background-color: #E8F0FE; border-color: var(--color-primary); color: var(--color-primary); }
    </style>
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <a href="list.php" class="back-link">&larr; Kembali ke Daftar Kuis</a>
            <h2>Tambah Kuis Baru</h2>
            <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Tipe Kuis:</label>
                    <div class="quiz-type-selector">
                        <input type="radio" id="type_internal" name="quiz_type" value="internal" checked onchange="toggleQuizType()">
                        <label for="type_internal"><i class="fas fa-tasks"></i> Kuis Internal</label>
                        <input type="radio" id="type_external" name="quiz_type" value="external" onchange="toggleQuizType()">
                        <label for="type_external"><i class="fas fa-link"></i> Kuis Eksternal</label>
                    </div>
                </div>
                <div class="form-group"><label>Judul Kuis: <input type="text" name="title" required></label></div>
                <div class="form-group"><label>Deskripsi: <textarea name="description"></textarea></label></div>
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
                    <?php if ($isTeacher): ?><input type="hidden" name="subject" value="<?= htmlspecialchars($teacherSubject) ?>"><?php endif; ?>
                </div>
                <div id="internal-quiz-fields">
                    <div class="form-group"><label>Durasi (menit): <input type="number" name="duration" value="60"></label></div>
                </div>
                <div id="external-quiz-fields" style="display: none;">
                    <div class="form-group"><label>Link Kuis Eksternal:</label><input type="text" name="external_link" placeholder="https_//forms.gle/contohLinkAnda"></label></div>
                </div>
                <button type="submit">Buat Kuis</button>
            </form>
        </div>
    </main>
    <script>
    function toggleQuizType() {
        const type = document.querySelector('input[name="quiz_type"]:checked').value;
        const internalFields = document.getElementById('internal-quiz-fields');
        const externalFields = document.getElementById('external-quiz-fields');
        if (type === 'internal') {
            internalFields.style.display = 'block';
            externalFields.style.display = 'none';
        } else {
            internalFields.style.display = 'none';
            externalFields.style.display = 'block';
        }
    }
    </script>
</body>
</html>