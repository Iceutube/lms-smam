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
    SELECT q.title, qr.score, qr.total_questions, qr.correct_count
    FROM quizzes q
    JOIN quiz_results qr ON q.id = qr.quiz_id
    WHERE q.id = ? AND qr.user_id = ?
");
$stmt->execute([$quiz_id, $user_id]);
$result_summary = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result_summary) {
    $error = "Hasil kuis tidak ditemukan atau Anda belum mengerjakan kuis ini.";
}

$review_data = [];
if (!$error) {
    $stmt_review = $pdo->prepare("
        SELECT 
            qs.id, 
            qs.question_text, 
            qs.option_a, 
            qs.option_b, 
            qs.option_c, 
            qs.option_d, 
            qs.correct_answer,
            sa.selected_answer,
            sa.is_correct
        FROM questions qs
        LEFT JOIN student_answers sa ON qs.id = sa.question_id AND sa.user_id = ?
        WHERE qs.quiz_id = ?
        ORDER BY qs.id ASC
    ");
    $stmt_review->execute([$user_id, $quiz_id]);
    $review_data = $stmt_review->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Kuis: <?= htmlspecialchars($result_summary['title'] ?? 'Error') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .review-header {
            text-align: center;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .review-header h2 { color: var(--color-primary); margin: 0; }
        .review-header p { margin: 5px 0 0; color: var(--color-text-light); font-size: 1.2em; }
        .review-header .score { font-size: 2em; font-weight: bold; color: var(--color-green); }

        .question-review-card {
            border: 1px solid var(--color-border);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            background: var(--color-card);
        }
        .question-review-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            text-align: left;
            color: var(--color-text);
            display: flex;
            align-items: center;
        }

        .options-review .option {
            margin-bottom: 10px;
            padding: 10px 15px;
            border-radius: var(--border-radius);
            border: 1px solid var(--color-border);
            background: #f9f9f9;
            position: relative;
        }
        .options-review .option.correct {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            font-weight: 600;
        }
        .options-review .option.wrong {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            font-weight: 600;
        }

        .answer-label {
            font-weight: bold;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .answer-label.your-answer {
            color: var(--color-primary-dark);
        }
        .answer-label.correct-answer {
            color: var(--color-green);
        }

        .status-icon {
            margin-right: 10px;
            font-size: 1.2em;
        }
        .status-icon.fa-check-circle { color: var(--color-green); }
        .status-icon.fa-times-circle { color: var(--color-red); }
    </style>
</head>
<body class="app-layout">

    <?php include '../layout/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <?php if ($error): ?>
                <h2>Oops! Terjadi Kesalahan</h2>
                <p class="error"><?= $error ?></p>
                <a href="list.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali</a>
            <?php else: ?>
                <div class="review-header">
                    <h2>Review: <?= htmlspecialchars($result_summary['title']) ?></h2>
                    <p>Skor Anda: <span class="score"><?= number_format($result_summary['score'], 0) ?></span></p>
                    <p>(<?= $result_summary['correct_count'] ?> dari <?= $result_summary['total_questions'] ?> jawaban benar)</p>
                </div>
                
                <?php $q_num = 1; foreach ($review_data as $item): ?>
                    <div class="question-review-card">
                        <h3>
                            <?php if ($item['is_correct']): ?>
                                <i class="fas fa-check-circle status-icon"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle status-icon"></i>
                            <?php endif; ?>
                            <?= $q_num++ ?>. <?= htmlspecialchars($item['question_text']) ?>
                        </h3>
                        <div class="options-review">
                            <?php foreach (['A', 'B', 'C', 'D'] as $option): 
                                $option_text = $item['option_' . strtolower($option)];
                                $classes = 'option';
                                $label = '';

                                $is_correct_answer = ($option === $item['correct_answer']);
                                $is_your_answer = ($option === $item['selected_answer']);

                                if ($is_correct_answer) {
                                    $classes .= ' correct';
                                    $label .= '<span class="answer-label correct-answer">(Jawaban Benar)</span>';
                                }
                                
                                if ($is_your_answer && !$is_correct_answer) {
                                    $classes = 'option wrong'; // Timpa jika jawabanmu salah
                                    $label = '<span class="answer-label your-answer">(Jawaban Anda)</span>';
                                } elseif ($is_your_answer && $is_correct_answer) {
                                    $label = '<span class="answer-label your-answer">(Jawaban Anda Benar)</span>';
                                }
                            ?>
                                <div class="<?= $classes ?>">
                                    <?= $option ?>. <?= htmlspecialchars($option_text) ?> <?= $label ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <a href="list.php" class="back-link" style="width: auto; margin-top: 1rem;"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Kuis</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>