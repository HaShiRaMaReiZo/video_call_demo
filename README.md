# Stream Video demo: client + officer (two Laravel apps)

Two separate Laravel 11 applications with **Blade + Vite + vanilla JavaScript** and [Stream Video](https://getstream.io/video/) via [`@stream-io/video-client`](https://www.npmjs.com/package/@stream-io/video-client). The **client** app registers end users by **phone number** (login with phone + password) and listens for incoming rings. The **officer** app enters the client’s **phone number**; it resolves **phone → Stream `user_id`** via a small server-to-server call to the client app (`CLIENT_APP_URL` + shared `OFFICER_LOOKUP_TOKEN`), then rings and joins the same call.

## Repository layout

| Path | Purpose |
|------|---------|
| [stream-video-client](stream-video-client/) | Client web app (port **8000** in examples) |
| [stream-video-officer](stream-video-officer/) | Officer web app (port **8001** in examples) |
| [stream-sdk-php](stream-sdk-php/) | Shared PHP helpers adapted from [GetStream/stream-video-php-examples](https://github.com/GetStream/stream-video-php-examples) (`StreamIo\` namespace) |
| [_stream-php-ref](_stream-php-ref/) | Optional upstream clone for reference only; safe to delete |

## Technology stack

| Technology | Role in this repo |
|------------|-------------------|
| **Laravel 11** | HTTP server, routing, auth, validation, config, queues/sessions/cache via DB, Artisan commands (`officer:create` on the officer app). |
| **PHP 8.2+** | Runtime; shared **GetStream** helpers live in [`stream-sdk-php`](stream-sdk-php/) as the `StreamIo\` namespace (Composer `psr-4` from each app). |
| **Blade** | Full-page UI from [`resources/views`](stream-video-client/resources/views/) (Breeze-style layouts, auth, profile, dashboards, active call shell). |
| **Alpine.js** | Lightweight interactivity for Breeze components (e.g. nav dropdown) via [`resources/js/app.js`](stream-video-client/resources/js/app.js). |
| **`@stream-io/video-client`** | Browser SDK for connect, ring listener (client dashboard), join/leave, and minimal local/remote `<video>` UI ([`resources/js/dashboard-client.js`](stream-video-client/resources/js/dashboard-client.js), [`resources/js/active-call.js`](stream-video-client/resources/js/active-call.js)). |
| **Vite** | Multi-entry build (global `app.js`, dashboard script on client, shared `active-call.js`), dev server + HMR. |
| **Laravel Breeze (views)** | Auth and profile Blade patterns; **no** Inertia or React runtime. |
| **Axios** | Used from the Stream entry scripts for `POST /stream/token` and CSRF (meta tag + [`resources/js/bootstrap.js`](stream-video-client/resources/js/bootstrap.js)). |
| **MySQL (or SQLite)** | Each app has its **own** `.env` and **own** database; there is **no shared users table** between client and officer. |
| **GetStream Video** | Real-time video + server APIs (JWT from your backend, `getOrCreate` + ring). Same **Video app** credentials in both `.env` files. |
| **Firebase JWT (php-jwt)** | Minting user tokens for Stream where the PHP examples use JWT signing. |

## How Laravel, Blade, and Stream JS work together

1. The user requests a URL (e.g. `/dashboard`). **Laravel** runs `routes/web.php`, applies middleware (`web`, `auth`, `verified`, `ShareBladeGlobals`, locale), and returns **`view(...)`** with the right Blade template.
2. **Blade layouts** ([`resources/views/layouts/app.blade.php`](stream-video-client/resources/views/layouts/app.blade.php)) load global CSS/JS with `@vite` and push page-specific bundles with `@stack('scripts')` where needed.
3. **Shared config for templates** (e.g. whether Stream env vars are set) comes from [`ShareBladeGlobals`](stream-video-client/app/Http/Middleware/ShareBladeGlobals.php) (`View::share('stream', ...)`).
4. **Forms** (login, register, officer ring, profile) use normal HTML `POST`/`PATCH` to Laravel. **Stream-only pages** use **Axios** from small Vite entries to call `POST /stream/token` and run **`StreamVideoClient`** (ring events on the client dashboard; join + tracks on `/call/{type}/{id}`).
5. **Vite** builds `resources/js/app.js` (Alpine + Axios bootstrap), **`dashboard-client.js`** (client only), and **`active-call.js`** (both apps).

So: **Laravel owns routing, auth, secrets, and Stream server calls; Blade owns documents and forms; the Stream SDK owns in-browser call state** with minimal custom DOM for video and controls.

## Database structure (per application)

The two apps are **not** linked by foreign keys in SQL. They only meet in the **browser** (two tabs) and on **GetStream** (same Video app, two distinct `stream_user_id` values). The **officer** app also calls the **client** app over HTTP to map **phone → `stream_user_id`**.

### Client app database (`stream_video_client` in examples)

| Table | Purpose |
|-------|---------|
| **`users`** | Clients: `name`, **`phone`** (unique, digits-only), `email` (nullable), `password`, `locale`, **`stream_user_id`** (UUID, unique), timestamps, `email_verified_at`, `remember_token`. |
| **`sessions`** | Session driver when `SESSION_DRIVER=database`. |
| **`cache`**, **`jobs`**, related | Standard Laravel 11 tables for cache/queue if configured. |

Phone is normalized on register/update; **Stream user id** is created at registration and never shown to the officer as an input—officers use **phone** + internal lookup.

### Officer app database (`stream_video_officer` in examples)

| Table | Purpose |
|-------|---------|
| **`users`** | Officers: `name`, `email`, `password`, `locale`, **`stream_user_id`**, timestamps, etc. No `phone` column on officers; officers are created with `php artisan officer:create`. |
| **`sessions`**, **`cache`**, **`jobs`** | Same roles as on the client app. |

### Logical “links” between data (not SQL FKs)

- **Client `users.stream_user_id`** ↔ **Officer `users.stream_user_id`**: both are Stream **user ids** in the **same** Stream Video application; Stream knows them as two participants in a call.
- **Officer form “client phone”** → **HTTP `POST` to client** `/internal/stream-user-id-by-phone` (header `X-Officer-Lookup-Token`) → **client `users.phone`** → response **`stream_user_id`** → officer backend calls Stream `getOrCreate` with officer + that client id.

```mermaid
flowchart LR
  subgraph browser [Browser]
    CR[Client dashboard JS]
    OR[Officer dashboard form]
  end
  subgraph clientApp [stream-video-client Laravel]
    CL[routes controllers]
    CDB[(MySQL client DB)]
  end
  subgraph officerApp [stream-video-officer Laravel]
    OL[routes controllers]
    ODB[(MySQL officer DB)]
  end
  subgraph stream [GetStream Video]
    SV[Stream API]
  end
  CR --> CL
  OR --> OL
  CL --> CDB
  OL --> ODB
  OL -->|"POST internal lookup token"| CL
  CL --> CDB
  OL --> SV
  CL --> SV
  CR -->|"JWT from POST /stream/token"| CL
  OR -->|"JWT from POST /stream/token"| OL
```

## Where important code lives

| Concern | Client app | Officer app |
|---------|------------|-------------|
| Routes / pages | [`routes/web.php`](stream-video-client/routes/web.php), [`resources/views/`](stream-video-client/resources/views/) | [`routes/web.php`](stream-video-officer/routes/web.php), [`resources/views/`](stream-video-officer/resources/views/) |
| Stream token | [`StreamTokenController`](stream-video-client/app/Http/Controllers/StreamTokenController.php) | Same pattern |
| Ring + join | — | [`StreamRingController`](stream-video-officer/app/Http/Controllers/StreamRingController.php), [`ClientStreamLookupService`](stream-video-officer/app/Services/ClientStreamLookupService.php) |
| Phone → id API | [`OfficerClientLookupController`](stream-video-client/app/Http/Controllers/Internal/OfficerClientLookupController.php) | — |
| Active call UI | [`resources/views/video/active-call.blade.php`](stream-video-client/resources/views/video/active-call.blade.php), [`resources/js/active-call.js`](stream-video-client/resources/js/active-call.js) | Same Blade + JS pattern |
| Client dashboard Stream | [`resources/js/dashboard-client.js`](stream-video-client/resources/js/dashboard-client.js) | — (officer dashboard is a plain form) |
| Stream server SDK wrapper | [`App\Services\StreamVideoService`](stream-video-client/app/Services/StreamVideoService.php) | Mirror under officer `app/Services/` |

## Prerequisites

- PHP **8.2+** with extensions: `openssl`, `mbstring`, `pdo`, **`pdo_sqlite`** (or use MySQL in `.env`)
- [Composer](https://getcomposer.org/) (this repo includes `composer.phar` at the root for Windows setups where Composer is not global)
- Node.js **18+** and npm

If `php artisan migrate` fails with **could not find driver** for SQLite, enable `pdo_sqlite` in `php.ini` or switch `DB_CONNECTION` / `DB_*` to MySQL in each app’s `.env`.

## Stream dashboard configuration

In the [Stream dashboard](https://dashboard.getstream.io/) (Video product), copy:

- **API key** → `STREAM_API_KEY` in **both** apps
- **Secret** → `STREAM_API_SECRET` in **both** apps (never expose to the browser; only Laravel reads it)
- **Call type** → usually `default`; override with `STREAM_DEFAULT_CALL_TYPE` if needed

Optional for your own records (not required for the SDK in this demo):

- **Organization id** → `STREAM_ORGANIZATION_ID` (from the URL `/organization/<id>/...`)
- **Application id** → `STREAM_APPLICATION_ID` (from the Apps list or URL when inside an app)

## Setup (each app)

From the repo root:

```powershell
cd "stream-video-client"
copy .env.example .env
C:\php\php.exe ..\composer.phar install
C:\php\php.exe artisan key:generate
# Edit .env: STREAM_* , APP_URL , and a long random OFFICER_LOOKUP_TOKEN (officer .env must use the same token)
C:\php\php.exe artisan migrate
npm install
npm run dev
```

In a second terminal:

```powershell
cd "stream-video-officer"
copy .env.example .env
C:\php\php.exe ..\composer.phar install
C:\php\php.exe artisan key:generate
# Same STREAM_* as the client. Set APP_URL (officer origin), CLIENT_APP_URL (client app base URL, e.g. http://127.0.0.1:8080), and the SAME OFFICER_LOOKUP_TOKEN as on the client.
C:\php\php.exe artisan migrate
npm install
npm run dev
```

Run Laravel on two ports (examples):

```powershell
# Terminal A — client
cd stream-video-client
C:\php\php.exe artisan serve --host=127.0.0.1 --port=8000

# Terminal B — officer
cd stream-video-officer
C:\php\php.exe artisan serve --host=127.0.0.1 --port=8001
```

On Windows, if `composer` is not on your `PATH`, each app includes `composer.bat` that forwards to the root `composer.phar`. Prepend the app folder to `PATH` before running `php artisan breeze:install` in the future.

If you see **419 Page Expired** when logging in, you were likely hitting **two Laravel apps on `127.0.0.1` with the same default session cookie** (browser sends `laravel_session` to every port). Each app’s `.env` sets a unique **`SESSION_COOKIE`** so client and officer do not overwrite each other’s sessions. Also use a **stable URL** (match `APP_URL` to the host/port you open in the browser).

## Demo flow

1. **Client**: **Register** with **phone number** (10–15 digits, spaces/`+` allowed), pick language, open **Dashboard** and leave it open.
2. **Officer**: create an account with `php artisan officer:create officer@example.com YourSecurePassword --name="Officer Name"`. **Log in**, open **Dashboard**, enter the **same phone number** the client used, submit **Ring and join call** (requires `CLIENT_APP_URL` + matching `OFFICER_LOOKUP_TOKEN` on both apps).
3. **Client**: when the officer rings, **Join call** enables — click it to enter the room.

Both sides use a server-minted JWT from `POST /stream/token`. Ringing uses Stream’s server API (`getOrCreate` with `ring: true` and two members), as described in the [ring calls API](https://getstream.io/video/docs/api/ring-calls/).

## PHP / Composer notes

- `composer.json` in each app sets `"platform": { "php": "8.3.99" }` so dependency resolution works on hosts reporting PHP **8.5** where some transitive packages still declare `<8.5`.
- The shared `stream-sdk-php` tree is autoloaded as **`StreamIo\`** from each app via a relative `psr-4` path.

## Security

- Rotate any API secret that was ever pasted into chat or committed to git.
- Use HTTPS and strict CORS in production; this demo assumes local same-origin requests to each app’s own backend.
