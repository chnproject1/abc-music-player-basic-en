<?php
// ──────────────────────────────────────────
//  abcMusic — Basic delivery player (EN)
//  play.abcmusic.tech/{uuid}
// ──────────────────────────────────────────

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$uuid = preg_replace('/[^a-f0-9\-]/i', '', $path);

if (strlen($uuid) !== 36) {
    http_response_code(404);
    die('Song not found.');
}

define('SUPABASE_URL',   'https://baltzukuszagxcgkfrpi.supabase.co');
define('SUPABASE_KEY',   'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJhbHR6dWt1c3phZ3hjZ2tmcnBpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzczMTg4MjMsImV4cCI6MjA5Mjg5NDgyM30.gcRHTzssV3OsbObvnpnbROrrpA8Dn6zZz9j_qDJdw0s');
define('SUPABASE_TABLE', 'presentes');

$api  = SUPABASE_URL . '/rest/v1/' . SUPABASE_TABLE . '?uuid=eq.' . urlencode($uuid) . '&limit=1';
$ch   = curl_init($api);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'apikey: '               . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
    ],
]);
$resp = curl_exec($ch);
curl_close($ch);

$rows = json_decode($resp, true);
if (empty($rows)) {
    http_response_code(404);
    die('Song not found.');
}

$m            = $rows[0];
$audio_url    = htmlspecialchars($m['audio_url'] ?? '');
$raw_url      = $m['audio_url'] ?? '';
$name         = $m['nome'] ?? $m['nome_presenteado'] ?? 'my-song';
$download_url = '/download.php?url=' . urlencode($raw_url) . '&filename=' . urlencode($name . '.mp3');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta property="og:title"       content="Your special song 🎵">
  <meta property="og:description" content="A song made just for you by abcMusic.">
  <meta name="theme-color"        content="#0d1a12">
  <title>Your song — abcMusic</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100dvh;
      background: #0d1a12;
      color: #f0faf4;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    .content {
      width: 100%;
      max-width: 400px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
    }

    /* ── Logo ── */
    .brand {
      font-size: 13px;
      font-weight: 600;
      letter-spacing: 0.12em;
      color: #34d399;
      text-transform: uppercase;
      text-decoration: none;
      margin-bottom: 8px;
    }

    /* ── Animated icon ── */
    .music-icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: rgba(52, 211, 153, 0.1);
      border: 1px solid rgba(52, 211, 153, 0.25);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 4px;
    }
    .music-icon svg {
      fill: #34d399;
    }
    .music-icon.playing {
      animation: pulse 1.8s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { box-shadow: 0 0 0 0 rgba(52,211,153,0.25); }
      50%       { box-shadow: 0 0 0 14px rgba(52,211,153,0); }
    }

    /* ── Wave bars (only visible while playing) ── */
    .wave {
      display: none;
      align-items: flex-end;
      gap: 3px;
      height: 20px;
      margin-bottom: 4px;
    }
    .wave.playing { display: flex; }
    .wave span {
      width: 3px;
      border-radius: 2px;
      background: #34d399;
      animation: wave var(--d) ease-in-out infinite alternate;
    }
    @keyframes wave { from { height: 3px; } to { height: var(--h); } }

    /* ── Player ── */
    .player {
      width: 100%;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(52,211,153,0.15);
      border-radius: 20px;
      padding: 24px 20px 20px;
    }

    .progress-area {
      margin-bottom: 18px;
      cursor: pointer;
    }
    .progress-bar {
      width: 100%;
      height: 4px;
      background: rgba(255,255,255,0.1);
      border-radius: 2px;
      overflow: hidden;
      margin-bottom: 8px;
    }
    .progress-fill {
      height: 100%;
      width: 0%;
      background: #34d399;
      border-radius: 2px;
      transition: width 0.3s linear;
    }
    .time-row {
      display: flex;
      justify-content: space-between;
      font-size: 11px;
      color: rgba(255,255,255,0.35);
      font-variant-numeric: tabular-nums;
    }

    .controls {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 20px;
    }
    .btn-skip {
      background: none;
      border: none;
      cursor: pointer;
      padding: 8px;
      color: rgba(255,255,255,0.4);
      transition: color 0.15s;
      display: flex;
      align-items: center;
    }
    .btn-skip:hover { color: #34d399; }

    .btn-play {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: #34d399;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.1s, background 0.15s;
      flex-shrink: 0;
    }
    .btn-play:hover  { background: #2ebd87; }
    .btn-play:active { transform: scale(0.95); }
    .btn-play svg    { fill: #0d1a12; }

    /* ── Download button ── */
    .btn-download {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 14px 20px;
      background: #34d399;
      border: none;
      border-radius: 14px;
      color: #0d1a12;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.15s, transform 0.1s;
    }
    .btn-download:hover  { background: #2ebd87; }
    .btn-download:active { transform: scale(0.98); }
    .btn-download:disabled { opacity: 0.6; cursor: default; }

    /* ── Footer CTA ── */
    footer {
      font-size: 13px;
      color: rgba(255,255,255,0.45);
      text-align: center;
      margin-top: 8px;
    }
    footer a {
      color: #34d399;
      font-weight: 600;
      text-decoration: none;
      transition: color 0.15s;
    }
    footer a:hover { color: #6ee7b7; text-decoration: underline; }

    audio { display: none; }
  </style>
</head>
<body>
<div class="content">

  <a class="brand" href="https://abcmusic.tech" target="_blank">abcMusic</a>

  <!-- Animated icon -->
  <div class="music-icon" id="musicIcon">
    <svg width="32" height="32" viewBox="0 0 24 24">
      <path d="M12 3v10.55A4 4 0 1 0 14 17V7h4V3h-6z"/>
    </svg>
  </div>

  <!-- Wave bars -->
  <div class="wave" id="wave">
    <span style="--d:.5s;--h:14px"></span>
    <span style="--d:.7s;--h:20px"></span>
    <span style="--d:.4s;--h:10px"></span>
    <span style="--d:.6s;--h:18px"></span>
    <span style="--d:.45s;--h:12px"></span>
    <span style="--d:.65s;--h:16px"></span>
    <span style="--d:.55s;--h:8px"></span>
  </div>

  <!-- Player -->
  <div class="player">
    <audio id="audio" src="<?= $audio_url ?>" preload="metadata"></audio>

    <div class="progress-area" id="progressArea">
      <div class="progress-bar">
        <div class="progress-fill" id="progressFill"></div>
      </div>
      <div class="time-row">
        <span id="timeNow">0:00</span>
        <span id="timeDur">0:00</span>
      </div>
    </div>

    <div class="controls">
      <button class="btn-skip" onclick="seek(-10)" aria-label="Rewind 10s">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M11 17l-5-5 5-5"/><path d="M18 17l-5-5 5-5"/>
        </svg>
      </button>

      <button class="btn-play" id="playBtn" onclick="togglePlay()" aria-label="Play/Pause">
        <svg id="iconPlay" width="28" height="28" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        <svg id="iconPause" width="28" height="28" viewBox="0 0 24 24" style="display:none"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
      </button>

      <button class="btn-skip" onclick="seek(10)" aria-label="Forward 10s">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M13 17l5-5-5-5"/><path d="M6 17l5-5-5-5"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- Download button -->
  <button class="btn-download" id="btnDownload" onclick="downloadSong()">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    Download your song
  </button>

  <footer>Loved it? Make another one at <a href="https://abcmusic-quiz-us.netlify.app/?utm_source=link_pagina_entrega" target="_blank" rel="noopener">abcMusic</a></footer>

</div>

<script>
  const audio      = document.getElementById('audio');
  const fill       = document.getElementById('progressFill');
  const timeNow    = document.getElementById('timeNow');
  const timeDur    = document.getElementById('timeDur');
  const iconPlay   = document.getElementById('iconPlay');
  const iconPause  = document.getElementById('iconPause');
  const musicIcon  = document.getElementById('musicIcon');
  const wave       = document.getElementById('wave');

  function fmt(s) {
    s = Math.floor(s || 0);
    return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
  }

  audio.addEventListener('loadedmetadata', () => {
    timeDur.textContent = fmt(audio.duration);
  });

  audio.addEventListener('timeupdate', () => {
    if (!audio.duration) return;
    fill.style.width = (audio.currentTime / audio.duration * 100) + '%';
    timeNow.textContent = fmt(audio.currentTime);
  });

  audio.addEventListener('ended', () => setPlaying(false));

  function setPlaying(playing) {
    iconPlay.style.display  = playing ? 'none' : '';
    iconPause.style.display = playing ? ''     : 'none';
    musicIcon.classList.toggle('playing', playing);
    wave.classList.toggle('playing', playing);
  }

  function togglePlay() {
    if (audio.paused) { audio.play(); setPlaying(true); }
    else              { audio.pause(); setPlaying(false); }
  }

  function seek(d) {
    audio.currentTime = Math.max(0, Math.min(audio.duration || 0, audio.currentTime + d));
  }

  document.getElementById('progressArea').addEventListener('click', function(e) {
    if (!audio.duration) return;
    const r = this.getBoundingClientRect();
    audio.currentTime = ((e.clientX - r.left) / r.width) * audio.duration;
  });

  // URLs
  const audioUrl    = <?= json_encode($audio_url) ?>;
  const downloadUrl = <?= json_encode($download_url) ?>;
  const fileName    = <?= json_encode($name . '.mp3') ?>;

  // ── Download the MP3 ──
  const DOWNLOAD_ICON = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>`;

  async function downloadSong() {
    const btn = document.getElementById('btnDownload');
    btn.disabled = true;
    btn.innerHTML = DOWNLOAD_ICON + ' Loading...';

    try {
      // Try the proxy first; fall back to the direct Supabase URL
      let res = await fetch(downloadUrl);
      if (!res.ok) res = await fetch(audioUrl);
      if (!res.ok) throw new Error('audio_unavailable');

      const blob = await res.blob();
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = fileName;
      a.click();
      URL.revokeObjectURL(a.href);
    } catch (e) {
      // Last resort: open the direct URL
      const a = document.createElement('a');
      a.href = audioUrl;
      a.target = '_blank';
      a.click();
    } finally {
      btn.disabled = false;
      btn.innerHTML = DOWNLOAD_ICON + ' Download your song';
    }
  }
</script>
</body>
</html>
