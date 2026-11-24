<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

function navigationItem(string $href, string $icon, string $label, string $currentPath): string {
    $target = ltrim(str_replace('../', '/', $href), '/');
    $isActive = str_ends_with($currentPath, $target);
    $activeClass = $isActive ? ' class="active"' : '';
    $ariaCurrent = $isActive ? ' aria-current="page"' : '';

    return '<li><a href="' . $href . '"' . $activeClass . $ariaCurrent . '><i class="' . $icon . '" aria-hidden="true"></i><span>' . $label . '</span></a></li>';
}
?>
<button class="mobile-nav-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" aria-label="Buka navigasi">
    <i class="fas fa-bars" aria-hidden="true"></i>
</button>
<div class="sidebar-overlay" aria-hidden="true"></div>
<nav class="sidebar" id="primary-navigation" aria-label="Navigasi utama">
    <div class="sidebar-header">
        <img src="../assets/img/Logo_Smam.png" alt="Logo SMAM">
    </div>

    <div class="sidebar-user">
        <i class="fas fa-user-circle user-icon" aria-hidden="true"></i>
        <span><?= htmlspecialchars($_SESSION['name']) ?></span>
        <small><?= ucfirst(htmlspecialchars($_SESSION['role'])) ?></small>
    </div>

    <ul class="sidebar-menu">
        <?php
        $role = $_SESSION['role'];

        if ($role === 'admin') {
            echo navigationItem('../dashboard/dashboard_admin.php', 'fas fa-home', 'Dashboard', $currentPath);
            echo navigationItem('../users/list.php', 'fas fa-users', 'Kelola User', $currentPath);
            echo navigationItem('../announcements/list.php', 'fas fa-bullhorn', 'Pengumuman', $currentPath);
            echo navigationItem('../materials/list.php', 'fas fa-book', 'Materi', $currentPath);
            echo navigationItem('../quiz/list.php', 'fas fa-question-circle', 'Kuis', $currentPath);
            echo navigationItem('../schedules/list.php', 'fas fa-calendar-alt', 'Jadwal', $currentPath);
            echo navigationItem('../feedback/list.php', 'fas fa-comment-dots', 'Feedback', $currentPath);
        } elseif ($role === 'teacher') {
            echo navigationItem('../dashboard/dashboard_teacher.php', 'fas fa-home', 'Dashboard', $currentPath);
            echo navigationItem('../announcements/list.php', 'fas fa-bullhorn', 'Pengumuman', $currentPath);
            echo navigationItem('../materials/list.php', 'fas fa-book', 'Materi', $currentPath);
            echo navigationItem('../quiz/list.php', 'fas fa-question-circle', 'Kuis', $currentPath);
            echo navigationItem('../schedules/list.php', 'fas fa-calendar-alt', 'Jadwal', $currentPath);
            echo navigationItem('../feedback/list.php', 'fas fa-comment-dots', 'Feedback', $currentPath);
        } elseif ($role === 'student') {
            echo navigationItem('../dashboard/dashboard_student.php', 'fas fa-home', 'Dashboard', $currentPath);
            echo navigationItem('../announcements/list.php', 'fas fa-bullhorn', 'Pengumuman', $currentPath);
            echo navigationItem('../materials/list.php', 'fas fa-book', 'Materi', $currentPath);
            echo navigationItem('../quiz/list.php', 'fas fa-question-circle', 'Kuis', $currentPath);
            echo navigationItem('../schedules/list.php', 'fas fa-calendar-alt', 'Jadwal', $currentPath);
            echo navigationItem('../feedback/add.php', 'fas fa-comment-dots', 'Kirim Feedback', $currentPath);
        }
        ?>
    </ul>

    <div class="sidebar-footer">
        <?php if ($role === 'student'): ?>
            <a href="../profile/settings_student.php"><i class="fas fa-cog" aria-hidden="true"></i><span>Pengaturan Akun</span></a>
        <?php elseif ($role === 'teacher'): ?>
            <a href="../profile/settings_teacher.php"><i class="fas fa-cog" aria-hidden="true"></i><span>Pengaturan Akun</span></a>
        <?php endif; ?>
        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt" aria-hidden="true"></i><span>Logout</span></a>
    </div>
</nav>
<script>
(() => {
    const toggle = document.querySelector('.mobile-nav-toggle');
    const overlay = document.querySelector('.sidebar-overlay');
    const navigation = document.getElementById('primary-navigation');
    if (!toggle || !overlay || !navigation) return;

    const closeNavigation = () => {
        document.body.classList.remove('nav-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Buka navigasi');
    };
    const openNavigation = () => {
        document.body.classList.add('nav-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Tutup navigasi');
        navigation.querySelector('a')?.focus();
    };

    toggle.addEventListener('click', () => document.body.classList.contains('nav-open') ? closeNavigation() : openNavigation());
    overlay.addEventListener('click', closeNavigation);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeNavigation(); });
    navigation.querySelectorAll('a').forEach(link => link.addEventListener('click', closeNavigation));
})();
</script>
