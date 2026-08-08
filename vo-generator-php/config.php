<?php
// config.php — setelan utama Voiceover Generator (Gemini TTS)
// Kompatibel dengan PHP 7.4.33

// API key gratis dari https://aistudio.google.com/app/apikey
// Tanpa billing, tanpa Google Cloud.
// Bisa isi 3 key: saat key 1 kena limit (429), otomatis pakai key 2, lalu key 3.
$GEMINI_API_KEYS = [
    'KEY1',
    'KEY2',
    'KEY3',
];
// Backward-compat: jika hanya pakai satu key, bisa juga set di variabel ini.
$GEMINI_API_KEY = 'your_api_key_here';

// --- Login sederhana ---
$AUTH_USER = 'shopee';
$AUTH_PASS = 'affiliate';
$AUTH_SESSION_NAME = 'vo_gen_auth';

// Model gratis (input & output). Disarankan: gemini-3.1-flash-tts-preview
// Alternatif: gemini-2.5-flash-preview-tts
$MODEL = 'gemini-3.1-flash-tts-preview';

// Suara default (host video affiliate Shopee, energik & persuasif)
$DEFAULT_VOICE = 'Despina';
$DEFAULT_LANG  = 'id-ID';
$DEFAULT_PROMPT = 'Read the following transcript based on the director\'s note.

# Director\'s note
Style: The "Vocal Smile": soft palate raised, tone bright, sunny, explicitly inviting.
Pace: Fast, energetic, no dead air. Sentences overlap slightly.
Accent: Indonesian (Bahasa Indonesia, id-ID) — pronounce letters following Indonesian phonetics (e.g. "produk" said as pro-duk), NOT English accent.

## Scene:
menceritakan kelebihan produk dan persuasif agar orang tertarik beli';

// Daftar suara (nama => gender)
$VOICES = [
    'Kore' => 'Perempuan',
    'Aoede' => 'Perempuan',
    'Callirrhoe' => 'Perempuan',
    'Despina' => 'Perempuan',
    'Gacrux' => 'Perempuan',
    'Laomedeia' => 'Perempuan',
    'Leda' => 'Perempuan',
    'Sulafat' => 'Perempuan',
    'Vindemiatrix' => 'Perempuan',
    'Zephyr' => 'Perempuan',
    'Achird' => 'Laki-laki',
    'Charon' => 'Laki-laki',
    'Enceladus' => 'Laki-laki',
    'Fenrir' => 'Laki-laki',
    'Iapetus' => 'Laki-laki',
    'Puck' => 'Laki-laki',
    'Rasalgethi' => 'Laki-laki',
    'Schedar' => 'Laki-laki',
    'Umbriel' => 'Laki-laki',
    'Zubenelgenubi' => 'Laki-laki',
];

// Bahasa yang didukung
$LANGS = ['id-ID', 'en-US'];

// --- History hasil generate ---
// File JSON berisi daftar audio yang sudah dibuat (untuk list + download ulang)
$HISTORY_FILE = __DIR__ . '/generated/history.json';
// Masa simpan WAV (detik). Default 7 hari, agar history tetap bisa diunduh ulang.
$WAV_MAX_AGE = 7 * 24 * 60 * 60;
// Jumlah maksimal entri history yang disimpan
$HISTORY_LIMIT = 200;

// --- Prompt tersimpan (disunting user) ---
// File JSON berisi prompt yang disimpan user via tombol "Simpan Prompt".
// Jika ada, prompt ini dipakai sebagai default (menggantikan $DEFAULT_PROMPT).
$PROMPT_FILE = __DIR__ . '/generated/prompt.json';
