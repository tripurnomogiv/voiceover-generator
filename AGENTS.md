# AGENTS.md — Voiceover Generator (Gemini TTS)

## Project Overview
Single-file FastAPI web app for generating voiceover audio from text using the free Gemini Developer API (TTS).
Also includes a PHP version (`vo-generator-php/`) for shared hosting.

## Tech Stack
- **Backend**: FastAPI + uvicorn
- **AI**: Gemini TTS via `google-genai` SDK (Interactions API, audio output)
- **Frontend**: HTML/CSS/JS inline (dark mode)
- **Config**: `.env` loaded via `python-dotenv`

## How to Run
```bash
pip install -r requirements.txt
cp .env.example .env   # isi GEMINI_API_KEY (gratis di https://aistudio.google.com/app/apikey)
python app.py
open http://localhost:8000
```

## Pipeline
```
Teks + voice + bahasa + style prompt
  → Gemini TTS (gemini-2.5-flash-preview-tts) → PCM 24kHz
  → konversi ke WAV server-side → kirim balik ke browser (play + download)
```

## API Endpoints
- `GET /` — serves the HTML UI
- `POST /generate` — send `{text, voice, language, prompt, model}` → `{wav_b64, bytes}`

## Environment Variables
Set in `.env`:
- `GEMINI_API_KEY` — required. Free API key from Google AI Studio. NO billing needed.

## Key Notes
- Free tier model: `gemini-2.5-flash-preview-tts` (input & output gratis). Alternative: `gemini-3.1-flash-tts-preview`.
- Output audio is raw PCM 16-bit 24kHz, converted to WAV in `pcm_to_wav()`.
- Voice list + language list defined in `VOICES` / `LANGS`.
- Model name overridable via `model` field in request body.
- Indonesian accent: `language_code` is NOT supported by the Interactions TTS API. To force Indonesian pronunciation, the default prompt explicitly instructs id-ID accent. Users can also set per-word overrides in the "Koreksi pelafalan" field (format `kata=ejaan` per line), applied by `apply_pronounce()` (Python) / `applyPronounce()` (PHP) before sending.

## PHP Version (`vo-generator-php/`)
- For shared hosting (PHP 7.4+), uses cURL to call the same Interactions API.
- Login: `shopee` / `affiliate` (set in `config.php`), session-based.
- WAV saved to `generated/`, play via `generate.php?play=<token>`, download via `download.php?token=<token>&name=<slug>`.
- IMPORTANT: WAV header built with `pack('A4VA4', 'RIFF', $size, 'WAVE')` — must include the `A4` format code for "WAVE", otherwise the file has no RIFF/WAVE marker and browsers can't play it.
- Free tier rate limit: ~10 requests/minute per project (429 errors expected if exceeded).
- Some short phrases may trigger Google content-policy blocks (false positives); use realistic sentences.

