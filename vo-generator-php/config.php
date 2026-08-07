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

// Model gratis (input & output). Hemat: gemini-2.5-flash-preview-tts
// Alternatif: gemini-3.1-flash-tts-preview
$MODEL = 'gemini-2.5-flash-preview-tts';

// Suara default (host video affiliate Shopee, energik & persuasif)
$DEFAULT_VOICE = 'Despina';
$DEFAULT_LANG  = 'id-ID';
$DEFAULT_PROMPT = 'Kamu adalah host video affiliate Shopee yang energik dan persuasif. Bicaralah dengan gaya voiceover promo yang ceria, jelas, dan meyakinkan sehingga penonton tertarik untuk membeli produk.';

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
