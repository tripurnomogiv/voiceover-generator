<?php
// config.php — setelan utama Voiceover Generator (Gemini TTS)
// Kompatibel dengan PHP 7.4.33

// API key gratis dari https://aistudio.google.com/app/apikey
// Tanpa billing, tanpa Google Cloud.
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
