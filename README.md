# abc-music-player-basic-en

English version of the abcMusic basic delivery player.
Port of [`abc-music-player-basic`](https://github.com/chnproject1/abc-music-player-basic).

## Route

```
https://<domain>/{uuid}
```

Every non-file path is rewritten to `index.php` (see `htaccess` / `Dockerfile`),
which reads the 36-char UUID from the path and looks the record up in Supabase.

## Data source

| Item | Value |
|---|---|
| Supabase project | `baltzukuszagxcgkfrpi` |
| Table | `presentes` (constant `SUPABASE_TABLE` in `index.php`) |
| Lookup | `uuid=eq.{uuid}&limit=1` |
| Fields used | `audio_url`, `nome` / `nome_presenteado` |

If the US operation ever gets its own table, change `SUPABASE_TABLE` — that is the
only place the table name appears.

## Differences from the PT-BR version

**Removed**

- The **WhatsApp** button and its `navigator.share({files})` flow. WhatsApp is not
  the delivery channel for the US operation.

**Changed**

- `lang="en"`, all UI copy, `aria-label`s, OG tags and code comments in English
- The WhatsApp button was replaced by a plain **Download your song** button that
  hits `/download.php` (same proxy, same fallbacks: proxy → direct Supabase URL →
  open in a new tab). Without it the page would have no way to reach `download.php`.
  To ship the page with no button at all, delete the `<!-- Download button -->`
  block, the `.btn-download` CSS and the `downloadSong()` function.
- Filename fallback is `my-song.mp3` (was `minha-musica.mp3`), and the downloaded
  file now uses the recipient's name instead of a hardcoded name
- `download.php` sanitizer no longer whitelists Portuguese accented characters
- Table name extracted into the `SUPABASE_TABLE` constant
- The footer is now a **CTA** — `Loved it? Make another one at abcMusic`, pointing to
  `https://abcmusic-quiz-us.netlify.app/?utm_source=link_pagina_entrega`
  (was `Made with 💚 by abcMusic` → `abcmusic.tech`). Brightened from
  `rgba(255,255,255,0.15)` to `0.45` with a hover state, so it reads as a call to
  action instead of a credit line. The abcMusic logo at the top of the page still
  points to `abcmusic.tech`.

Everything else is unchanged: animated icon, wave bars, play/pause, ±10s seek,
scrubbable progress bar.

## Files

| File | Purpose |
|---|---|
| `index.php` | The player page |
| `download.php` | Proxy that forces the MP3 download (only accepts Supabase storage URLs) |
| `htaccess` | Rewrite rules — rename to `.htaccess` if you deploy outside Docker |
| `Dockerfile` | PHP 8.2 + Apache, rewrites configured in the vhost |

## Deploy

```bash
docker build -t abc-music-player-basic-en . && docker run -p 8080:80 abc-music-player-basic-en
```

Then open `http://localhost:8080/<uuid>`.
