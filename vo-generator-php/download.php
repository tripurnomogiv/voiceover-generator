<?php
// download.php — mengunduh WAV yang sudah dibuat oleh generate.php
require __DIR__ . '/config.php';

session_name($AUTH_SESSION_NAME);
session_start();

// Proteksi login
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Silakan login terlebih dahulu.']);
    exit;
}

$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
if (strlen($token) !== 32) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Token tidak valid.']);
    exit;
}

$file = __DIR__ . '/generated/' . $token . '.wav';
if (!is_file($file)) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'File tidak ditemukan atau sudah kedaluwarsa.']);
    exit;
}

// Nama file ramah: slug dari parameter name, fallback voiceover
$name = trim((string)($_GET['name'] ?? ''));
$name = preg_replace('/[^A-Za-z0-9\-_]/', '', $name);
if ($name === '') {
    $name = 'voiceover';
}
$name .= '.wav';

header('Content-Type: audio/wav');
header('Content-Length: ' . filesize($file));
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Cache-Control: no-store');
readfile($file);
exit;
