<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';
$filter_role = $_GET['role'] ?? 'all';
$filter_class = $_GET['filter_class'] ?? 'all';
$search_name = $_GET['search_name'] ?? '';
$search_nis = $_GET['search_nis'] ?? '';
$classes = $pdo->query("SELECT name FROM classes ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$sql_base = "SELECT * FROM users";
$where_conditions = [];
$params = [];
if ($filter_role !== 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $filter_role;
}
if ($filter_class !== 'all') {
    $where_conditions[] = "class = ?";
    $params[] = $filter_class;
}
if (!empty($search_name)) {
    $where_conditions[] = "name LIKE ?";
    $params[] = "%" . $search_name . "%";
}
if (!empty($search_nis)) {
    $where_conditions[] = "nis LIKE ?";
    $params[] = "%" . $search_nis . "%";
}
if (!empty($where_conditions)) {
    $sql = $sql_base . " WHERE " . implode(' AND ', $where_conditions);
} else {
    $sql = $sql_base;
}
$sql .= " ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .filter-form button {
            width: auto;
            margin-top: 0;
            padding: 10px 15px;
        }
    </style>
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <h2>Daftar User</h2>
            <div class="header-links">
                <a href="add.php"><i class="fas fa-plus"></i> Tambah User</a>
            </div>
            <form method="get" class="filter-form">
                <div class="form-group" style="flex-grow: 1; min-width: 150px;">
                    <label>Cari Nama:</label>
                    <input type="text" name="search_name" placeholder="Ketik nama..." value="<?= htmlspecialchars($search_name) ?>">
                </div>
                <div class="form-group" style="flex-grow: 1; min-width: 120px;">
                    <label>Cari NIS:</label>
                    <input type="text" name="search_nis" placeholder="Ketik NIS..." value="<?= htmlspecialchars($search_nis) ?>">
                </div>
                <div class="form-group" style="flex-grow: 1; min-width: 150px;">
                    <label>Filter Role:</label>
                    <select name="role">
                        <option value="all" <?= $filter_role=='all'?'selected':'' ?>>Semua Role</option>
                        <option value="admin" <?= $filter_role=='admin'?'selected':'' ?>>Admin</option>
                        <option value="teacher" <?= $filter_role=='teacher'?'selected':'' ?>>Guru</option>
                        <option value="student" <?= $filter_role=='student'?'selected':'' ?>>Siswa</option>
                    </select>
                </div>
                <div class="form-group" style="flex-grow: 1; min-width: 150px;">
                    <label>Filter Kelas:</label>
                    <select name="filter_class">
                        <option value="all" <?= $filter_class=='all'?'selected':'' ?>>Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= htmlspecialchars($class) ?>" <?= $filter_class==$class?'selected':'' ?>>
                                <?= htmlspecialchars($class) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit">Filter</button>
                </div>
            </form>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>NIS</th>
                            <th>Role</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Data user tidak ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td data-label="Nama"><?= htmlspecialchars($user['name']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                            <td data-label="NIS"><?= htmlspecialchars($user['nis'] ?: '-') ?></td>
                            <td data-label="Role"><?= htmlspecialchars($user['role']) ?></td>
                            <td data-label="Kelas"><?= htmlspecialchars($user['class'] ?: '-') ?></td>
                            <td data-label="Mata Pelajaran"><?= htmlspecialchars($user['subject'] ?: '-') ?></td>
                            <td class="action-cell">
                                <a href="edit.php?id=<?= $user['id'] ?>" class="edit-btn">Edit</a>
                                <a href="#" class="delete-btn" data-id="<?= $user['id'] ?>">Hapus</a>
                            </td>
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
            <p>Apakah Anda yakin ingin menghapus pengguna ini?</p>
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
            const userId = this.getAttribute('data-id');
            deleteModal.style.display = 'flex';
            confirmBtn.href = 'delete.php?id=' + userId;
        });
    });

    cancelBtn.addEventListener('click', function() {
        deleteModal.style.display = 'none';
    });
    </script>
</body>
</html>