<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: list.php");
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id=?");
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    die("Jadwal tidak ditemukan!");
}
$classes = $pdo->query("SELECT * FROM classes")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query("SELECT id, name, subject FROM users WHERE role='teacher'")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id   = $_POST['class_id'];
    $teacher_id = $_POST['teacher_id'];
    $subject_id = $_POST['subject_id'];
    $day        = $_POST['day'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];
    $stmtSubject = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
    $stmtSubject->execute([$subject_id]);
    $subject_name = $stmtSubject->fetchColumn();

    $stmt = $pdo->prepare("UPDATE schedules 
                            SET class_id=?, teacher_id=?, subject=?, day=?, start_time=?, end_time=? 
                            WHERE id=?");
    $stmt->execute([$class_id, $teacher_id, $subject_name, $day, $start_time, $end_time, $id]);

    header("Location: list.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jadwal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 12px); 
            padding: 10px 12px;
            border-color: var(--color-border);
            border-radius: var(--border-radius);
        }
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 10px);
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0;
        }
    </style>
</head>
<body class="app-layout">

    <?php include '../layout/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <a href="list.php" class="back-link">&larr; Kembali ke Daftar Jadwal</a>
            <h2>Edit Jadwal Pelajaran</h2>
            <form method="post">
                <div class="form-group">
                    <label>Kelas:</label>
                    <select name="class_id" id="class_id" class="select-search" required>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id']==$schedule['class_id']?'selected':'' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Guru:</label>
                    <select name="teacher_id" id="teacher_id" class="select-search" required>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" data-subject-name="<?= htmlspecialchars($t['subject']) ?>" <?= $t['id']==$schedule['teacher_id']?'selected':'' ?>>
                                <?= htmlspecialchars($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mata Pelajaran:</label>
                    <select name="subject_id" id="subject_id" class="select-search" required>
                        <?php 
                        $stmtS = $pdo->prepare("SELECT id FROM subjects WHERE name = ?");
                        $stmtS->execute([$schedule['subject']]);
                        $currentSubjectId = $stmtS->fetchColumn();
                        foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($s['id'] === $currentSubjectId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hari:</label>
                    <select name="day" required>
                        <?php 
                        $days = ['Senin','Selasa','Rabu','Kamis','Jumat'];
                        foreach ($days as $d): ?>
                            <option value="<?= $d ?>" <?= $schedule['day']==$d?'selected':'' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jam Mulai:</label>
                    <input type="time" name="start_time" value="<?= $schedule['start_time'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Jam Selesai:</label>
                    <input type="time" name="end_time" value="<?= $schedule['end_time'] ?>" required>
                </div>
                <button type="submit">Simpan Perubahan</button>
            </form>
        </div>
    </main>
</body>
</html>