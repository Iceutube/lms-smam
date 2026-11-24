<?php
session_start();
if (!in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$quiz_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($quiz_id == 0) {
    header("Location: list.php?error=invalid_id");
    exit;
}
$stmt = $pdo->prepare("SELECT created_by FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: list.php?error=not_found");
    exit;
}
if ($_SESSION['role'] === 'teacher' && $quiz['created_by'] != $user_id) {
    header("Location: list.php?error=forbidden");
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM student_answers WHERE quiz_id = ?")->execute([$quiz_id]);
    $pdo->prepare("DELETE FROM quiz_results WHERE quiz_id = ?")->execute([$quiz_id]);
    $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$quiz_id]);
    $pdo->prepare("DELETE FROM quizzes WHERE id = ?")->execute([$quiz_id]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Gagal menghapus kuis: " . $e->getMessage());
}
header("Location: list.php?status=deleted");
exit;