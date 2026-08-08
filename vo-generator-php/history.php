<?php
// history.php — daftar audio yang sudah dibuat (login wajib)
require __DIR__ . '/config.php';

session_name($AUTH_SESSION_NAME);
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login terlebih dahulu.']);
    exit;
}

// --- Hapus satu entri + file WAV ---
if (isset($_GET['delete'])) {
    $token = preg_replace('/[^a-f0-9]/', '', $_GET['delete']);
    if (strlen($token) !== 32) {
        http_response_code(400);
        echo json_encode(['error' => 'Token tidak valid.']);
        exit;
    }
    @unlink(__DIR__ . '/generated/' . $token . '.wav');

    $list = [];
    if (is_file($HISTORY_FILE)) {
        $decoded = json_decode((string)file_get_contents($HISTORY_FILE), true);
        if (is_array($decoded)) {
            $list = $decoded;
        }
    }
    $list = array_values(array_filter($list, function ($e) use ($token) {
        return ($e['token'] ?? '') !== $token;
    }));
    @file_put_contents($HISTORY_FILE, json_encode($list));

    echo json_encode(['ok' => true]);
    exit;
}

// --- Daftar history (hanya yang file WAV-nya masih ada) ---
$list = [];
if (is_file($HISTORY_FILE)) {
    $decoded = json_decode((string)file_get_contents($HISTORY_FILE), true);
    if (is_array($decoded)) {
        $list = $decoded;
    }
}

$out = [];
foreach ($list as $e) {
    $token = $e['token'] ?? '';
    $file  = __DIR__ . '/generated/' . $token . '.wav';
    if ($token === '' || !is_file($file)) {
        continue;
    }
    $out[] = $e;
}

echo json_encode($out);
exit;
