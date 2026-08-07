"""
Voiceover Generator (Gemini TTS) - single file.

Flow:
  1. Ketik / tempel teks naskah di browser.
  2. Pilih suara (voice), bahasa, dan gaya (style prompt).
  3. FastAPI memanggil Gemini TTS (free tier, tanpa billing)
     -> audio .wav dikirim balik untuk diputar / diunduh.

Run:
  pip install -r requirements.txt
  cp .env.example .env   # isi GEMINI_API_KEY (gratis di aistudio.google.com)
  python app.py
  open http://localhost:8000
"""

import base64
import io
import os
import wave

import uvicorn
from dotenv import load_dotenv
from fastapi import FastAPI
from fastapi.responses import HTMLResponse, Response
from google import genai
from pydantic import BaseModel

load_dotenv()

app = FastAPI()

api_key = os.environ.get("GEMINI_API_KEY")
if not api_key or api_key == "your_api_key_here":
    print("ERROR: GEMINI_API_KEY belum diisi di .env")
    print("Ambil API key gratis: https://aistudio.google.com/app/apikey")
    raise SystemExit(1)

client = genai.Client(api_key=api_key)

# Free tier (input & output gratis), mendukung single & multi-speaker.
DEFAULT_MODEL = "gemini-2.5-flash-preview-tts"

# Suara dari Gemini-TTS (prebuilt output voices).
VOICES = [
    ("Kore", "Perempuan"),
    ("Aoede", "Perempuan"),
    ("Callirrhoe", "Perempuan"),
    ("Despina", "Perempuan"),
    ("Gacrux", "Perempuan"),
    ("Laomedeia", "Perempuan"),
    ("Leda", "Perempuan"),
    ("Sulafat", "Perempuan"),
    ("Vindemiatrix", "Perempuan"),
    ("Zephyr", "Perempuan"),
    ("Achird", "Laki-laki"),
    ("Charon", "Laki-laki"),
    ("Enceladus", "Laki-laki"),
    ("Fenrir", "Laki-laki"),
    ("Iapetus", "Laki-laki"),
    ("Puck", "Laki-laki"),
    ("Rasalgethi", "Laki-laki"),
    ("Schedar", "Laki-laki"),
    ("Umbriel", "Laki-laki"),
    ("Zubenelgenubi", "Laki-laki"),
]

LANGS = ["id-ID", "en-US"]

DEFAULT_PROMPT = (
    "Kamu adalah host video affiliate Shopee yang energik dan persuasif. "
    "Bicaralah dengan gaya voiceover promo yang ceria, jelas, dan meyakinkan "
    "sehingga penonton tertarik untuk membeli produk"
)


class TTSRequest(BaseModel):
    text: str
    voice: str = "Despina"
    language: str = "id-ID"
    prompt: str = DEFAULT_PROMPT
    model: str = DEFAULT_MODEL


@app.get("/", response_class=HTMLResponse)
def index():
    voice_options = "".join(
        f'<option value="{name}"{" selected" if name == "Despina" else ""}>{name} — {gender}</option>'
        for name, gender in VOICES
    )
    lang_options = "".join(f'<option value="{l}">{l}</option>' for l in LANGS)
    html = HTML_TEMPLATE.replace("{voices}", voice_options).replace("{langs}", lang_options)
    return HTMLResponse(html)


@app.post("/generate")
def generate(req: TTSRequest):
    if not req.text.strip():
        return {"error": "Teks tidak boleh kosong."}

    content = f"{req.prompt}: {req.text}"
    try:
        interaction = client.interactions.create(
            model=req.model,
            input=content,
            response_format={"type": "audio"},
            generation_config={"speech_config": [{"voice": req.voice}]},
        )
    except Exception as e:
        return {"error": f"Gagal generate: {e}"}

    if not interaction.output_audio or not interaction.output_audio.data:
        return {"error": "Respons tidak mengandung audio."}

    pcm = base64.b64decode(interaction.output_audio.data)
    wav_bytes = pcm_to_wav(pcm, rate=24000, channels=1, sample_width=2)
    return {
        "wav_b64": base64.b64encode(wav_bytes).decode(),
        "bytes": len(wav_bytes),
    }


def pcm_to_wav(pcm: bytes, rate: int, channels: int, sample_width: int) -> bytes:
    buf = io.BytesIO()
    with wave.open(buf, "wb") as wf:
        wf.setnchannels(channels)
        wf.setsampwidth(sample_width)
        wf.setframerate(rate)
        wf.writeframes(pcm)
    return buf.getvalue()


