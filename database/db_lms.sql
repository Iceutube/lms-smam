-- LMS SMAM Database Schema & Demo Seed
-- Compatible with MySQL 8.0+ / MariaDB / TiDB Cloud

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
    class VARCHAR(100) NULL,
    subject VARCHAR(100) NULL,
    nis VARCHAR(50) NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS announcement_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    file_url VARCHAR(255) NULL,
    link_url VARCHAR(500) NULL,
    subject VARCHAR(100) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS material_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    subject VARCHAR(100) NOT NULL,
    duration INT NOT NULL DEFAULT 30,
    created_by INT NOT NULL,
    quiz_type ENUM('internal', 'external') NOT NULL DEFAULT 'internal',
    external_link VARCHAR(500) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    user_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 0,
    correct_count INT NOT NULL DEFAULT 0,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer CHAR(1) NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    day VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    category VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'resolved') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS php_sessions (
    id VARCHAR(128) PRIMARY KEY,
    data MEDIUMTEXT NOT NULL,
    last_access INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Demo Seed Data (Portfolio Ready)
-- ============================================

INSERT IGNORE INTO classes (id, name) VALUES
(1, 'X IPA 1'),
(2, 'X IPA 2'),
(3, 'XI IPA 1'),
(4, 'XII IPA 1');

INSERT IGNORE INTO subjects (id, name) VALUES
(1, 'Matematika'),
(2, 'Bahasa Indonesia'),
(3, 'Informatika'),
(4, 'Fisika'),
(5, 'Bahasa Inggris');

-- Demo passwords:
-- admin@smam.sch.id -> admin123
-- guru@smam.sch.id  -> guru123
-- siswa@smam.sch.id (atau NIS 2025001) -> siswa123
INSERT IGNORE INTO users (id, name, email, password, role, class, subject, nis) VALUES
(1, 'Admin SMAM', 'admin@smam.sch.id', '$2y$10$rkPGdm3.YA5C/oz0vell6ehy6P5ge6qp.RnOJypiugOLYBiVsrFIK', 'admin', NULL, NULL, NULL),
(2, 'Budi Santoso, S.Pd', 'guru@smam.sch.id', '$2y$10$Jdh0I3nPcmZfZNpdpKXGJuoZ2ECgylbmNF6uF1WVXv7.D2CbM/Zta', 'teacher', NULL, 'Informatika', NULL),
(3, 'Aisyah Putri', 'siswa@smam.sch.id', '$2y$10$T8DF85kOycykVbqActSR0.cob6NAoVrU1fns0Rz3ls30eB3ozrYzm', 'student', 'X IPA 1', NULL, '2025001');

INSERT IGNORE INTO announcements (id, title, body, created_by) VALUES
(1, 'Selamat Datang di LMS SMAM', 'Selamat datang di portal pembelajaran digital LMS SMAM. Gunakan menu Materi, Kuis, dan Jadwal untuk mengikuti kegiatan belajar mengajar.', 1),
(2, 'Jadwal Ujian Tengah Semester', 'Ujian Tengah Semester akan dilaksanakan mulai pekan depan. Pastikan seluruh materi dan latihan kuis sudah dipelajari.', 2);

INSERT IGNORE INTO announcement_classes (id, announcement_id, class_name) VALUES
(1, 1, 'X IPA 1'),
(2, 1, 'X IPA 2'),
(3, 2, 'X IPA 1');

INSERT IGNORE INTO materials (id, title, description, file_url, link_url, subject, created_by) VALUES
(1, 'Pengantar Pemrograman Web', 'Slide presentasi dasar HTML, CSS, PHP, dan MySQL untuk siswa kelas X.', 'uploads/PPT TA.pdf', 'https://developer.mozilla.org', 'Informatika', 2);

INSERT IGNORE INTO material_classes (id, material_id, class_name) VALUES
(1, 1, 'X IPA 1'),
(2, 1, 'X IPA 2');

INSERT IGNORE INTO quizzes (id, title, description, subject, duration, created_by, quiz_type, external_link, is_published) VALUES
(1, 'Kuis Dasar Informatika', 'Latihan pemahaman dasar algoritma dan pengembangan web.', 'Informatika', 15, 2, 'internal', NULL, 1);

INSERT IGNORE INTO quiz_classes (id, quiz_id, class_name) VALUES
(1, 1, 'X IPA 1');

INSERT IGNORE INTO questions (id, quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) VALUES
(1, 1, 'Bahasa markup standar yang digunakan untuk membuat struktur halaman web adalah?', 'HTML', 'SQL', 'Python', 'C++', 'A'),
(2, 1, 'Ekstensi PDO di PHP digunakan untuk?', 'Mengatur tata letak CSS', 'Menghubungkan dan berinteraksi dengan database', 'Mengompres gambar', 'Membuat animasi', 'B'),
(3, 1, 'Perintah SQL untuk mengambil data dari tabel adalah?', 'INSERT', 'UPDATE', 'SELECT', 'DELETE', 'C');

INSERT IGNORE INTO schedules (id, class_id, teacher_id, subject, day, start_time, end_time) VALUES
(1, 1, 2, 'Informatika', 'Senin', '08:00:00', '09:30:00'),
(2, 1, 2, 'Informatika', 'Rabu', '10:00:00', '11:30:00');

INSERT IGNORE INTO feedback (id, user_id, category, message, status) VALUES
(1, 3, 'Saran', 'Tampilan LMS sangat mudah digunakan. Mohon tambahkan lebih banyak latihan kuis.', 'new');
