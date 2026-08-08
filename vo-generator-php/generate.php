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

// GET ?play=token -> putar WAV yang sudah disimpan (inline)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['play'])) {
    $token = preg_replace('/[^a-f0-9]/', '', $_GET['play']);
    if (strlen($token) === 32) {
        $saved = __DIR__ . '/generated/' . $token . '.wav';
        if (is_file($saved)) {
            header('Content-Type: audio/wav');
            header('Content-Length: ' . filesize($saved));
            header('Content-Disposition: inline; filename="voiceover.wav"');
            readfile($saved);
            exit;
        }
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'File tidak ditemukan atau sudah kedaluwarsa.']);
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
$pronounce = trim($_POST['pronounce'] ?? '');
if ($pronounce !== '') {
    $text = applyPronounce($text, $pronounce);
}

// Pastikan voice/language valid (fallback ke default)
if (!isset($VOICES[$voice])) $voice = $DEFAULT_VOICE;
if (!in_array($lang, $LANGS)) $lang = $DEFAULT_LANG;

// --- Susun request ke streamGenerateContent (format playground resmi) ---
// Prompt dijadikan bagian teks: gaya & instruksi aksen lalu transcript.
$content = $prompt . "\n\n## Transcript:\n" . $text;

$body = [
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => $content]]],
    ],
    'generationConfig' => [
        'responseModalities' => ['audio'],
        'temperature' => 1,
        'speech_config' => [
            'voice_config' => [
                'prebuilt_voice_config' => ['voice_name' => $voice],
            ],
        ],
    ],
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/'
     . $MODEL . ':streamGenerateContent?key=' . urlencode($GEMINI_API_KEY);

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

// Respons streamGenerateContent = array chunk.
// Audio base64 di candidates[].content.parts[].inlineData.data (gabungkan semua).
$rate = 24000;
$audioBase64 = '';
foreach ((array)$data as $chunk) {
    foreach ($chunk['candidates'] ?? [] as $cand) {
        foreach ($cand['content']['parts'] ?? [] as $part) {
            $inline = $part['inlineData'] ?? null;
            if ($inline && !empty($inline['data'])) {
                $audioBase64 .= $inline['data'];
                if (isset($inline['mimeType']) && preg_match('/rate=(\d+)/', $inline['mimeType'], $m)) {
                    $rate = (int)$m[1];
                }
            }
        }
    }
}
// Fallback: non-stream struktur (parts[].inlineData)
if ($audioBase64 === '' && isset($data['candidates'][0]['content']['parts'][0]['inlineData']['data'])) {
    $audioBase64 = $data['candidates'][0]['content']['parts'][0]['inlineData']['data'];
}

if ($audioBase64 === '') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Respons tidak mengandung audio.']);
    exit;
}

// Decode PCM 16-bit, bungkus jadi WAV agar bisa diputar browser
$pcm = base64_decode($audioBase64);
$wav = pcmToWav($pcm, $rate, 1, 2);

// Simpan WAV ke folder generated/ (diblokir akses langsung via .htaccess)
$dir = __DIR__ . '/generated';
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
$token = bin2hex(random_bytes(16));
$file  = $dir . '/' . $token . '.wav';
if (file_put_contents($file, $wav) === false) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Gagal menyimpan audio di server. Pastikan folder generated/ writable.']);
    exit;
}

// Catat ke history agar bisa list + download ulang
addHistory($token, $text, $voice, $lang, $rate, strlen($wav));

// Bersihkan file lama (lebih dari masa simpan) agar tidak menumpuk
$maxAge = $WAV_MAX_AGE;
foreach (glob($dir . '/*.wav') as $old) {
    if (filemtime($old) < time() - $maxAge) {
        @unlink($old);
    }
}

header('Content-Type: application/json');
echo json_encode([
    'token' => $token,
    'name'  => 'voiceover.wav',
    'size'  => strlen($wav),
]);
exit;

function addHistory(string $token, string $text, string $voice, string $lang, int $rate, int $size): void
{
    global $HISTORY_FILE, $HISTORY_LIMIT;

    $list = [];
    if (is_file($HISTORY_FILE)) {
        $decoded = json_decode((string)file_get_contents($HISTORY_FILE), true);
        if (is_array($decoded)) {
            $list = $decoded;
        }
    }

    array_unshift($list, [
        'token'   => $token,
        'text'    => mb_substr(preg_replace('/\s+/u', ' ', $text), 0, 120),
        'voice'   => $voice,
        'lang'    => $lang,
        'rate'    => $rate,
        'size'    => $size,
        'created' => time(),
    ]);

    $list = array_slice($list, 0, $HISTORY_LIMIT);
    @file_put_contents($HISTORY_FILE, json_encode($list));
}

function pcmToWav(string $pcm, int $rate, int $channels, int $sampleWidth): string
{
    $dataSize = strlen($pcm);
    $byteRate = $rate * $channels * $sampleWidth;
    $blockAlign = $channels * $sampleWidth;

    $riff = pack('A4VA4', 'RIFF', 36 + $dataSize, 'WAVE');
    $fmt  = pack('A4VvvVVvv', 'fmt ', 16, 1, $channels, $rate, $byteRate, $blockAlign, $sampleWidth * 8);
    $data = pack('A4V', 'data', $dataSize);

    return $riff . $fmt . $data . $pcm;
}

function applyPronounce(string $text, string $rules): string
{
    foreach (preg_split('/\r?\n/', $rules) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '=') === false) {
            continue;
        }
        [$kata, $ejaan] = array_map('trim', explode('=', $line, 2));
        if ($kata === '' || $ejaan === '') {
            continue;
        }
        // Ganti kata utuh (whole word), semua kemunculan, abaikan huruf kapital
        $text = preg_replace('/\b' . preg_quote($kata, '/') . '\b/iu', $ejaan, $text);
    }
    return $text;
}
