# YZH-HR — Local Development Setup

> **Day 0 setup.** Follow this top to bottom. Should take about 1 hour the first time.
>
> **Note:** Original guide is macOS-flavored. Windows users — see `Windows install` section below.

---

## Prerequisites you need to install

Required versions:
- **PHP 8.3+** (Laravel 11 minimum)
- **Composer 2.7+**
- **Node.js 20+** + npm or pnpm
- **MySQL 8+** (or MariaDB 10.6+)
- **Redis 7+** (for queues — required by Laravel Horizon)
- **Git** (you probably have this)

---

## Option A — Native install on macOS (recommended for Walid)

### 1. Install Homebrew if you don't have it
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

### 2. Install PHP 8.3
```bash
brew install php@8.3
brew link php@8.3 --force --overwrite
php -v   # should show 8.3.x
```

### 3. Install Composer
```bash
brew install composer
composer --version   # should show 2.7+
```

### 4. Install Node.js 20+ via nvm
```bash
brew install nvm
mkdir ~/.nvm

# Add to ~/.zshrc:
echo 'export NVM_DIR="$HOME/.nvm"' >> ~/.zshrc
echo '[ -s "/opt/homebrew/opt/nvm/nvm.sh" ] && . "/opt/homebrew/opt/nvm/nvm.sh"' >> ~/.zshrc
source ~/.zshrc

# Install Node 20
nvm install 20
nvm use 20
node -v   # should show v20.x
```

### 5. Install MySQL 8
```bash
brew install mysql
brew services start mysql

mysql_secure_installation

mysql -u root -p -e "SELECT VERSION();"
```

### 6. Install Redis
```bash
brew install redis
brew services start redis

redis-cli ping   # should return PONG
```

### 7. Install MailHog (local email catching, optional but useful)
```bash
brew install mailhog
brew services start mailhog
# Web UI at http://localhost:8025
# SMTP at localhost:1025
```

### 8. Install Git (if missing)
```bash
git --version
brew install git
```

---

## Option B — Docker (alternative, more portable)

If preferred:

```bash
cp .env.example .env
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
docker-compose exec app npm install
docker-compose exec app npm run dev
```

A `docker-compose.yml` and `Dockerfile` will be created during F1 (Project scaffold).

---

## Windows install

