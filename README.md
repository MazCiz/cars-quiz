# Cars Quiz

A car quiz app on a modern full-stack setup:

- **`web/`** — Next.js (App Router, TypeScript, Tailwind) frontend
- **`api/`** — Laravel REST API (PHP) — API routes in `routes/api.php`
- **`docker-compose.yml`** — PostgreSQL for local dev

The frontend talks to the Laravel API over HTTP (`NEXT_PUBLIC_API_URL`), and the
API persists to PostgreSQL. The scaffold's landing page calls `GET /api/ping`
and shows all three tiers connected.

## Prerequisites

- Node.js + npm
- PHP 8.2+ and Composer (`brew install php composer`)
- A Docker runtime (Docker Desktop, or Colima: `colima start`)

## Getting started

```bash
# 1. PostgreSQL (host port 5433)
docker compose up -d

# 2. API — Laravel on http://localhost:8000
cd api
cp .env.example .env        # DB settings already point at the compose Postgres
composer install            # if dependencies aren't installed yet
php artisan key:generate    # if APP_KEY is empty
php artisan migrate
php artisan serve --port=8000

# 3. Web — Next.js on http://localhost:3000
cd web
npm install
npm run dev
```

Open http://localhost:3000 — you should see the API and PostgreSQL reported as
**connected**.

## Layout

| Path | What |
|---|---|
| `web/src/app/page.tsx` | Landing page (stack status) — start building here |
| `api/routes/api.php` | REST API routes (`/api/ping`, `/api/user`) |
| `api/app/`, `api/database/migrations/` | Laravel app + schema |
| `docker-compose.yml` | Postgres (`cars_quiz` db, user/pass `carsquiz`, port **5433**) |

## Notes

- **Ports:** web `3000`, API `8000`, Postgres `5433` (5433 to avoid clashing
  with other local Postgres instances).
- **API auth:** Sanctum is installed (`php artisan install:api`); `/api/user`
  is token-protected as a starting point.
- **CORS:** Laravel allows cross-origin calls to `/api/*` by default (fine for
  local dev); tighten `config/cors.php` before production.
