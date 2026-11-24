<?php
session_start();
if ($_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$quiz_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$error = '';
$stmt = $pdo->prepare("
    SELECT q.* FROM quizzes q
    JOIN quiz_classes qc ON q.id = qc.quiz_id
    WHERE q.id = ? 
      AND q.is_published = TRUE 
      AND (qc.class_name = ? OR qc.class_name = 'Semua')
");
$stmt->execute([$quiz_id, $_SESSION['class']]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) {
    $error = "Kuis tidak ditemukan, belum dipublikasikan, atau bukan untuk kelas Anda.";
}
if (!$error && $quiz['quiz_type'] === 'external') {
    header("Location: " . $quiz['external_link']);
    exit;
}
if (!$error) {
    $stmt_check = $pdo->prepare("SELECT id FROM quiz_results WHERE quiz_id = ? AND user_id = ?");
    $stmt_check->execute([$quiz_id, $user_id]);
    if ($stmt_check->fetch()) {
        $error = "Anda sudah pernah menyelesaikan kuis ini. <a href='list.php'>Lihat kuis lain</a>.";
    }
}
if (!$error) {
    $stmt_q = $pdo->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d 
                           FROM questions WHERE quiz_id = ? ORDER BY RAND()");
    $stmt_q->execute([$quiz_id]);
    $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);
    if (empty($questions)) {
        $error = "Kuis ini (internal) belum memiliki pertanyaan. Silakan hubungi guru Anda.";
    }
}
if (!$error && !isset($_SESSION['quiz_start_time_' . $quiz_id])) {
    $_SESSION['quiz_start_time_' . $quiz_id] = time();
}
$end_time = ($_SESSION['quiz_start_time_' . $quiz_id] ?? time()) + ($quiz['duration'] * 60);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kerjakan Kuis: <?= htmlspecialchars($quiz['title'] ?? 'Error') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {display: block; background-color: var(--color-bg); padding: 1rem;}
        .container {max-width: 900px;margin: 20px auto;}
        .quiz-header {text-align: center; border-bottom: 1px solid var(--color-border); padding-bottom: 20px; margin-bottom: 20px;}
        .quiz-header p {margin: 5px 0 0; color: var(--color-text-light); }
        #timer {position: fixed; top: 20px; right: 20px; background-color: var(--color-red); color: white; padding: 10px 20px; border-radius: var(--border-radius-pill); font-size: 1.2em; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 1001;}
        .question-card {background-color: var(--color-card); border: 1px solid var(--color-border); border-left: 5px solid var(--color-primary); padding: 20px; margin-bottom: 20px; border-radius: var(--border-radius);}
        .question-card h3 { margin-top: 0; margin-bottom: 15px; text-align: left; color: var(--color-text);}
        .options div {margin-bottom: 10px;}
        .options label {display: block; background-color: #f9f9f9; padding: 12px; border-radius: var(--border-radius); border: 1px solid var(--color-border); cursor: pointer; transition: all 0.2s ease;}
        .options label:hover { background-color: #E8F0FE; border-color: var(--color-primary);}
        .options input[type="radio"] { margin-right: 10px; width: auto; accent-color: var(--color-primary);}
        button[type="button"] { background-color: var(--color-green);}
        button[type="button"]:hover { background-color: #218838; }
        .error-container { text-align: center; padding: 40px; }
        .error-container p { font-size: 1.2em; color: var(--color-red); }
        .error-container a { display: inline-block; margin-top: 1rem; background-color: var(--color-primary);  color: white !important; padding: 10px 20px;  border-radius: var(--border-radius-pill);  text-decoration: none;  font-weight: 600;}
    </style>
    </head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="error-container">
                <p><i class="fas fa-exclamation-triangle"></i> <?= $error ?></p>
                <a href="list.php">Kembali ke Daftar Kuis</a>
            </div>
        <?php else: ?>
            <div class="quiz-header">
                <h2><?= htmlspecialchars($quiz['title']) ?></h2>
                <p><strong>Durasi:</strong> <?= $quiz['duration'] ?> menit</p>
            </div>
            <div id="timer"></div>
            <form action="submit.php?id=<?= $quiz_id ?>" method="post" id="quizForm">
                <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
                <?php $q_num = 1; foreach ($questions as $q): ?>
                    <div class="question-card">
                        <h3><?= $q_num++ ?>. <?= htmlspecialchars($q['question_text']) ?></h3>
                        <div class="options">
                            <label><input type="radio" name="answer[<?= $q['id'] ?>]" value="A"> A. <?= htmlspecialchars($q['option_a']) ?></label>
                            <label><input type="radio" name="answer[<?= $q['id'] ?>]" value="B"> B. <?= htmlspecialchars($q['option_b']) ?></label>
                            <label><input type="radio" name="answer[<?= $q['id'] ?>]" value="C"> C. <?= htmlspecialchars($q['option_c']) ?></label>
                            <label><input type="radio" name="answer[<?= $q['id'] ?>]" value="D"> D. <?= htmlspecialchars($q['option_d']) ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button type="button" id="openSubmitModalBtn"><i class="fas fa-paper-plane"></i> Kirim Jawaban</button>
            </form>
            <div id="submitModal" class="modal">
                <div class="modal-content">
                    <h4>Konfirmasi Pengiriman</h4>
                    <p>Anda yakin ingin mengakhiri kuis dan mengirim jawaban?</p>
                    <div class="modal-buttons">
                        <button id="confirmSubmitBtn" class="confirm-btn" style="background-color: var(--color-green);">Ya, Kirim</button>
                        <button id="cancelSubmitBtn" class="cancel-btn">Batal</button>
                    </div>
                </div>
            </div>
            <script>
                const endTime = <?= $end_time ?>;
                const timerDisplay = document.getElementById('timer');
                const quizForm = document.getElementById('quizForm');
                function updateTimer() {
                    const now = Math.floor(Date.now() / 1000);
                    const timeLeft = endTime - now;
                    if (timeLeft <= 0) {
                        timerDisplay.textContent = "Waktu Habis!";
                        timerDisplay.style.backgroundColor = "var(--color-red)";
                        alert("Waktu pengerjaan kuis telah habis! Jawaban Anda akan dikirim secara otomatis.");
                        quizForm.submit(); 
                        return;
                    }
                    if (timeLeft < 60) {
                        timerDisplay.style.backgroundColor = "var(--color-yellow)";
                        timerDisplay.style.color = "#333";
                    }
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timerDisplay.innerHTML = `<i class="fas fa-clock"></i> ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }
                const timerInterval = setInterval(updateTimer, 1000);
                updateTimer(); 
                window.addEventListener('beforeunload', function (e) {
                    const confirmationMessage = 'Waktu akan terus berjalan jika Anda meninggalkan halaman kuis. Apakah Anda yakin?';
                    e.returnValue = confirmationMessage;
                    return confirmationMessage;
                });
            </script>
            <script>
                const submitModal = document.getElementById('submitModal');
                const openModalBtn = document.getElementById('openSubmitModalBtn');
                const cancelBtn = document.getElementById('cancelSubmitBtn');
                const confirmBtn = document.getElementById('confirmSubmitBtn');
                openModalBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    submitModal.style.display = 'flex';
                });
                cancelBtn.addEventListener('click', function() {
                    submitModal.style.display = 'none';
                });
                confirmBtn.addEventListener('click', function() {
                    document.getElementById('quizForm').submit();
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>