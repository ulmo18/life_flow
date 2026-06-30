# LifeFlow

A mobile-first PHP web application boilerplate with session-based authentication, CSRF protection, PWA support, and Firebase Cloud Messaging (FCM) device token management.

## Architecture

- **Language:** PHP 8.2
- **Architecture:** Custom MVC (no framework)
- **Database:** SQLite (via PDO) — auto-initialized on first run at `storage/lifeflow.sqlite`
- **Auth:** Session-based with CSRF tokens
- **Port:** 5000

## Project Structure

```
app/
  Controllers/   - Request handlers (AuthController)
  Core/          - Router, Database (SQLite), CSRF, helpers
  Middleware/    - AuthMiddleware, GuestMiddleware
  Models/        - User model
  Views/         - PHP templates (auth, pages, layouts)
config/
  app.php        - App name, support email
  database.php   - DB config (defaults to SQLite)
public/
  index.php      - Entry point / router bootstrap
  assets/css/    - Stylesheets
sql/
  schema.sql     - Original MySQL schema (reference only)
storage/         - SQLite database file (gitignored)
```

## Database

- Uses SQLite by default (no server required)
- Schema is auto-created on first connection in `app/Core/Database.php`
- To switch to MySQL/MariaDB, set `DB_DRIVER=mysql` and configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` environment variables

## Running Locally

```bash
php -S 0.0.0.0:5000 -t public
```

## Environment Variables

| Variable | Default | Description |
|---|---|---|
| DB_DRIVER | sqlite | Database driver (sqlite or mysql) |
| DB_PATH | storage/lifeflow.sqlite | SQLite file path |
| DB_HOST | 127.0.0.1 | MySQL host |
| DB_PORT | 3306 | MySQL port |
| DB_NAME | lifeflow | MySQL database name |
| DB_USER | root | MySQL username |
| DB_PASS | (empty) | MySQL password |
| APP_NAME | LifeFlow | Application name |
| SUPPORT_EMAIL | support@lifeflow.app | Support email address |
| FIREBASE_* | (unset) | Firebase/FCM configuration |

## Routes

- `GET /` — Redirects to /dashboard or /login
- `GET/POST /login` — Login page
- `GET/POST /register` — Registration page
- `POST /logout` — Logout (auth required)
- `GET /dashboard` — Dashboard (auth required)
- `GET /settings` — Settings page (auth required)
- `GET /notification-guide` — Notification guide (auth required)
- `GET /privacy-policy` — Privacy policy (auth required)
- `GET /terms` — Terms of service (auth required)
- `GET /contact` — Contact page (auth required)
- `GET/POST /withdraw` — Account deactivation (auth required)
