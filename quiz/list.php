<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;}
require '../includes/db.php';
$current_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$subjects = $pdo->query("SELECT DISTINCT subject FROM quizzes WHERE subject IS NOT NULL AND subject <> '' ORDER BY subject ASC")->fetchAll(PDO::FETCH_COLUMN);
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$filterSubject = $_GET['subject'] ?? 'all';
$filterClass = $_GET['class'] ?? 'all';
$params = [];
$select_score_sql = "";
$join_results_sql = "";
$join_classes_sql = "JOIN quiz_classes qc ON q.id = qc.quiz_id";
$where_conditions = [];
if ($current_role === 'student') {
    $where_conditions[] = "q.is_published = TRUE";
    $where_conditions[] = "qc.class_name = ?";
    $params[] = $_SESSION['class'];
    $select_score_sql = ", qr.score";
    $join_results_sql = "LEFT JOIN quiz_results qr ON q.id = qr.quiz_id AND qr.user_id = ?";
    array_unshift($params, $user_id);
} elseif ($current_role === 'teacher') {
    $where_conditions[] = "q.created_by = ?";
    $params[] = $user_id;
    if ($filterClass !== 'all') {
        $where_conditions[] = "qc.class_name = ?";
        $params[] = $filterClass;
    }
} elseif ($current_role === 'admin') {
    if ($filterClass !== 'all') {
        $where_conditions[] = "qc.class_name = ?";
        $params[] = $filterClass;
    }
}
if ($filterSubject !== 'all') {
    $where_conditions[] = "q.subject = ?";
    $params[] = $filterSubject;
}
$where_sql = !empty($where_conditions) ? "WHERE " . implode(' AND ', $where_conditions) : "";
$sql = "SELECT 
            q.*, 
            u.name AS creator, 
            COUNT(qs.id) AS total_questions,
            GROUP_CONCAT(DISTINCT qc.class_name ORDER BY qc.class_name SEPARATOR ', ') AS classes_assigned
            $select_score_sql
        FROM quizzes q 
        JOIN users u ON q.created_by = u.id
        $join_classes_sql
        LEFT JOIN questions qs ON q.id = qs.quiz_id
        $join_results_sql
        $where_sql
        GROUP BY q.id
        ORDER BY q.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kuis</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <h2>Daftar Kuis</h2>
            <div class="header-links">
                <?php if ($current_role !== 'student'): ?>
                    <a href="add.php"><i class="fas fa-plus"></i> Tambah Kuis</a>
                <?php endif; ?>
            </div>
            <form method="get" class="filter-form">
                <label>Mata Pelajaran:
                    <select name="subject" onchange="this.form.submit()">
                        <option value="all" <?= $filterSubject === 'all' ? 'selected' : '' ?>>Semua Mapel</option>
                        <?php foreach ($subjects as $subj): ?>
                            <option value="<?= htmlspecialchars($subj) ?>" <?= $filterSubject === $subj ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subj) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ($current_role !== 'student'): ?>
                <label>Kelas:
                    <select name="class" onchange="this.form.submit()">
                        <option value="all" <?= $filterClass === 'all' ? 'selected' : '' ?>>Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= htmlspecialchars($class) ?>" <?= $filterClass === $class ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
                <noscript><button type="submit">Filter</button></noscript>
            </form>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Mapel</th>
                            <th>Tipe Kuis</th>
                            <?php if ($current_role !== 'student'): ?>
                                <th>Kelas</th>
                            <?php endif; ?>
                            <th>Soal</th>
                            <th>Durasi (menit)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $colspan = ($current_role === 'student') ? 7 : 8; ?>
                        <?php if (empty($quizzes)): ?>
                            <tr><td colspan="<?= $colspan ?>" style="text-align: center;">Tidak ada kuis yang tersedia.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($quizzes as $q): ?>
                            <tr>
                                <td><?= htmlspecialchars($q['title']) ?></td>
                                <td><?= htmlspecialchars($q['subject']) ?></td>
                                <td>
                                    <?php if($q['quiz_type'] == 'external'): ?>
                                        <i class="fas fa-link"></i> Eksternal
                                    <?php else: ?>
                                        <i class="fas fa-tasks"></i> Internal
                                    <?php endif; ?>
                                </td>
                                <?php if ($current_role !== 'student'): ?>
                                    <td style="white-space: normal; min-width: 150px;"><?= htmlspecialchars($q['classes_assigned']) ?></td>
                                <?php endif; ?>             
                                <td><?= $q['quiz_type'] == 'internal' ? $q['total_questions'] : 'N/A' ?></td>
                                <td><?= $q['quiz_type'] == 'internal' ? $q['duration'] : 'N/A' ?></td>
                                <td><span class="status-<?= $q['is_published'] ? 'aktif' : 'draft' ?>"><?= $q['is_published'] ? 'Aktif' : 'Draft' ?></span></td>
                                <td class="action-cell">
                                    <?php if ($current_role === 'student'): ?>
                                        <?php if ($q['quiz_type'] == 'external'): ?>
                                            <a href="<?= htmlspecialchars($q['external_link']) ?>" class="start-btn" target="_blank">Kerjakan Kuis</a>
                                        <?php else: ?>
                                            <?php if (isset($q['score'])): ?>
                                                <a href="review.php?id=<?= $q['id'] ?>" class="review-btn">Lihat Hasil (<?= number_format($q['score'], 0) ?>)</a>
                                            <?php else: ?>
                                                <a href="take.php?id=<?= $q['id'] ?>" class="start-btn">Mulai Kuis</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="edit.php?id=<?= $q['id'] ?>" class="edit-btn icon-btn" title="Edit Kuis"><i class="fas fa-edit"></i></a>
                                        <a href="toggle_publish.php?id=<?= $q['id'] ?>" class="<?= $q['is_published'] ? 'draft-btn' : 'publish-btn' ?>">
                                            <?= $q['is_published'] ? 'Draft' : 'Publish' ?>
                                        </a>
                                        <a href="delete.php?id=<?= $q['id'] ?>" class="delete-btn icon-btn" title="Hapus Kuis" onclick="return confirm('Anda yakin ingin menghapus kuis ini?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td></tr>
                        <?php endforeach; ?>
                    </tbody></table></div> </div>
    </main>
</body>
</html>