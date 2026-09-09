# <p align="center"><img src="https://raw.githubusercontent.com/lucide-icons/lucide/main/icons/cloud.svg" width="48" height="48" alt="JHN Drive Icon" /> <br/> JHN Drive</p>

<p align="center">
  <strong>A lightning-fast, minimalist, self-hosted personal cloud storage and file management system.</strong>
</p>

<p align="center">
  <a href="#about--inspiration"><img src="https://img.shields.io/badge/inspired%20by-Cloud%20Drives-38bdf8.svg?style=flat-square" alt="Inspired by Cloud Drives" /></a>
  <a href="#license"><img src="https://img.shields.io/badge/license-MIT-emerald.svg?style=flat-square" alt="MIT License" /></a>
  <img src="https://img.shields.io/badge/laravel-12.x-red.svg?style=flat-square&logo=laravel" alt="Laravel 12" />
  <img src="https://img.shields.io/badge/php-8.2+-blue.svg?style=flat-square&logo=php" alt="PHP 8.2+" />
  <img src="https://img.shields.io/badge/alpine.js-3.x-8bc0d0.svg?style=flat-square&logo=alpinedotjs" alt="Alpine.js" />
  <img src="https://img.shields.io/badge/tailwind-4.x-38bdf8.svg?style=flat-square&logo=tailwindcss" alt="Tailwind CSS" />
  <img src="https://img.shields.io/badge/sqlite-embedded-003B57.svg?style=flat-square&logo=sqlite" alt="SQLite" />
</p>

---

## 📖 About & Inspiration

**JHN Drive** is an open-source, private cloud storage solution engineered to deliver the seamless, intuitive user experience of modern mainstream cloud drives (such as Google Drive, Dropbox, and OneDrive) while providing **complete ownership and privacy** of your files.

Rather than relying on third-party servers with subscription limits, data harvesting, and proprietary locks, JHN Drive runs bare-metal on your own local server, home lab, homelab NAS, or remote VPS. It brings immediate responsiveness, clean aesthetics, and zero bloat directly to your private infrastructure.

> [!NOTE]
> **Inspiration & Disclaimer:** This project is inspired by popular commercial cloud drives (like Google Drive). It is an independent, community-driven, self-hosted file management application and is **not affiliated with, endorsed by, or associated with Google LLC, Dropbox Inc., Microsoft Corp., or any other cloud service provider**. All product and service names are trademarks or registered trademarks of their respective holders.

---

## 🎯 Use Cases & Context

In what scenarios and contexts can **JHN Drive** be used by you or others?

| Context | How JHN Drive Solves It |
| :--- | :--- |
| 🏠 **Home Lab & Personal Server** | Turn an old laptop, mini PC, or Raspberry Pi into a high-performance central drive for all family members or personal devices over LAN / VPN. |
| 🔒 **Self-Hosted Privacy & Zero Subscription** | Retain 100% control over sensitive documents, backups, photos, and media without recurring monthly cloud fees or third-party data tracking. |
| 🎬 **Media Hub & Streaming** | Instant in-browser media preview for images, plus seamless HTTP Range audio and video streaming (with fast range chunks and seek support). |
| 🚀 **Frictionless Public & Client Sharing** | Generate instant, unauthenticated public share links with one-click clipboard copy, folder downloads, and ZIP packaging for quick asset handoffs. |
| 🛡️ **Gated Secret Vault** | Built-in isolated, PIN-protected secret vault for confidential media and files, completely unlinked from the main navigation. |
| 👥 **Small Team / Household Workspaces** | Multi-user quotas (configurable per-user limits, e.g. 20 GB) with a superadmin approval workflow to protect your server from unauthorized registrations. |

---

## ✨ Key Features

- ⚡ **Lightning-Fast Minimalist UI**: Sleek, high-contrast dark theme (`#0d1117` / `#161b22`) inspired by modern developer tooling and GitHub-style palettes with zero white-flash loading.
- 🗂️ **Direct Local Filesystem Parity**: Maps directly to your local machine or mount point (`LOCAL_STORAGE_PATH`), keeping folder structures clean and accessible directly from the host OS.
- 👥 **Multi-User Quotas & Isolation**: Strict folder boundary isolation per user with configurable storage caps (e.g. 20 GB).
- 🛡️ **Access Control & Admin Panel**: Secure request-access registration gate where a Superadmin approves or rejects new accounts before they can log in.
- 🔗 **Public Shareable Links**: Instant share tokens for individual files or entire folders with automatic download counts and single-archive ZIP downloads.
- 🎥 **Rich Media Preview & Stream**: In-browser photo viewing and native HTTP 206 Partial Content video/audio streaming with thumbnail generation.
- 🔐 **Isolated Secret Vault**: Dedicated PIN-protected space (`/secret`) with independent media viewer, download limits, and fast thumbnail previews.
- 🛡️ **Hardened Security**: Robust path traversal guards (`realpath` containment checks) preventing directory escaping.

