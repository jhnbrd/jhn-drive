# JHN Drive

A lightweight, self-hosted personal cloud storage and file management system built with Laravel, Alpine.js, and Tailwind CSS.

---

## Features

- **Personal Cloud Storage**: Dedicated per-user storage quota (20 GB per user).
- **Media Previews & Streaming**: In-browser preview for images and HTTP Range audio/video streaming.
- **Folder & File Sharing**: Public share links with single-click clipboard copy and auto-propagating folder sharing.
- **Access Management**: Request-based user registration with Superadmin approval workflow.
- **Minimalist Dark UI**: High-contrast, responsive dark interface.

---

## Prerequisites

- **PHP 8.2+** (with `pdo_sqlite`, `fileinfo`, `mbstring`, `openssl`, `curl`)
- **Composer**
- **Node.js 18+** & **npm**
- **SQLite 3**

---

## Quick Start

### 1. Clone & Install Dependencies

```bash
git clone <repository-url> jhn-drive
cd jhn-drive
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Configure your `.env` settings as needed (`APP_URL`, `PORT`, etc.).

### 3. Database & Storage Setup

```bash
touch database/database.sqlite
php artisan migrate --force
```

### 4. Create Superadmin Account

Create the initial administrator account via CLI:

```bash
php artisan drive:admin
```

Follow the interactive prompts to define your admin name, email, and password.

---

## Running the Server

### Local Development / Home Server

```bash
php artisan serve --host=0.0.0.0 --port=8088
```

Or using the helper scripts:
- Windows: `run-server.bat` or `run-server.ps1`

---

## License

MIT
