<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

try {
    $stmt = $pdo->query("SELECT title, body, created_at FROM announcements ORDER BY created_at DESC LIMIT 3");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $summary = [
        'users' => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'materials' => (int) $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn(),
        'quizzes' => (int) $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn(),
        'schedules' => (int) $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn(),
    ];
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <section class="dashboard-hero">
                <span class="page-header__eyebrow" style="color:#cbdcff">Pusat Administrasi</span>
                <h1>Selamat datang, <?= htmlspecialchars($_SESSION['name']) ?></h1>
                <p>Kelola aktivitas pembelajaran, akun, dan informasi sekolah dari satu ruang kerja yang ringkas.</p>
                <div class="hero-actions">
                    <a class="btn" href="../users/add.php"><i class="fas fa-user-plus"></i> Tambah User</a>
                    <a class="btn" href="../announcements/add.php"><i class="fas fa-bullhorn"></i> Buat Pengumuman</a>
                </div>
            </section>
            <section class="stats-grid" aria-label="Ringkasan platform">
                <article class="stat-card"><span class="stat-card__icon"><i class="fas fa-users"></i></span><div><strong class="stat-card__value"><?= $summary['users'] ?></strong><span class="stat-card__label">Total pengguna</span></div></article>
                <article class="stat-card"><span class="stat-card__icon"><i class="fas fa-book"></i></span><div><strong class="stat-card__value"><?= $summary['materials'] ?></strong><span class="stat-card__label">Materi tersedia</span></div></article>
                <article class="stat-card"><span class="stat-card__icon"><i class="fas fa-question-circle"></i></span><div><strong class="stat-card__value"><?= $summary['quizzes'] ?></strong><span class="stat-card__label">Kuis dibuat</span></div></article>
                <article class="stat-card"><span class="stat-card__icon"><i class="fas fa-calendar-alt"></i></span><div><strong class="stat-card__value"><?= $summary['schedules'] ?></strong><span class="stat-card__label">Jadwal tercatat</span></div></article>
            </section>
            <section class="news-section">
                <div class="section-heading"><h2>Pengumuman terbaru</h2><a href="../announcements/list.php">Lihat semua <i class="fas fa-arrow-right"></i></a></div>
                <div class="news-grid">
                    <?php if (empty($announcements)): ?>
                        <div class="news-card"><div class="news-content"><h3>Belum ada pengumuman</h3><p>Buat pengumuman pertama untuk menyampaikan informasi kepada warga sekolah.</p></div></div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <article class="news-card"><div class="news-content"><h3><?= htmlspecialchars($announcement['title']) ?></h3><p><?= htmlspecialchars(substr($announcement['body'], 0, 130) . (strlen($announcement['body']) > 130 ? '…' : '')) ?></p></div><span class="news-meta"><i class="far fa-calendar"></i> <?= htmlspecialchars($announcement['created_at']) ?></span></article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
