<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
require '../includes/db.php';

$classes = $pdo->query("SELECT * FROM classes")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query("SELECT id, name, subject FROM users WHERE role='teacher'")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll(PDO::FETCH_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id   = isset($_POST['class_id']) ? (int)$_POST['class_id'] : null;
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : null;
    $day        = isset($_POST['day']) ? $_POST['day'] : null;
    $start_time = isset($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time   = isset($_POST['end_time']) ? $_POST['end_time'] : null;
    
    if (!$class_id || !$teacher_id || !$day || !$start_time || !$end_time) {
        $error = "Lengkapi semua field yang wajib.";
    } else {
        try {
            $stmtS = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
            $stmtS->execute([$subject_id]);
            $subject_name = $stmtS->fetchColumn() ?: '';
            
            $sql = "INSERT INTO schedules (class_id, teacher_id, subject, day, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$class_id, $teacher_id, $subject_name, $day, $start_time, $end_time];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal menyimpan jadwal: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Jadwal</title>
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
            <h2>Tambah Jadwal Pelajaran</h2>
            <?php if ($error): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Kelas:</label>
                    <select name="class_id" id="class_id" class="select-search" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Guru:</label>
                    <select name="teacher_id" id="teacher_id" class="select-search" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>" data-subject-name="<?= htmlspecialchars($t['subject']) ?>">
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['subject']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mata Pelajaran:</label>
                    <select name="subject_id" id="subject_id" class="select-search" required>
                        <option value="">-- Pilih Mapel --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hari:</label>
                    <select name="day" required>
                        <option>Senin</option>
                        <option>Selasa</option>
                        <option>Rabu</option>
                        <option>Kamis</option>
                        <option>Jumat</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jam Mulai:</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>Jam Selesai:</label>
                    <input type="time" name="end_time" required>
                </div>
                <button type="submit">Simpan</button>
            </form>
        </div>
    </main>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select-search').select2({
            width: '100%'
        });
        $('#teacher_id').on('change', function() {
            const subjectName = $(this).find(':selected').data('subject-name');
            
            if (subjectName) {
                let found = false;
                $('#subject_id option').each(function() {
                    if ($(this).text().trim().toLowerCase() === subjectName.trim().toLowerCase()) {
                        $('#subject_id').val($(this).val()).trigger('change');
                        found = true;
                    }
                });
                if (!found) {
                    $('#subject_id').val('').trigger('change');
                }
            } else {
                $('#subject_id').val('').trigger('change');
            }
        });
    });
    </script>
</body>
</html>