<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-10-red?logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/license-GPLv3-blue" alt="License">
</p>

<h1 align="center">🎞️ OpenGifs</h1>

<p align="center">
  <strong>Free & open-source GIF hosting service.</strong><br>
  Upload, share, and discover GIFs — no account required.<br>
  Built with Laravel + MySQL. Powered by ImgBB.
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#quick-start">Quick Start</a> •
  <a href="#self-hosting">Self-Hosting</a> •
  <a href="#api">API</a> •
  <a href="#environment-variables">Configuration</a>
</p>

---

## Features

- **Upload GIFs** — no registration, no API keys needed
- **Proxied URLs** — `/g/{id}` hides the real ImgBB URL, returns proper Content-Type & cache headers
- **Full-text search** — search by title and keywords
- **Open REST API** — search, trending, latest, random, and detail endpoints
- **Auto-install** — creates the database table on first visit (zero config)
- **Embed codes** — HTML, Markdown, BBCode provided for every GIF
- **2010 Web 2.0 aesthetic** — gradients, rounded corners, glossy buttons
- **Upload rules** — built-in content policy page
- **Developer-friendly API docs** — examples in JS, PHP, Python, Ruby, Go, cURL

## Quick Start

The easiest way is to deploy on any cloud hosting (Railway, Heroku, Fly.io, etc.):

1. Set the required [environment variables](#environment-variables) in your hosting dashboard
2. Deploy — the first visit will auto-run migrations
3. Get an [ImgBB API key](https://api.imgbb.com/) and set `IMGBB_API_KEY`

## Self-Hosting

```bash
git clone https://github.com/agichev/opengifs.git
cd opengifs
composer install
php artisan key:generate
```

Create a MySQL database and set your credentials in your environment. Then visit the site — migrations run automatically.

> **Document root:** point your web server to `public/` for best results.
> If your host doesn't allow changing the document root, the root `index.php` will automatically forward requests to `public/`.

## Environment Variables

| Variable | Required | Default | Description |
|---|---|---|---|
| `APP_KEY` | **yes** | — | Laravel app key (run `php artisan key:generate`) |
| `APP_ENV` | no | `production` | Environment mode |
| `APP_DEBUG` | no | `false` | Enable debug mode |
| `APP_URL` | no | `http://localhost` | Public URL |
| `DB_CONNECTION` | **yes** | `mysql` | Database driver |
| `DB_HOST` | no | `127.0.0.1` | Database host |
| `DB_PORT` | no | `3306` | Database port |
| `DB_DATABASE` | **yes** | — | Database name |
| `DB_USERNAME` | **yes** | — | Database user |
| `DB_PASSWORD` | no | — | Database password |
| `IMGBB_API_KEY` | **yes** | — | [ImgBB API key](https://api.imgbb.com/) |

## API

OpenGifs provides a free, open REST API — no key required.

### Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/gifs/search?q={query}` | Search GIFs |
| `GET` | `/api/v1/gifs/trending` | Most viewed |
| `GET` | `/api/v1/gifs/latest` | Recently uploaded |
| `GET` | `/api/v1/gifs/random` | Random GIF |
| `GET` | `/api/v1/gifs/{id}` | GIF by ID |

**Rate limit:** 120 req/min per IP.

```bash
curl "/api/v1/gifs/search?q=cat&limit=3"
```

Response:

```json
{
  "success": true,
  "query": "cat",
  "count": 1,
  "data": [
    {
      "id": 1,
      "title": "Funny cat",
      "keywords": ["cat", "funny"],
      "url": "https://opengifs.com/gif/abc123",
      "gif_url": "https://opengifs.com/g/abc123",
      "file_size": 123456,
      "views": 42,
      "created_at": "2026-01-15T12:00:00+00:00"
    }
  ]
}
```

Full API docs with integration examples (JavaScript, PHP, Python, Ruby, Go, cURL) are available at `/api` on your instance.

## Tech Stack

- **Backend:** Laravel 10, PHP 8.1+
- **Database:** MySQL 8+ / MariaDB 10+
- **File storage:** ImgBB (external API)
- **HTTP client:** Guzzle
- **License:** GPLv3

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the repo
2. Create a feature branch (`git checkout -b feature/amazing`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing`)
5. Open a Pull Request

## License

[GNU General Public License v3.0](LICENSE)

---

<p align="center">
  <sub>Made by <a href="https://github.com/agichev">Agichev</a></sub>
</p>
