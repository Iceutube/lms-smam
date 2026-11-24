<?php
session_start();
if (!in_array($_SESSION['role'], ['admin','teacher'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $filter = $_GET['category'] ?? 'all';

    if ($status === 'resolved') {
        $stmt = $pdo->prepare("DELETE FROM feedback WHERE id=?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = "Feedback berhasil diselesaikan dan dihapus.";
    } else {
        $stmt = $pdo->prepare("UPDATE feedback SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
        $_SESSION['flash'] = "Status feedback berhasil diperbarui.";
    }

    header("Location: list.php?category=" . urlencode($filter));
    exit;
}
$filter = $_GET['category'] ?? 'all';
if ($filter === 'all') {
    $stmt = $pdo->query("SELECT f.*, u.name AS user_name 
                         FROM feedback f 
                         LEFT JOIN users u ON f.user_id = u.id 
                         ORDER BY f.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT f.*, u.name AS user_name 
                           FROM feedback f 
                           LEFT JOIN users u ON f.user_id = u.id 
                           WHERE f.category = ? 
                           ORDER BY f.created_at DESC");
    $stmt->execute([$filter]);
}
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .flash { 
            background: #d4edda; color: #155724; padding: 12px; 
            border: 1px solid #c3e6cb; margin-bottom: 15px; 
            border-radius: var(--border-radius); text-align: center; 
        }
    </style>
</head>
<body class="app-layout">

    <?php include '../layout/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <h2>Feedback</h2>

            <?php if (!empty($_SESSION['flash'])): ?>
                <div class="flash"><?= $_SESSION['flash']; unset($_SESSION['flash']); ?></div>
            <?php endif; ?>

            <form method="get" class="filter-form">
                <label>Filter Kategori:
                    <select name="category" onchange="this.form.submit()">
                        <option value="all" <?= $filter=='all'?'selected':'' ?>>Semua</option>
                        <option value="app" <?= $filter=='app'?'selected':'' ?>>Aplikasi</option>
                        <option value="school" <?= $filter=='school'?'selected':'' ?>>Sekolah</option>
                        <option value="other" <?= $filter=='other'?'selected':'' ?>>Lainnya</option>
                    </select>
                </label>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Pengirim</th>
                            <th>Kategori</th>
                            <th>Pesan</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedbacks)): ?>
                            <tr><td colspan="6" style="text-align: center;">Tidak ada feedback.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($feedbacks as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['user_name'] ?? 'Anonim') ?></td>
                            <td><?= ucfirst($f['category']) ?></td>
                            <td style="white-space: normal; min-width: 300px;"><?= htmlspecialchars($f['message']) ?></td>
                            <td><?= ucfirst($f['status']) ?></td>
                            <td><?= $f['created_at'] ?></td>
                            <td class="action-cell" style="min-width: 150px;">
                                <form method="post">
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="new" <?= $f['status']=='new'?'selected':'' ?>>Baru</option>
                                        <option value="in_progress" <?= $f['status']=='in_progress'?'selected':'' ?>>Diproses</option>
                                        <option value="resolved">Selesai (hapus)</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>