<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM schedules WHERE id=?");
$stmt->execute([$id]);

header("Location: list.php");
exit;
