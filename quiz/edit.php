<?php
session_start();
if (!in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$quiz_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$error = '';
$message = '';
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) {
    die("Kuis tidak ditemukan.");}
if ($_SESSION['role'] === 'teacher' && $quiz['created_by'] != $user_id) {
    die("Akses ditolak. Anda bukan pemilik kuis ini.");}
$is_external = ($quiz['quiz_type'] === 'external');
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$stmt_selected_classes = $pdo->prepare("SELECT class_name FROM quiz_classes WHERE quiz_id = ?");
$stmt_selected_classes->execute([$quiz_id]);
$selected_classes = $stmt_selected_classes->fetchAll(PDO::FETCH_COLUMN);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quiz'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $new_selected_classes = $_POST['class_codes'] ?? [];  
    if (empty($new_selected_classes)) {
        $error = "Anda harus memilih setidaknya satu kelas.";
    } else {
        try {
            $pdo->beginTransaction();
            if ($is_external) {
                $external_link = $_POST['external_link'];
                $stmt_update = $pdo->prepare("UPDATE quizzes SET title = ?, description = ?, external_link = ? WHERE id = ?");
                $stmt_update->execute([$title, $description, $external_link, $quiz_id]);
            } else {
                $duration = $_POST['duration'];
                $stmt_update = $pdo->prepare("UPDATE quizzes SET title = ?, description = ?, duration = ? WHERE id = ?");
                $stmt_update->execute([$title, $description, $duration, $quiz_id]);
            }
            $pdo->prepare("DELETE FROM quiz_classes WHERE quiz_id = ?")->execute([$quiz_id]);
            $stmt_class = $pdo->prepare("INSERT INTO quiz_classes (quiz_id, class_name) VALUES (?, ?)");
            foreach ($new_selected_classes as $class_name) {
                $stmt_class->execute([$quiz_id, $class_name]);
            }
            $pdo->commit();
            $message = "Detail kuis berhasil diperbarui.";
            $stmt->execute([$quiz_id]);
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt_selected_classes->execute([$quiz_id]);
            $selected_classes = $stmt_selected_classes->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal memperbarui kuis: " . $e->getMessage();
        }
    }
}
if (!$is_external) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
        $question_text = $_POST['question_text']; $option_a = $_POST['option_a']; $option_b = $_POST['option_b']; $option_c = $_POST['option_c']; $option_d = $_POST['option_d']; $correct_answer = $_POST['correct_answer'];
        try {
            $stmt_add_q = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_add_q->execute([$quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer]);
            $message = "Pertanyaan baru berhasil ditambahkan.";
        } catch (PDOException $e) { $error = "Gagal menambahkan pertanyaan: " . $e->getMessage(); }
    }
    if (isset($_GET['delete_question'])) {
        $question_id_to_delete = $_GET['delete_question'];
        try {
            $stmt_del_q = $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
            $stmt_del_q->execute([$question_id_to_delete, $quiz_id]);
            header("Location: edit.php?id=$quiz_id&message=del_ok"); exit;
        } catch (PDOException $e) { $error = "Gagal menghapus pertanyaan: " . $e->getMessage(); }
    }
    if(isset($_GET['message']) && $_GET['message'] == 'del_ok') $message = "Pertanyaan berhasil dihapus.";
    $stmt_q = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
    $stmt_q->execute([$quiz_id]);
    $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kuis: <?= htmlspecialchars($quiz['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .section-divider { border-top: 1px solid var(--color-border); margin: 2rem 0; }
        .add-question-form { background-color: #f9f9f9; padding: 1.5rem; border-radius: var(--border-radius); border: 1px solid var(--color-border); }
    </style>
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <a href="list.php" class="back-link">&larr; Kembali ke Daftar Kuis</a>
            <h2>Edit Kuis <?= $is_external ? '(Eksternal)' : '(Internal)' ?></h2>
            <?php if ($message): ?><p class="message success"><?= $message ?></p><?php endif; ?>
            <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
            <h3>Detail Kuis</h3>
            <form method="post">
                <input type="hidden" name="update_quiz" value="1">
                <div class="form-group"><label>Judul Kuis:</label><input type="text" name="title" value="<?= htmlspecialchars($quiz['title']) ?>" required></div>
                <div class="form-group"><label>Deskripsi:</label><textarea name="description"><?= htmlspecialchars($quiz['description']) ?></textarea></div>
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
                <?php if ($is_external): ?>
                    <div class="form-group"><label>Link Kuis Eksternal:</label><input type="text" name="external_link" value="<?= htmlspecialchars($quiz['external_link']) ?>" placeholder="https_//forms.gle/contohLinkAnda"></label></div>
                <?php else: ?>
                    <div class="form-group"><label>Durasi (menit):</label><input type="number" name="duration" value="<?= htmlspecialchars($quiz['duration']) ?>" required></label></div>
                <?php endif; ?>
                <button type="submit">Update Detail Kuis</button>
            </form>
            <?php if (!$is_external): ?>
                <div class="section-divider"></div>
                <h3>Daftar Pertanyaan (Total: <?= count($questions ?? []) ?>)</h3>
                <div class="table-container">
                    <table>
                        <thead> <tr> <th>#</th> <th>Pertanyaan</th> <th>Kunci</th> <th>Aksi</th> </tr> </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr><td colspan="4" style="text-align:center;">Belum ada pertanyaan.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($questions as $index => $q): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars(substr($q['question_text'], 0, 80)) ?>...</td>
                                <td><?= htmlspecialchars($q['correct_answer']) ?></td>
                                <td class="action-cell">
                                    <a href="edit.php?id=<?= $quiz_id ?>&delete_question=<?= $q['id'] ?>" class="delete-btn icon-btn" onclick="return confirm('Yakin ingin menghapus pertanyaan ini?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="section-divider"></div>
                <div class="add-question-form">
                    <h3>Tambah Pertanyaan Baru</h3>
                    <form method="post">
                        <input type="hidden" name="add_question" value="1">
                        <div class="form-group"><label>Teks Pertanyaan:</label><textarea name="question_text" required></textarea></div>
                        <h4>Opsi Jawaban</h4>
                        <div class="form-group"><label>A: <input type="text" name="option_a" required></label></div>
                        <div class="form-group"><label>B: <input type="text" name="option_b" required></label></div>
                        <div class="form-group"><label>C: <input type="text" name="option_c" required></label></div>
                        <div class="form-group"><label>D: <input type="text" name="option_d" required></label></div>
                        <div class="form-group">
                            <label>Kunci Jawaban Benar:</label>
                            <select name="correct_answer" required>
                                <option value="A">A</option> <option value="B">B</option> <option value="C">C</option> <option value="D">D</option>
                            </select>
                        </div>
                        <button type="submit">Tambah Pertanyaan</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
</body>
</html>