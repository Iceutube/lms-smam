<?php
session_start();
if (!in_array($_SESSION['role'], ['admin','teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM materials WHERE id=?");
$stmt->execute([$id]);

header("Location: list.php");
exit;
