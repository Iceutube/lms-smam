<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$subjects = $pdo->query("SELECT DISTINCT subject FROM materials WHERE subject IS NOT NULL AND subject <> '' ORDER BY subject ASC")->fetchAll(PDO::FETCH_COLUMN);
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$filterSubject = $_GET['subject'] ?? 'all';
$filterClass = $_GET['class'] ?? 'all';

$params = [];
$where_conditions = [];
$join_classes_sql = "JOIN material_classes mc ON m.id = mc.material_id";

if ($_SESSION['role'] === 'student') {
    $where_conditions[] = "mc.class_name = ?";
    $params[] = $_SESSION['class'] ?? ''; 
    
    if ($filterSubject !== 'all') {
        $where_conditions[] = "m.subject = ?";
        $params[] = $filterSubject;
    }
} elseif ($_SESSION['role'] === 'teacher') {
    $where_conditions[] = "m.subject = ?";
    $params[] = $_SESSION['subject'] ?? '';
    if ($filterClass !== 'all') {
        $where_conditions[] = "mc.class_name = ?";
        $params[] = $filterClass;
    }
} else {
    if ($filterSubject !== 'all') {
        $where_conditions[] = "m.subject = ?";
        $params[] = $filterSubject;
    }
    if ($filterClass !== 'all') {
        $where_conditions[] = "mc.class_name = ?";
        $params[] = $filterClass;
    }
}
$where_sql = !empty($where_conditions) ? "WHERE " . implode(' AND ', $where_conditions) : "";
$sql = "SELECT 
            m.id, m.title, m.description, m.subject,
            m.file_url, m.link_url,
            u.name AS creator,
            GROUP_CONCAT(DISTINCT mc.class_name ORDER BY mc.class_name SEPARATOR ', ') AS classes_assigned
         FROM materials m
         JOIN users u ON m.created_by = u.id
         $join_classes_sql
         $where_sql
         GROUP BY m.id
         ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
$colspan = ($_SESSION['role'] !== 'student') ? 7 : 5;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Materi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <h2>Materi Pelajaran</h2>
            
            <div class="header-links">
                <?php if ($_SESSION['role'] !== 'student'): ?>
                    <a href="add.php"><i class="fas fa-plus"></i> Tambah Materi</a>
                <?php endif; ?>
            </div>

            <form method="get" class="filter-form">
                <?php if ($_SESSION['role'] !== 'teacher'): ?>
                <label>Mata Pelajaran:
                    <select name="subject" onchange="this.form.submit()">
                        <option value="all" <?= $filterSubject==='all'?'selected':'' ?>>Semua Mapel</option>
                        <?php foreach ($subjects as $subj): ?>
                            <option value="<?= htmlspecialchars($subj) ?>" <?= $filterSubject===$subj?'selected':'' ?>>
                                <?= htmlspecialchars($subj) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>

                <?php if ($_SESSION['role'] !== 'student'): ?>
                <label>Kelas:
                    <select name="class" onchange="this.form.submit()">
                        <option value="all" <?= $filterClass==='all'?'selected':'' ?>>Semua Kelas</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= htmlspecialchars($cls) ?>" <?= $filterClass===$cls?'selected':'' ?>>
                                <?= htmlspecialchars($cls) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Materi</th>
                            <th>Deskripsi (Sub Materi)</th>
                            <th>Mapel</th>
                            <?php if ($_SESSION['role'] !== 'student'): ?>
                                <th>Kelas</th>
                            <?php endif; ?>
                            <th>Dibuat Oleh</th>
                            <th>Link</th>
                            <?php if ($_SESSION['role'] !== 'student'): ?>
                                <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materials)): ?>
                            <tr><td colspan="<?= $colspan ?>" style="text-align: center;">Tidak ada materi ditemukan.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($materials as $m): ?>
                        <tr>
                            <td data-label="Materi" style="white-space: normal; min-width: 200px;">
                                <strong><?= htmlspecialchars($m['title']) ?></strong>
                            </td>
                            <td data-label="Deskripsi" style="white-space: normal; min-width: 250px;">
                                <?= htmlspecialchars($m['description']) ?>
                            </td>
                            <td data-label="Mapel">
                                <?= htmlspecialchars($m['subject']) ?>
                            </td>
                            <?php if ($_SESSION['role'] !== 'student'): ?>
                                <td data-label="Kelas" style="white-space: normal;">
                                    <?= htmlspecialchars($m['classes_assigned']) ?>
                                </td>
                            <?php endif; ?>
                            <td data-label="Dibuat Oleh">
                                <?= htmlspecialchars($m['creator']) ?>
                            </td>
                            <td data-label="Link" class="action-cell">
                                <?php if ($m['file_url']): ?>
                                    <a href="../download.php?file=<?= urlencode($m['file_url']) ?>" class="btn" style="background-color: var(--color-primary); width: auto; padding: 5px 10px; font-size: 0.8rem;">Download</a>
                                <?php elseif ($m['link_url']): ?>
                                    <a href="<?= htmlspecialchars($m['link_url']) ?>" target="_blank" class="btn" style="background-color: var(--color-grey); width: auto; padding: 5px 10px; font-size: 0.8rem;">Link</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <?php if ($_SESSION['role'] !== 'student'): ?>
                            <td class="action-cell">
                                <a href="edit.php?id=<?= $m['id'] ?>" class="edit-btn">Edit</a>
                                <a href="#" class="delete-btn" data-id="<?= $m['id'] ?>">Hapus</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h4>Konfirmasi Penghapusan</h4>
            <p>Apakah Anda yakin ingin menghapus materi ini?</p>
            <div class="modal-buttons">
                <a href="#" id="confirmDeleteBtn" class="confirm-btn">Ya, Hapus</a>
                <button id="cancelDeleteBtn" class="cancel-btn">Batal</button>
            </div>
        </div>
    </div>

    <script>
    const deleteModal = document.getElementById('deleteModal');
    const cancelBtn = document.getElementById('cancelDeleteBtn');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const materialId = this.getAttribute('data-id');
            deleteModal.style.display = 'flex';
            confirmBtn.href = 'delete.php?id=' + materialId;
        });
    });
    cancelBtn.addEventListener('click', function() {
        deleteModal.style.display = 'none';
    });
    </script>
</body>
</html>