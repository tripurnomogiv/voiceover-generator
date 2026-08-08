<?php
// settings.php — simpan & baca prompt yang disunting user (disimpan ke JSON)
require __DIR__ . '/config.php';

session_name($AUTH_SESSION_NAME);
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Silakan login terlebih dahulu.']);
    exit;
}

$dir = dirname($PROMPT_FILE);
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}

// --- Simpan prompt ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = trim($_POST['prompt'] ?? '');
    if ($prompt === '') {
        // Kosong -> hapus prompt tersimpan (kembali ke default)
        @unlink($PROMPT_FILE);
        echo json_encode(['ok' => true, 'saved' => false]);
        exit;
    }
    $ok = @file_put_contents($PROMPT_FILE, json_encode(['prompt' => $prompt]));
    if ($ok === false) {
        echo json_encode(['error' => 'Gagal menyimpan prompt. Pastikan folder generated/ writable.']);
        exit;
    }
    echo json_encode(['ok' => true, 'saved' => true]);
    exit;
}

// --- Baca prompt tersimpan ---
$saved = '';
if (is_file($PROMPT_FILE)) {
    $decoded = json_decode((string)file_get_contents($PROMPT_FILE), true);
    if (is_array($decoded) && !empty($decoded['prompt'])) {
        $saved = $decoded['prompt'];
    }
}
echo json_encode(['saved' => $saved]);
exit;
