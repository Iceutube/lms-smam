<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$isAdmin = ($_SESSION['role'] === 'admin');
$isTeacher = ($_SESSION['role'] === 'teacher');
$isStudent = ($_SESSION['role'] === 'student'); 
$userId = $_SESSION['user_id'] ?? null; 
try {
    $classes = $pdo->query("SELECT * FROM classes")->fetchAll(PDO::FETCH_ASSOC);

    $sql_base = "SELECT s.id, c.name AS class_name, u.name AS teacher_name, 
                 COALESCE(s.subject, '-') AS subject_name,
                 s.day, s.start_time, s.end_time
                 FROM schedules s
                 JOIN classes c ON s.class_id = c.id
                 JOIN users u ON s.teacher_id = u.id";
    $where = [];
    $params = [];
    if ($isStudent) {
        $where[] = "c.name = ?"; 
        $params[] = $_SESSION['class'];
    }
    if ($isTeacher) {
        $where[] = "s.teacher_id = ?";
        $params[] = $userId;
    }
    $selected_class_id = $_GET['class'] ?? '';
    if ($isAdmin && !empty($selected_class_id)) {
        $where[] = "s.class_id = ?";
        $params[] = (int)$selected_class_id;
    }
    $selected_day = $_GET['day'] ?? '';
    if (!empty($selected_day)) {
        $where[] = "s.day = ?";
        $params[] = $selected_day;
    }
    $sql_where = "";
    if (!empty($where)) {
        $sql_where = " WHERE " . implode(' AND ', $where);
    }
    $sql_order = " ORDER BY FIELD(s.day,'Senin','Selasa','Rabu','Kamis','Jumat'), s.start_time";
    $sql = $sql_base . $sql_where . $sql_order;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $grouped = [];
    foreach ($schedules as $sch) {
        $grouped[$sch['day']][] = $sch;
    }
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pelajaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-layout">
    <?php include '../layout/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <h2>Jadwal Pelajaran</h2>
            
            <div class="header-links">
                <?php if ($isAdmin): ?>
                    <a href="add.php"><i class="fas fa-plus"></i> Tambah Jadwal</a>
                <?php endif; ?>
            </div>
            <?php if ($isAdmin || $isTeacher): ?>
            <form method="get" class="filter-form">
                <?php if ($isAdmin): ?>
                    <label>Pilih Kelas:
                        <select name="class" onchange="this.form.submit()">
                            <option value="">-- Semua Kelas --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($selected_class_id == $c['id'] ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endif; ?>
                <label>Pilih Hari:
                    <select name="day" onchange="this.form.submit()">
                        <option value="">-- Semua Hari --</option>
                        <option value="Senin" <?= ($selected_day === 'Senin' ? 'selected' : '') ?>>Senin</option>
                        <option value="Selasa" <?= ($selected_day === 'Selasa' ? 'selected' : '') ?>>Selasa</option>
                        <option value="Rabu" <?= ($selected_day === 'Rabu' ? 'selected' : '') ?>>Rabu</option>
                        <option value="Kamis" <?= ($selected_day === 'Kamis' ? 'selected' : '') ?>>Kamis</option>
                        <option value="Jumat" <?= ($selected_day === 'Jumat' ? 'selected' : '') ?>>Jumat</option>
                    </select>
                </label>
                <noscript><button type="submit">Filter</button></noscript>
            </form>
            <?php endif; ?>
            <?php if ($isStudent): ?>
            <form method="get" class="filter-form">
                <label>Pilih Hari:
                    <select name="day" onchange="this.form.submit()">
                        <option value="">-- Semua Hari --</option>
                        <option value="Senin" <?= ($selected_day === 'Senin' ? 'selected' : '') ?>>Senin</option>
                        <option value="Selasa" <?= ($selected_day === 'Selasa' ? 'selected' : '') ?>>Selasa</option>
                        <option value="Rabu" <?= ($selected_day === 'Rabu' ? 'selected' : '') ?>>Rabu</option>
                        <option value="Kamis" <?= ($selected_day === 'Kamis' ? 'selected' : '') ?>>Kamis</option>
                        <option value="Jumat" <?= ($selected_day === 'Jumat' ? 'selected' : '') ?>>Jumat</option>
                    </select>
                </label>
                <noscript><button type="submit">Filter</button></noscript>
            </form>
            <?php endif; ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <?php if (!$isStudent): ?>
                                <th>Kelas</th>
                            <?php endif; ?>
                            <th>Guru</th>
                            <th>Mata Pelajaran</th>
                            <th>Jam Mulai</th>
                            <th>Jam Selesai</th>
                            <?php if ($isAdmin): ?><th>Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $days_order = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                        if ($selected_day) {
                            $days_order = [$selected_day];
                        }   
                        foreach ($days_order as $day):
                            if (isset($grouped[$day]) && !empty($grouped[$day])):
                                usort($grouped[$day], function($a, $b) { return strcmp($a['start_time'], $b['start_time']); });
                                
                                $colspan = $isAdmin ? 6 : ($isStudent ? 4 : 5);
                        ?>
                            <tr class="day-header">
                                <th colspan="<?= $colspan ?>"><?= $day ?></th>
                            </tr>
                            <?php 
                            $last_time = "00:00:00"; 
                            foreach ($grouped[$day] as $sch):
                                if ($sch['start_time'] > "09:45:00" && $last_time <= "09:45:00"): ?>
                                    <tr><td colspan="<?= $colspan ?>" style="text-align:center; font-weight:bold; background:#f2f2f2;">Istirahat (09.45 - 10.20)</td></tr>
                                <?php endif;
                                if ($day === "Jumat" && $sch['start_time'] > "11:30:00" && $last_time <= "11:30:00"): ?>
                                    <tr><td colspan="<?= $colspan ?>" style="text-align:center; font-weight:bold; background:#ffe699;">Sholat Jumat (11.30 - 12.25)</td></tr>
                                <?php 
                                elseif ($day !== "Jumat"):
                                    if ($sch['start_time'] > "11:30:00" && $last_time <= "11:30:00"): ?>
                                        <tr><td colspan="<?= $colspan ?>" style="text-align:center; font-weight:bold; background:#ffe699;">Sholat Dhuhur (11.30 - 12.00)</td></tr>
                                    <?php endif;
                                    if ($sch['start_time'] > "12:00:00" && $last_time <= "12:00:00"): ?>
                                        <tr><td colspan="<?= $colspan ?>" style="text-align:center; font-weight:bold; background:#f2f2f2;">Istirahat (12.00 - 12.25)</td></tr>
                                    <?php endif;
                                    if ($sch['start_time'] > "14:45:00" && $last_time <= "14:45:00"): ?>
                                        <tr><td colspan="<?= $colspan ?>" style="text-align:center; font-weight:bold; background:#c6e0b4;">Sholat Ashar (14.45 - 15.10)</td></tr>
                                    <?php endif;
                                endif;
                                ?>
                                <tr>
                                    <?php if (!$isStudent): ?>
                                        <td><?= htmlspecialchars($sch['class_name']) ?></td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($sch['teacher_name']) ?></td>
                                    <td><?= htmlspecialchars($sch['subject_name']) ?></td>
                                    <td><?= htmlspecialchars(substr($sch['start_time'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars(substr($sch['end_time'], 0, 5)) ?></td>
                                    <?php if ($isAdmin): ?>
                                    <td class="action-cell">
                                        <a href="edit.php?id=<?= $sch['id'] ?>" class="edit-btn">Edit</a>
                                        <a href="#" class="delete-btn" data-id="<?= $sch['id'] ?>">Hapus</a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php 
                                $last_time = $sch['end_time'];
                            endforeach; 
                            if ($day === "Jumat") {
                                if ($last_time == "11:30:00") {
                                    echo '<tr><td colspan="'.$colspan.'" style="text-align:center; font-weight:bold; background:#ffe699;">Sholat Jumat (11.30 - 12.25)</td></tr>';
                                }
                            } else { 
                                if ($last_time == "11:30:00") {
                                    echo '<tr><td colspan="'.$colspan.'" style="text-align:center; font-weight:bold; background:#ffe699;">Sholat Dhuhur (11.30 - 12.00)</td></tr>';
                                    echo '<tr><td colspan="'.$colspan.'" style="text-align:center; font-weight:bold; background:#f2f2f2;">Istirahat (12.00 - 12.25)</td></tr>';
                                } elseif ($last_time == "12:00:00") {
                                    echo '<tr><td colspan="'.$colspan.'" style="text-align:center; font-weight:bold; background:#f2f2f2;">Istirahat (12.00 - 12.25)</td></tr>';
                                }
                                if ($last_time >= "14:45:00") {
                                    echo '<tr><td colspan="'.$colspan.'" style="text-align:center; font-weight:bold; background:#c6e0b4;">Sholat Ashar (14.45 - 15.10)</td></tr>';
                                }
                            }
                            endif; 
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h4>Konfirmasi Penghapusan</h4>
            <p>Apakah Anda yakin ingin menghapus jadwal ini?</p>
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
                const scheduleId = this.getAttribute('data-id');
                deleteModal.style.display = 'flex';
                confirmBtn.href = 'delete.php?id=' + scheduleId;
            });
        });

        cancelBtn.addEventListener('click', function() {
            deleteModal.style.display = 'none';
        });
    </script>
</body>
</html>