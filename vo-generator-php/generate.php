<?php
// generate.php — menerima POST, memanggil Gemini TTS, mengembalikan WAV biner
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

// --- Validasi input ---
$text = trim($_POST['text'] ?? '');
if ($text === '') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Teks tidak boleh kosong.']);
    exit;
}
$voice   = $_POST['voice']   ?? $DEFAULT_VOICE;
$lang    = $_POST['language'] ?? $DEFAULT_LANG;
$prompt  = trim($_POST['prompt'] ?? $DEFAULT_PROMPT);
if ($prompt === '') $prompt = $DEFAULT_PROMPT;

// Pastikan voice/language valid (fallback ke default)
if (!isset($VOICES[$voice])) $voice = $DEFAULT_VOICE;
if (!in_array($lang, $LANGS)) $lang = $DEFAULT_LANG;

// --- Susun request ke Interactions API (output audio) ---
$content = $prompt . ': ' . $text;

$body = [
    'model' => $MODEL,
    'input' => $content,
    'response_format' => ['type' => 'audio'],
    'generation_config' => [
        'speech_config' => [
            ['voice' => $voice],
        ],
    ],
];

$url = 'https://generativelanguage.googleapis.com/v1beta/interactions?key=' . urlencode($GEMINI_API_KEY);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($body),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Gagal terhubung ke API: ' . $curlErr]);
    exit;
}

$data = json_decode($response, true);
if ($httpCode !== 200) {
    $msg = $data['error']['message'] ?? ('HTTP ' . $httpCode);
    if ($httpCode === 429) {
        $msg = 'Batas harian tercapai (429). Coba lagi besok atau tunggu beberapa menit.';
    }
    header('Content-Type: application/json');
    echo json_encode(['error' => $msg]);
    exit;
}

// Audio PCM 16-bit (base64) di dalam steps[].content[].data
$audioBase64 = null;
foreach ($data['steps'] ?? [] as $step) {
    foreach ($step['content'] ?? [] as $part) {
        if (!empty($part['data'])) {
            $audioBase64 = $part['data'];
            break 2;
        }
    }
}
// Fallback: struktur lama (outputAudio.data)
if (!$audioBase64) {
    $audioBase64 = $data['outputAudio']['data'] ?? null;
}

if (!$audioBase64) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Respons tidak mengandung audio.']);
    exit;
}

// Decode PCM 16-bit 24kHz, bungkus jadi WAV agar bisa diputar browser
$pcm = base64_decode($audioBase64);
$wav = pcmToWav($pcm, 24000, 1, 2);

// Kirim WAV langsung sebagai file biner
header('Content-Type: audio/wav');
header('Content-Length: ' . strlen($wav));
header('Content-Disposition: inline; filename="voiceover.wav"');
echo $wav;
exit;

function pcmToWav(string $pcm, int $rate, int $channels, int $sampleWidth): string
{
    $dataSize = strlen($pcm);
    $byteRate = $rate * $channels * $sampleWidth;
    $blockAlign = $channels * $sampleWidth;

    $riff = pack('A4V', 'RIFF', 36 + $dataSize, 'WAVE');
    $fmt  = pack('A4VvvVVvv', 'fmt ', 16, 1, $channels, $rate, $byteRate, $blockAlign, $sampleWidth * 8);
    $data = pack('A4V', 'data', $dataSize);

    return $riff . $fmt . $data . $pcm;
}