Use **winget** (preinstalled on Windows 10/11) or **Scoop** (https://scoop.sh) for everything except Redis.

### 1. PHP 8.3
```powershell
winget install -e --id PHP.PHP.8.3
# Add C:\Program Files\PHP\v8.3 to PATH
php -v
```
Or via Scoop: `scoop install php`

### 2. Composer
```powershell
winget install -e --id Composer.Composer
composer --version
```

### 3. Node 20+
Already installed if `node -v` shows 20+. Otherwise:
```powershell
winget install -e --id OpenJS.NodeJS.LTS
```

### 4. MySQL 8
```powershell
winget install -e --id Oracle.MySQL
# Run MySQL Installer → Server only → set root password
```
Or use **Laragon** (https://laragon.org) which bundles PHP + MySQL + nginx in one installer.

### 5. Redis
Native Windows Redis isn't supported. Pick one:
- **Memurai** (https://www.memurai.com — free for dev, Redis-compatible)
- **Docker:** `docker run -d -p 6379:6379 --name redis redis`
- **WSL2:** install Ubuntu and run `sudo apt install redis-server`
- **Skip for Phase 1** — set `QUEUE_CONNECTION=database` in `.env` and Laravel will use a DB queue table

### 6. Git
Likely installed. Otherwise: `winget install -e --id Git.Git`

### Verify
```powershell
php -v; composer --version; node -v; mysql --version; redis-cli --version
```

---

## Project setup

### 1. Create project directory
```bash
mkdir ~/projects/yzh-hr   # or wherever
cd ~/projects/yzh-hr
```

### 2. Initialize Git
```bash
git init
```

### 3. Create the Laravel project
This will be done in F1 (Project scaffold) by Claude Code. For reference:

```bash
composer create-project laravel/laravel . "11.*"
```

### 4. Install Inertia + React (done by Claude Code in F1)
```bash
composer require inertiajs/inertia-laravel
php artisan inertia:middleware
npm install @inertiajs/react react react-dom @types/react @types/react-dom typescript
npm install -D @vitejs/plugin-react
```

### 5. Install Tailwind + shadcn (done by Claude Code in F1)
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
npx shadcn-ui@latest init
```

### 6. Install backend essentials (done by Claude Code in F1-F4)
```bash
composer require spatie/laravel-permission
composer require spatie/laravel-backup
composer require laravel/sanctum
composer require pestphp/pest --dev
composer require laravel/pint --dev
composer require sentry/sentry-laravel
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
```

---

## Database setup

### 1. Create database
```bash
mysql -u root -p

# In MySQL prompt:
CREATE DATABASE yzh_hr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'yzh_hr'@'localhost' IDENTIFIED BY 'a-strong-password-you-pick';
GRANT ALL PRIVILEGES ON yzh_hr.* TO 'yzh_hr'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Configure `.env`
```env
APP_NAME="YZH HR"
APP_ENV=local
APP_KEY=     # php artisan key:generate fills this
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Africa/Cairo

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yzh_hr
DB_USERNAME=yzh_hr
DB_PASSWORD=a-strong-password-you-pick

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hr@yzh.local
MAIL_FROM_NAME="${APP_NAME}"

# Production will switch to Zoho SMTP per Decision 4

QUEUE_CONNECTION=redis    # use 'database' if you skipped Redis
SESSION_DRIVER=redis      # use 'database' if you skipped Redis
CACHE_DRIVER=redis        # use 'database' if you skipped Redis

# Backup
BACKUP_DESTINATION=local

# Compliance flags
RANDOMIZE_PII=true     # local dev only — never use real employee data on dev machine
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

# Sentry (optional in dev)
SENTRY_LARAVEL_DSN=

# Face verification thresholds
FACE_VERIFY_VERIFIED_THRESHOLD=0.90
FACE_VERIFY_POSSIBLE_THRESHOLD=0.70
```

### 3. Generate app key
```bash
php artisan key:generate
```

### 4. Run migrations + seeders (after F2 + F8 are done by Claude Code)
```bash
php artisan migrate:fresh --seed
```

---

## Daily dev commands

### Start dev environment
Open 3 terminal windows:

**Terminal 1 — Laravel server:**
```bash
php artisan serve
# App at http://localhost:8000
```

**Terminal 2 — Vite dev server (frontend):**
```bash
npm run dev
# Hot reload for React changes
```

**Terminal 3 — Queue worker (for background jobs):**
```bash
php artisan queue:work
# Or for richer interface:
php artisan horizon
```

### Useful commands

```bash
# Run tests
php artisan test

# Run specific test
php artisan test --filter=EmployeeServiceTest

# Format code (run before commit)
./vendor/bin/pint            # backend
npm run format               # frontend (Prettier)

# Type check
npm run type-check

# Reset database to seeded state (dangerous — drops all data)
php artisan migrate:fresh --seed

# Run a backup manually
php artisan backup:run

# List backups
php artisan backup:list

# Restore from backup (custom command, built later)
php artisan backup:restore --date=2026-04-28

# Clear caches when something weird happens
php artisan optimize:clear
npm run build      # rebuild frontend if Vite gets confused

# Tinker (interactive PHP REPL with Laravel context)
php artisan tinker
```

---

## Test accounts (after F8 seeder runs)

All have password: `password`

- **admin@yzh.test** — Admin role, full access
- **hr@yzh.test** — HR role
- **manager@yzh.test** — Manager role with team
- **employee@yzh.test** — Employee role

Plus 25-30 additional seeded employees.

---

## Common errors & fixes

### "SQLSTATE[HY000] [2002] Connection refused"
MySQL isn't running.
```bash
brew services restart mysql   # macOS
# Windows: open Services.msc, restart "MySQL80"
```

### "Connection refused" for Redis
Redis isn't running.
```bash
brew services restart redis
```

### "Vite manifest not found"
Frontend needs to be built or dev server needs to be running.
```bash
npm run dev    # for development
# OR
npm run build  # for production-like
```

### "Class 'Pest\\TestCase' not found"
Pest tests not initialized yet.
```bash
./vendor/bin/pest --init
```

### "Permission denied" on storage
```bash
chmod -R 775 storage bootstrap/cache
```

### "Composer memory limit"
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

### Inertia "page not found"
Check that the React component file path matches the Inertia render call:
```php
Inertia::render('Employees/Index', ['employees' => $employees]);
// expects: resources/js/Pages/Employees/Index.tsx
```

---

## Verifying everything works (smoke test)

After F1-F12 are complete, run this checklist:

- [ ] `php artisan serve` starts without errors
- [ ] `npm run dev` starts without errors
- [ ] Open http://localhost:8000 → see login page
- [ ] Log in as `admin@yzh.test` / `password` → see dashboard layout
- [ ] Click each sidebar item → placeholder pages render
- [ ] Log out, log in as `employee@yzh.test` → sidebar shows employee-appropriate items
- [ ] `php artisan test` → all tests pass
- [ ] `php artisan backup:run` → creates a backup file in `storage/app/backups/`
- [ ] `php artisan queue:work` → runs without immediate errors
- [ ] `redis-cli ping` returns `PONG`
- [ ] Check email sent during signup goes to MailHog at http://localhost:8025

If all green: Foundation Phase complete. Walid can review and approve to start Feature Phase.

---

## Privacy reminder (PDPL compliance)

🔒 **Local development uses ONLY seeded fake data.**

- Never put real YZH employee data on Walid's laptop in development
- `.env` has `RANDOMIZE_PII=true` for local — even if real data accidentally imports, PII gets randomized
- Production employee data only on InMotion VPS, never on dev machines
- Real Mawared migration happens directly to staging environment in Phase 2

---

## Git ignore essentials

`.gitignore` includes (Laravel default + our additions):

```
# Laravel defaults
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode

# Our additions
/storage/app/backups/
/storage/app/uploads/
/storage/app/selfies/
.DS_Store
*.local

# Tests
.phpunit.cache/

# OS
Thumbs.db
```

---

## When you finish setup

1. Tell Claude Code: "Setup is complete, all smoke tests pass."
2. Read `TASK_MANAGER.md` to find next task
3. Claude Code begins F1 (or wherever marked next)
