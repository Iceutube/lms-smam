<?php
session_start();
require 'includes/db.php'; 
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Akses ditolak. Anda harus login untuk mengunduh file.");
}


$upload_dir = __DIR__ . "/uploads/"; 

$filename = basename($_GET['file'] ?? '');

if (strpos($filename, 'uploads/') === 0) {
    $filename = substr($filename, strlen('uploads/'));
}

$allowed_extensions = ['pdf', 'ppt', 'pptx'];
$file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    http_response_code(400);
    die("Format file tidak didukung untuk diunduh.");
}

$file_path = $upload_dir . $filename; 

if (!file_exists($file_path)) {
    http_response_code(404);
    die("File materi tidak ditemukan.");
}

if ($file_extension === 'pdf') {
    $mime_type = 'application/pdf';
} elseif ($file_extension === 'ppt') {
    $mime_type = 'application/vnd.ms-powerpoint';
} elseif ($file_extension === 'pptx') {
    $mime_type = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
} else {
    $mime_type = 'application/octet-stream';
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type); 
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit;
?>