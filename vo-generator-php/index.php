<?php
require __DIR__ . '/config.php';

session_name($AUTH_SESSION_NAME);
session_start();

// Proteksi login
if (empty($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$voiceOptions = '';
foreach ($VOICES as $name => $gender) {
    $sel = ($name === $DEFAULT_VOICE) ? ' selected' : '';
    $voiceOptions .= '<option value="' . htmlspecialchars($name) . '"' . $sel . '>'
        . htmlspecialchars($name) . ' — ' . htmlspecialchars($gender) . '</option>';
}
$langOptions = '';
foreach ($LANGS as $l) {
    $sel = ($l === $DEFAULT_LANG) ? ' selected' : '';
    $langOptions .= '<option value="' . htmlspecialchars($l) . '"' . $sel . '>' . htmlspecialchars($l) . '</option>';
}
?>
<!DOCTYPE html>
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
  .top { display: flex; align-items: center; justify-content: space-between; }
  a.logout { color: #8b93a7; font-size: 13px; text-decoration: none; }
  a.logout:hover { color: #ff6b6b; }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <h1>Voiceover Generator</h1>
      <p class="sub">Gemini TTS — gratis, tanpa billing. Tulis naskah, pilih suara, generate.</p>
    </div>
    <a class="logout" href="login.php?logout=1">Logout</a>
  </div>

  <label for="text">Naskah / Teks</label>
  <textarea id="text" placeholder="Tulis naskah voiceover di sini..."></textarea>

  <div class="row">
    <div>
      <label for="voice">Suara</label>
      <select id="voice"><?php echo $voiceOptions; ?></select>
    </div>
    <div>
      <label for="lang">Bahasa</label>
      <select id="lang"><?php echo $langOptions; ?></select>
    </div>
  </div>

  <label for="prompt">Gaya bicara (opsional)</label>
  <input id="prompt" type="text" value="<?php echo htmlspecialchars($DEFAULT_PROMPT); ?>">

  <label for="pronounce">Koreksi pelafalan (opsional) — format: <code>kata=ejaan</code> tiap baris</label>
  <textarea id="pronounce" rows="3" placeholder="produk=pro-duk&#10;gudang=gu-dang" style="min-height:70px"></textarea>

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
  statusEl.classList.remove('err');

  const form = new URLSearchParams();
  form.append('text', text);
  form.append('voice', document.getElementById('voice').value);
  form.append('language', document.getElementById('lang').value);
  form.append('prompt', document.getElementById('prompt').value);
  form.append('pronounce', document.getElementById('pronounce').value);

  try {
    const res = await fetch('generate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: form,
    });
    const data = await res.json();

    if (data.error) {
      statusEl.textContent = data.error;
      statusEl.classList.add('err');
      return;
    }

    // Sukses -> play langsung dari generate.php, download lewat download.php
    player.src = 'generate.php?play=' + data.token + '&name=' + encodeURIComponent(makeSlug(text));
    download.href = 'download.php?token=' + data.token + '&name=' + encodeURIComponent(makeSlug(text));
    resultEl.classList.add('show');
    statusEl.textContent = 'Selesai. Audio siap diputar / diunduh.';
  } catch (e) {
    statusEl.textContent = 'Error: ' + e.message;
    statusEl.classList.add('err');
  } finally {
    btn.disabled = false;
  }
});

// Ubah awal teks jadi slug untuk nama file, mis. "Mouse gaming ini..." -> "mouse-gaming"
function makeSlug(str) {
  const words = str.trim().toLowerCase().replace(/[^a-z0-9\s]/g, ' ').split(/\s+/).filter(Boolean);
  return words.slice(0, 3).join('-') || 'voiceover';
}
</script>
</body>
</html>
