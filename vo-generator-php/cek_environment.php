<?php
// cek_environment.php — cek kesiapan hosting sebelum pakai
echo "=== Cek Environment Voiceover Generator ===\n\n";

echo "PHP version: " . PHP_VERSION . " (min 7.4)\n";

echo "\n-- Ekstensi yang dibutuhkan --\n";
foreach (['curl', 'json', 'session'] as $ext) {
    $ok = extension_loaded($ext);
    echo "  $ext: " . ($ok ? "OK" : "TIDAK ADA") . "\n";
}

echo "\n-- cURL HTTPS ke Google (tanpa key) --\n";
$ch = curl_init('https://generativelanguage.googleapis.com/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 15,
]);
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo "  HTTP response code: " . ($code ? $code : "0 (gagal konek)") . "\n";
echo "  curl error: " . ($err ? $err : "tidak ada") . "\n";

echo "\n-- Folder writable (untuk sesi & output) --\n";
echo "  session.save_path: " . (session_save_path() ?: '(default)') . "\n";

echo "\n-- Konfigurasi upload/output --\n";
echo "  post_max_size: " . ini_get('post_max_size') . "\n";
echo "  max_execution_time: " . ini_get('max_execution_time') . " detik\n";
echo "  memory_limit: " . ini_get('memory_limit') . "\n";

echo "\nSelesai.\n";
