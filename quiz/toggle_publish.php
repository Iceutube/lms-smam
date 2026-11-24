<?php
session_start();
if (!in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id || !is_numeric($quiz_id)) {
    die("ID Kuis tidak valid.");
}

$user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];

try {    $stmt = $pdo->prepare("SELECT is_published, created_by FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        die("Kuis tidak ditemukan.");
    }
    if ($current_role === 'teacher' && $quiz['created_by'] != $user_id) {
        die("Anda tidak memiliki izin untuk mengubah status kuis ini.");
    }
    $new_status = !$quiz['is_published'];
    $stmt_update = $pdo->prepare("UPDATE quizzes SET is_published = ? WHERE id = ?");
    $stmt_update->execute([$new_status, $quiz_id]);
    header("Location: list.php?status=" . ($new_status ? 'published' : 'drafted'));
    exit;

} catch (PDOException $e) {
    die("Gagal mengubah status kuis: " . htmlspecialchars($e->getMessage()));
}
?>