<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/license-GPLv3-blue" alt="License">
</p>

<h1 align="center">🎞️ OpenGifs</h1>

<p align="center">
  <strong>Free & open-source GIF hosting service.</strong><br>
  Upload, share, and discover GIFs — no account, no setup.<br>
  Just set env vars and go.
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#quick-start">Quick Start</a> •
  <a href="#env">Environment Variables</a> •
  <a href="#api">API</a>
</p>

---

## Features

- **Upload GIFs** — no registration needed
- **Proxied URLs** — `/g/{id}` hides the real ImgBB URL
- **Search** — by title and keywords
- **Open REST API** — search, trending, latest, random, detail
- **Auto-setup** — creates the database table on first visit
- **Embed codes** — HTML, Markdown, BBCode for every GIF
- **2010 Web 2.0 design** — gradients, rounded corners, no bloat
- **Upload rules** — built-in content policy

## Quick Start

Deploy on any PHP hosting (wasmer.io, Railway, Heroku, etc.):

1. Set the required [environment variables](#env) in your hosting dashboard
2. Get an [ImgBB API key](https://api.imgbb.com/) and set `IMGBB_API_KEY`
3. Deploy — that's it. The table is created on first visit.

## Self-Hosting

Requirements: PHP 8.0+ with `pdo_mysql`, `curl`, `mbstring` extensions + MySQL 8+.

```bash
git clone https://github.com/agichev/opengifs.git
cd opengifs
```

Point your web server to the project root. The `index.php` in root handles all routing.

## <a name="env"></a>Environment Variables

| Variable | Required | Default | Description |
|---|---|---|---|
| `DB_HOST` | no | `127.0.0.1` | Database host |
| `DB_PORT` | no | `3306` | Database port |
| `DB_DATABASE` | **yes** | `opengifs` | Database name |
| `DB_USERNAME` | **yes** | `root` | Database user |
| `DB_PASSWORD` | no | — | Database password |
| `IMGBB_API_KEY` | **yes** | — | [Get here](https://api.imgbb.com/) |
| `GIPHY_API_KEY` | no | — | GIPHY API key. [Get here](https://developers.giphy.com/) |
| `KLIPY_API_KEY` | no | — | Klipy API key |

## <a name="api"></a>API

Open REST API — no key required. Rate limit: 120 req/min.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/gifs/search?q={query}` | Search GIFs |
| `GET` | `/api/v1/gifs/trending` | Most viewed |
| `GET` | `/api/v1/gifs/latest` | Recently uploaded |
| `GET` | `/api/v1/gifs/random` | Random GIF |
| `GET` | `/api/v1/gifs/{id}` | GIF by ID |

```bash
curl "/api/v1/gifs/search?q=cat&limit=3"
```

Full API docs with integration examples (JavaScript, PHP, Python, cURL) at `/api`.

## Project Structure

```
index.php              ← Router (entry point)
config.php             ← DB connection, env vars, auto-table
handlers/
├── upload.php         ← Upload to ImgBB
└── api.php            ← API logic
templates/             ← HTML pages
public/css/style.css   ← 2010 Web 2.0 styles
```

## Contributing

Fork, branch, PR. Follow PSR-12. Keep everything in English.

## License

[GNU General Public License v3.0](LICENSE)

---

<p align="center"><sub>Made by <a href="https://github.com/agichev">Agichev</a></sub></p>
