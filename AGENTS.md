# AGENTS.md

## Project Overview
This is a mobile-first commercial web app built with PHP, MySQL, Vanilla JS, PWA, and Firebase Cloud Messaging.

Authentication includes:
- Session-based login
- Remember Me (token-based)
- Google OAuth (planned/partial)

---

## Architecture

public/: web root  
app/Controllers  
app/Models  
app/Views  
app/Middleware  
app/Services  
app/Core  
config/  
sql/  

MVC principles must be strictly followed.

---

## Architecture Rules

- Keep controllers thin (only handle request/response flow)
- Put all DB queries in models
- Do not write SQL in controllers or views
- Do not put business logic in views
- Services handle external integrations (FCM, OAuth, etc.)

---

## Database Rules

- Support both MySQL and SQLite
- Never remove sqlite fallback support
- Maintain compatibility between drivers

- Separate schema files:
  - sql/schema.mysql.sql
  - sql/schema.sqlite.sql

- Avoid MySQL-only features:
  - AUTO_INCREMENT (handle separately for sqlite)
  - ENUM
  - ON UPDATE CURRENT_TIMESTAMP

- Always use prepared statements
- Never concatenate SQL strings with user input

---

## Authentication Rules

- Use password_hash / password_verify
- Use session-based authentication
- Always call session_regenerate_id after login

### Remember Me
- Must use token-based persistent login
- Never store user_id directly in cookies
- Store hashed token in DB
- Support token expiry and revocation

### OAuth (Google)
- Use provider-based account linking
- Do not blindly merge accounts by email
- Handle first login vs existing user cases safely

---

## Security Rules

- CSRF protection required for all POST requests
- Escape all user-facing output (XSS prevention)
- Do not expose sensitive error messages
- Store secrets in environment variables (.env or Replit Secrets)
- Never hardcode credentials

---

## UI / Frontend Rules

- Mobile-first design
- Use card-based layout
- Maintain consistent spacing and typography
- Reuse common components (buttons, inputs, forms)

- Form rules:
  - Labels must be linked to inputs
  - Show clear error messages
  - Maintain consistent input spacing

---

## Coding Conventions

- DB: snake_case
- Classes: PascalCase
- Functions: camelCase
- Keep code simple and readable
- Avoid unnecessary abstractions

---

## Git / PR Rules

- Use feature branches:
  - feat/*
  - fix/*
  - refactor/*

- Never commit directly to main
- Use clear commit messages:
  - feat:
  - fix:
  - refactor:

- Always create PR before merge
- Keep PRs small and focused

---

## When Codex Works

- Always explain the plan before coding
- Before working on Plan, Routine, Calendar, Retrospect, or Goal menu behavior, read `docs/menu-feature-overview.md`
- Before working on shared header, aside menu, bottom navigator, or common layout behavior, read `docs/common-layout.md`
- Before working on dashboard calendar entry points, calendar pages, toast reuse, or calendar data sources, read `docs/calendar-feature-roles.md`
- Only implement what is requested
- Do not modify unrelated files
- List changed files after completion
- Provide manual test steps

---

## Common Mistakes to Avoid

- Do not remove sqlite support
- Do not write SQL in controllers
- Do not put logic in views
- Do not break DB connection logic
- Do not hardcode environment values
- Do not over-engineer simple features

---

## Output Expectations

- Before coding: explain the plan briefly
- After coding:
  - list changed files
  - include test steps
- Keep responses concise and practical
