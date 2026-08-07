# AGENTS.md — Voiceover Generator (Gemini TTS)

## Project Overview
Single-file FastAPI web app for generating voiceover audio from text using the free Gemini Developer API (TTS).

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
