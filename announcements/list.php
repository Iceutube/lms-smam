<?php
session_start();
if (!in_array($_SESSION['role'], ['admin','teacher','student'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$sql_base = "SELECT 
                a.id, a.title, a.body, a.published_at, 
                u.name AS creator,
                GROUP_CONCAT(DISTINCT qc.class_name ORDER BY qc.class_name SEPARATOR ', ') AS classes_assigned
             FROM announcements a 
             JOIN users u ON a.created_by = u.id
             LEFT JOIN announcement_classes qc ON a.id = qc.announcement_id";
$where_conditions = [];
$params = [];
if ($_SESSION['role'] === 'student') {
    $where_conditions[] = "(qc.class_name = ? OR qc.class_name = 'Semua')";
    $params[] = $_SESSION['class'] ?? '';
}
if (!empty($where_conditions)) {
    $sql_base .= " WHERE " . implode(' AND ', $where_conditions);
}
$sql_base .= " GROUP BY a.id ORDER BY a.published_at DESC";
$stmt = $pdo->prepare($sql_base);
$stmt->execute($params);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengumuman</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <h2>Pengumuman</h2>
            <?php if ($_SESSION['role'] !== 'student'): ?>
            <div class="header-links">
                <a href="add.php"><i class="fas fa-plus"></i> Tambah Pengumuman</a>
            </div>
            <?php endif; ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Kegiatan</th>
                            <th>Isi</th>
                            <th>Pembuat</th>
                            <th>Ditujukan</th>
                            <th>Tanggal</th>
                            <?php if ($_SESSION['role'] !== 'student'): ?><th>Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($announcements)): ?>
                            <tr><td colspan="<?= $_SESSION['role'] !== 'student' ? 6 : 5 ?>" style="text-align: center;">Tidak ada pengumuman untuk Anda.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($announcements as $a): ?>
                            <tr>
                                <td data-label="Judul" style="white-space: normal; min-width: 200px;"><?= htmlspecialchars($a['title']) ?></td>
                                <td data-label="Isi" style="white-space: normal; min-width: 300px;"><?= htmlspecialchars($a['body']) ?></td>
                                <td data-label="Pembuat"><?= htmlspecialchars($a['creator']) ?></td>
                                <td data-label="Ditujukan" style="white-space: normal; min-width: 150px;"><?= htmlspecialchars($a['classes_assigned']) ?></td>
                                <td data-label="Tanggal"><?= $a['published_at'] ?></td>
                                <?php if ($_SESSION['role'] !== 'student'): ?>
                                <td class="action-cell">
                                    <a href="edit.php?id=<?= $a['id'] ?>" class="edit-btn">Edit</a>
                                    <a href="#" class="delete-btn" data-id="<?= $a['id'] ?>">Hapus</a>
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
            <p>Apakah Anda yakin ingin menghapus pengumuman ini?</p>
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
            const announcementId = this.getAttribute('data-id');
            deleteModal.style.display = 'flex';
            confirmBtn.href = 'delete.php?id=' + announcementId;
        });
    });

    cancelBtn.addEventListener('click', function() {
        deleteModal.style.display = 'none';
    });
    </script>
</body>
</html>