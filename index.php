<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}
switch ($_SESSION['role']) {
    case 'admin':
        header("Location: dashboard/dashboard_admin.php");
        break;
    case 'teacher':
        header("Location: dashboard/dashboard_teacher.php");
        break;
    case 'student':
        header("Location: dashboard/dashboard_student.php");
        break;
    default:
        header("Location: auth/login.php");
        break;
}
exit;
