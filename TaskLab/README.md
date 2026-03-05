# TaskLab

Internal tool to capture bug/feature requests, auto-refine them with AI into clear, developer-ready tasks, and manage their lifecycle.

## Stack

- Laravel 12
- Blade + Tailwind CSS (via Vite build)
- Laravel queue jobs for asynchronous "AI refinement" (currently a fake implementation)

## Core concepts

- **Task**: user request (bug, feature, improvement, question) with:
  - Raw description entered by the requester
  - AI-generated summary and requirements (via `RefineTaskWithAi` job + `AiTaskRefiner`)
  - Status, priority, type, reporter, optional assignee

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# configure your database in .env and then run
php artisan migrate

# run dev server
php artisan serve
```

## Production notes (Render + Supabase)

- Use `DB_CONNECTION=pgsql` and the connection parameters from Supabase (host, port, database, user, password).
- Set `SESSION_DRIVER=file` unless you also create the `sessions` table and want DB-backed sessions.
- Ensure `APP_KEY` is set from `php artisan key:generate --show`.
- Do **not** commit `public/hot`; assets are served from `public/build` built by Vite.

## Main routes

- `GET /` → redirect to `/tasks`
- `GET /tasks` → task list
- `GET /tasks/create` → form to create a new task
- `POST /tasks` → store task and dispatch AI refinement job
- `GET /tasks/{task}` → task detail with raw description + AI fields
- `PATCH /tasks/{task}/status` → update task status

## AI refinement flow (MVP)

- When a task is created, `RefineTaskWithAi` is dispatched.
- The job calls `App\Services\AiTaskRefiner::refine($rawDescription)`.
- For now, `AiTaskRefiner` returns a **fake** refinement so the UI works end-to-end without external API keys.
- Later this service can be wired to a real AI provider (OpenAI, Claude, etc.).

## Next steps / ideas

- Add roles (`requester`, `developer`, `admin`).
- Replace fake AI with a real provider.
- Add attachments (screenshots) to tasks.
- Add a Teams integration endpoint to create tasks from channel messages.