---

## 🛠️ Architecture & Tech Stack

- **Backend:** [Laravel 12](https://laravel.com/) (PHP 8.2+) with Eloquent ORM & Laravel Storage Facade
- **Database:** [SQLite](https://www.sqlite.org/) (zero-config, high-durability embedded relational store)
- **Frontend Reactive Core:** [Alpine.js](https://alpinejs.dev/)
- **Styling & Theme:** [Tailwind CSS](https://tailwindcss.com/) (standalone utility classes + CSS custom properties)
- **Icons:** [Lucide Icons](https://lucide.dev/) / Heroicons (SVG inline)
- **Bundler:** [Vite](https://vitejs.dev/)

---

## 📋 Prerequisites

Before installing, ensure your environment meets the following specifications:

- **PHP 8.2 or higher** with required extensions:
  - `pdo_sqlite`
  - `fileinfo`
  - `mbstring`
  - `openssl`
  - `curl`
  - `gd` (optional, for thumbnail operations)
- **Composer** (v2.x+)
- **Node.js** (v18+) & **npm**
- **SQLite 3**

---

## 🚀 Quick Start & Installation

### 1. Clone the Repository
```bash
git clone https://github.com/jhnbrd/jhn-drive.git
cd jhn-drive
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 3. Setup Environment Variables
```bash
cp .env.example .env
php artisan key:generate
```

Review your `.env` configuration, especially:
```env
APP_NAME="JHN Drive"
APP_URL=http://localhost:8088
PORT=8088
LOCAL_STORAGE_PATH="storage/app/drive_storage"
FILESYSTEM_DISK=local_drive
```

### 4. Initialize Database
```bash
# On Linux / macOS / Git Bash:
touch database/database.sqlite

# On Windows PowerShell:
New-Item -ItemType File -Path database\database.sqlite -Force

# Run database migrations:
php artisan migrate --force
```

### 5. Create Initial Superadmin Account
Run the interactive CLI command to scaffold your administrator credentials:
```bash
php artisan drive:admin
```
Follow the interactive prompts to assign the admin name, email, and password.

---

## 💻 Running the Server

### Local Development / Home Server LAN Access
Bind the server across your local network on port **8088**:

```bash
php artisan serve --host=0.0.0.0 --port=8088
```

Access the drive at:
- **Local Machine:** `http://localhost:8088`
- **LAN Devices:** `http://<SERVER-LAN-IP>:8088`

### Convenient Windows Launch Scripts
Double-click or run:
- Windows Batch: `run-server.bat`
- Windows PowerShell: `run-server.ps1`

---

## 📁 Directory Structure Overview

```text
jhn-drive/
├── app/
│   ├── Http/Controllers/    # Drive, Share, Secret Vault & Admin controllers
│   ├── Http/Middleware/     # Superadmin & Secret Vault authentication guards
│   └── Models/              # User, SharedLink, Secret models
├── config/                  # App & Filesystems storage configurations
├── database/                # SQLite migrations & schema definitions
├── public/                  # Public web root & compiled Vite assets
├── resources/
│   ├── css/                 # Tailwind design tokens
│   └── views/               # Blade layouts (drive, share, secret, admin)
├── routes/web.php           # Protected drive, public share & secret routes
├── storage/                 # Local disk mappings & user quotas
└── tests/                   # Feature and unit test suites
```

---

## 🧪 Testing

Execute the complete PHPUnit test suite to verify file sharing, vault streaming, and route security:

```bash
php artisan test
```

---

## 📄 License

This project is licensed under the [MIT License](LICENSE) - see the [LICENSE](LICENSE) file for details.

---

<p align="center">
  Crafted with care by <a href="https://github.com/jhnbrd">jhnbrd</a> • Inspired by modern cloud drives
</p>
