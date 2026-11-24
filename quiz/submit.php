<?php
session_start();
if ($_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$quiz_id = $_GET['id'] ?? $_POST['quiz_id'] ?? 0;
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';
$score = 0;
$correct_count = 0;
$total_questions = 0;
$stmt_title = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
$stmt_title->execute([$quiz_id]);
$quiz_title = $stmt_title->fetchColumn();
if (!isset($_SESSION['quiz_start_time_' . $quiz_id])) {
    $error = "Sesi kuis tidak valid. Anda mungkin sudah mengirimkan jawaban ini sebelumnya.";
} else {
    unset($_SESSION['quiz_start_time_' . $quiz_id]);
    $stmt_check = $pdo->prepare("SELECT id, score FROM quiz_results WHERE quiz_id = ? AND user_id = ?");
    $stmt_check->execute([$quiz_id, $user_id]);
    $existing_result = $stmt_check->fetch(PDO::FETCH_ASSOC);
    if ($existing_result) {
        $error = "Hasil Anda sudah tercatat sebelumnya dengan skor " . htmlspecialchars($existing_result['score']) . ".";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt_keys = $pdo->prepare("SELECT id, correct_answer FROM questions WHERE quiz_id = ?");
            $stmt_keys->execute([$quiz_id]);
            $correct_answers = $stmt_keys->fetchAll(PDO::FETCH_KEY_PAIR);
            $total_questions = count($correct_answers);
            if ($total_questions > 0) {
                $submitted_answers = $_POST['answer'] ?? [];                
                $sql_insert_answer = "INSERT INTO student_answers (quiz_id, user_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?, ?)";
                $stmt_insert_answer = $pdo->prepare($sql_insert_answer);
                foreach ($correct_answers as $q_id => $correct_key) {
                    $submitted_key = $submitted_answers[$q_id] ?? null;
                    $is_correct = ($submitted_key === $correct_key);
                    if ($is_correct) {
                        $correct_count++;
                    }
                    $stmt_insert_answer->execute([$quiz_id, $user_id, $q_id, $submitted_key, $is_correct]);
                }
                $score = round(($correct_count / $total_questions) * 100, 2);
                $sql_insert_result = "INSERT INTO quiz_results (quiz_id, user_id, score, total_questions, correct_count) VALUES (?, ?, ?, ?, ?)";
                $pdo->prepare($sql_insert_result)->execute([$quiz_id, $user_id, $score, $total_questions, $correct_count]);
            }
            $pdo->commit();
            $message = "Selamat! Kuis berhasil diselesaikan.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Gagal menyimpan hasil kuis: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Kuis: <?= htmlspecialchars($quiz_title ?: 'Selesai') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem; background-color: var(--color-bg);}
        .container {max-width: 700px; text-align: center; background-color: var(--color-card); padding: 2.5rem; border-radius: var(--border-radius); box-shadow: var(--shadow);}
        .score-display { margin: 2.5rem 0;}
        .score-display h3 { font-size: 1.5em; color: var(--color-text-light); margin-bottom: 1rem; font-weight: 600;}
        .score-value { font-size: 5em; font-weight: 700; color: var(--color-primary);}
        .score-details p { font-size: 1.2em; margin: 5px 0; color: var(--color-text-light); }
        .score-details strong {color: var(--color-text);}
        .result-icon { font-size: 6rem; margin-bottom: 1.5rem;}
        .icon-success { color: var(--color-green); }
        .icon-error { color: var(--color-red); }
        .button-group { display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; margin-top: 2rem;}
        .button-group a { width: auto; padding: 12px 25px;}
    </style>
</head>
<body>
    <div class="container">
        
        <?php if ($error): ?>
            <i class="fas fa-exclamation-triangle result-icon icon-error"></i>
            <h2>Oops! Terjadi Kesalahan</h2>
            <p class="error"><?= $error ?></p>
            <div class="button-group">
                <a href="list.php" class="btn" style="background-color: var(--color-grey);"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
            </div>
            
        <?php else: ?>
            <i class="fas fa-check-circle result-icon icon-success"></i>
            <h2><?= $message ?></h2>
            
            <div class="score-display">
                <h3>Skor Anda:</h3>
                <span class="score-value"><?= number_format($score, 0) ?></span> / 100
            </div>
            
            <div class="score-details">
                <p><strong>Jawaban Benar:</strong> <?= $correct_count ?> dari <?= $total_questions ?> soal</p>
                <?php if ($total_questions > 0): ?>
                <p><strong>Persentase Benar:</strong> <?= number_format(($correct_count / $total_questions) * 100, 0) ?>%</p>
                <?php endif; ?>
            </div>

            <div class="button-group">
                <a href="list.php" class="btn" style="background-color: var(--color-grey);"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
                <a href="review.php?id=<?= $quiz_id ?>" class="btn"><i class="fas fa-search"></i> Lihat Detail Jawaban</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>