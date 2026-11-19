# Contributing to the Biometric ATM System

Thanks for your interest! This is a student coursework project, so please keep
the contribution bar low and the changes focused.

## Getting started

```bash
cp .env.example .env
# Edit .env: set SHARED_SECRET, DB_PASS, and any other required values.
pip install -r python/requirements.txt
```

See [QUICK_START.txt](QUICK_START.txt) for the full run-through.

## Project layout

| Path | Purpose |
|------|---------|
| `public/` | Web-facing PHP (login, register, health) |
| `admin/` | Admin dashboard |
| `api/` | REST API endpoints |
| `core/` | Shared PHP core (config, crypto, sessions) |
| `config/` | DB connection + app config |
| `python/` | Biometric service (face + fingerprint) |
| `docker/` | Apache / PHP Dockerfiles |

## Rules

- **Never commit secrets.** `.env`, biometric templates, and raw captures are
  in `.gitignore`. Use `.env.example` as the template.
- **No weak defaults.** Admin passwords are generated randomly at setup and
  printed once — never hardcoded. Use `reset_admin.php` to change them.
- **Fail-secure.** Authentication must only succeed on explicit positive
  verification. Never weaken a check to make a test pass.
- **Keep the existing structure.** Don't reorganize the module layout without
  discussing it first.

## Reporting issues

Open an issue with:

- PHP version, OS, and whether you ran via Docker or XAMPP
- The exact error message and which page/action triggered it
- Steps to reproduce

## License

See [LICENSE](LICENSE) for details.