HTML_TEMPLATE = """<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Voiceover Generator (Gemini TTS)</title>
<style>
  :root { color-scheme: dark; }
  * { box-sizing: border-box; }
  body { font-family: system-ui, sans-serif; background: #0f1117; color: #e6e6e6; margin: 0; }
  .wrap { max-width: 720px; margin: 0 auto; padding: 32px 20px 60px; }
  h1 { font-size: 22px; margin: 0 0 4px; }
  p.sub { color: #8b93a7; margin: 0 0 24px; font-size: 13px; }
  label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 6px; color: #b6bdcc; }
  textarea, select, input[type=text] {
    width: 100%; background: #171a23; color: #e6e6e6;
    border: 1px solid #2a2f3d; border-radius: 8px; padding: 10px 12px; font-size: 14px;
  }
  textarea { min-height: 130px; resize: vertical; font-family: inherit; }
  .row { display: flex; gap: 12px; }
  .row > div { flex: 1; }
  button {
    width: 100%; margin-top: 20px; padding: 12px; font-size: 15px; font-weight: 700;
    background: #4f8cff; color: #fff; border: 0; border-radius: 8px; cursor: pointer;
  }
  button:hover { background: #6ba0ff; }
  button:disabled { opacity: .5; cursor: wait; }
  .status { margin-top: 14px; font-size: 13px; color: #ffb454; min-height: 18px; }
  .result { display: none; margin-top: 18px; background: #171a23; border: 1px solid #2a2f3d; border-radius: 8px; padding: 16px; }
  .result.show { display: block; }
  audio { width: 100%; }
  a.dl { display: inline-block; margin-top: 12px; color: #4f8cff; font-size: 13px; text-decoration: none; }
  a.dl:hover { text-decoration: underline; }
  .err { color: #ff6b6b; }
</style>
</head>
<body>
<div class="wrap">
  <h1>Voiceover Generator</h1>
  <p class="sub">Gemini TTS — gratis, tanpa billing. Tulis naskah, pilih suara, generate.</p>

  <label for="text">Naskah / Teks</label>
  <textarea id="text" placeholder="Tulis naskah voiceover di sini..."></textarea>

  <div class="row">
    <div>
      <label for="voice">Suara</label>
      <select id="voice">{voices}</select>
    </div>
    <div>
      <label for="lang">Bahasa</label>
      <select id="lang">{langs}</select>
    </div>
  </div>

  <label for="prompt">Gaya bicara (opsional)</label>
  <input id="prompt" type="text" value="Kamu adalah host video affiliate Shopee yang energik dan persuasif. Bicaralah dengan gaya voiceover promo yang ceria, jelas, dan meyakinkan sehingga penonton tertarik untuk membeli produk.">

  <button id="btn">Generate</button>
  <div class="status" id="status"></div>

  <div class="result" id="result">
    <audio id="player" controls></audio>
    <br>
    <a class="dl" id="download" href="#" download="voiceover.wav">Download .wav</a>
  </div>
</div>

<script>
const btn = document.getElementById('btn');
const statusEl = document.getElementById('status');
const resultEl = document.getElementById('result');
const player = document.getElementById('player');
const download = document.getElementById('download');

btn.addEventListener('click', async () => {
  const text = document.getElementById('text').value.trim();
  if (!text) { statusEl.textContent = 'Teks masih kosong.'; return; }

  btn.disabled = true;
  statusEl.textContent = 'Menggenerate audio...';
  resultEl.classList.remove('show');

  try {
    const res = await fetch('/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        text,
        voice: document.getElementById('voice').value,
        language: document.getElementById('lang').value,
        prompt: document.getElementById('prompt').value,
      }),
    });
    const data = await res.json();
    if (data.error) { statusEl.textContent = data.error; statusEl.classList.add('err'); return; }

    const wavUrl = 'data:audio/wav;base64,' + data.wav_b64;
    player.src = wavUrl;

    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    const ts = now.getFullYear() + pad(now.getMonth() + 1) + pad(now.getDate())
             + '-' + pad(now.getHours()) + pad(now.getMinutes()) + pad(now.getSeconds());
    download.href = wavUrl;
    download.setAttribute('download', makeSlug(text) + '-' + ts + '.wav');
    resultEl.classList.add('show');
    statusEl.textContent = 'Selesai. Audio siap diputar / diunduh.';
    statusEl.classList.remove('err');
  } catch (e) {
    statusEl.textContent = 'Error: ' + e.message;
    statusEl.classList.add('err');
  } finally {
    btn.disabled = false;
  }
});

// Ubah awal teks jadi slug untuk nama file, mis. "Mouse gaming ini..." -> "mouse-gaming"
function makeSlug(str) {
  const words = str.trim().toLowerCase().replace(/[^a-z0-9\\s]/g, ' ').split(/\\s+/).filter(Boolean);
  return words.slice(0, 3).join('-') || 'voiceover';
}
</script>
</body>
</html>
"""


if __name__ == "__main__":
    port = int(os.environ.get("PORT", "8000"))
    uvicorn.run(app, host="0.0.0.0", port=port)
