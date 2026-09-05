# Action Plan: Minimalist Dark Mode Cloud Drive (Local Host Agent Specification)

## Overview & Objective
Build a lightweight, lightning-fast, self-hosted web-based file management system (Google Drive alternative) using **Laravel 12**, styled with a modern minimalist dark mode UI. The system runs bare-metal directly on the home server laptop using native PHP runtime commands (`php artisan serve --host=0.0.0.0 --port=8088`), mapping directly to a dedicated local directory, and providing instant public shareable links with direct download access.

---

## Technical Stack & Runtime Environment
- **Backend Framework:** Laravel 12 (PHP 8.2+) using native filesystem tools and SQLite.
- **Frontend Layer:** Blade templates + Tailwind CSS + Alpine.js, styled with an ultra-clean, high-contrast dark mode aesthetic.
- **Storage Layer:** Custom local disk mapping directly to a dedicated directory on the laptop.
- **Runtime & Network:** Native PHP server bound to port **8088** (`php artisan serve --host=0.0.0.0 --port=8088`).
- **Reserved Ports Policy:** Strictly avoid ports 8000, 8008, 8080, 8110, 8120, 8200, and 8201.

---

## Phase 1: Environment & Storage Filesystem Setup
- **Environment Configuration (`.env`):**
  - Add keys for local storage mapping and target port:
    ```env
    LOCAL_STORAGE_PATH="C:/my-drive-storage" # or /home/username/my-drive-storage
    PORT=8088
    DB_CONNECTION=sqlite
    ```
- **Laravel Filesystem Disk (`config/filesystems.php`):**
  - Register a dedicated disk (`local_drive`) pointing directly to `env('LOCAL_STORAGE_PATH')`.
  - Validate that the target folder exists or auto-create it safely.
- **Path Traversal Security:**
  - Enforce strict validation against `realpath()` of the base storage folder on all filesystem operations to prevent escaping the root directory.
- **Core Controller & API Routes:**
  - `GET /api/files?path=...` — Returns JSON listing of directory contents with metadata (name, size, human-readable size, modified timestamp, MIME type, folder/file flag).
  - `POST /api/upload` — Multipart file upload directly into the target folder.
  - `POST /api/mkdir` — Creates a new folder inside the current directory.
  - `DELETE /api/delete` — Safely removes a targeted file or folder.
  - `GET /api/download?path=...` — Streams file download directly from the local folder.

---

## Phase 2: Shareable Link & Direct Access Engine
- **Database Setup (SQLite):**
  - Use default local SQLite database (`database/database.sqlite`).
  - Migration for `shared_links`:
    - `id` (primary key)
    - `token` (string, unique indexed)
    - `file_path` (text, relative to root storage)
    - `downloads_count` (unsigned integer, default 0)
    - `timestamps`
- **Public Share Routes (`routes/web.php`):**
  - `GET /s/{token}` — Renders a minimalist dark-mode landing card with file metadata (name, size, file icon) and a prominent **Download** button.
  - `GET /s/{token}/download` — Increments `downloads_count` and triggers a clean, unauthenticated stream download.

---

## Phase 3: Minimalist Dark Mode Frontend (UI/UX)
- **Aesthetic Guidelines:**
  - **Color Tokens:** Pure dark background (`#0d1117` / `#161b22`), subtle borders (`#30363d`), crisp white typography (`#f0f6fc`), muted text (`#8b949e`), and Sky-400 accent (`#38bdf8`).
  - **Layout:** Google Drive-style sidebar (Navigation, Drive Capacity/Storage used) + Main viewport (Breadcrumb path, Drag-and-Drop dropzone, File Grid/List toggle).
- **Core Interactions:**
  - **Context Actions:** Quick action buttons or dropdown menu per file (*Download*, *Copy Share Link*, *Delete*).
  - **One-Click Share:** Clicking "Copy Link" fetches/generates the share token, copies the full URL (`http://<SERVER_IP>:8088/s/{token}`) to the system clipboard, and triggers an Alpine.js floating toast notification.
  - **Zero White-Flash Loading:** Hardcode dark background styles into the master layout body tag.

---

## Phase 4: Local Server Setup & Launch Commands
- **Initialization Commands:**
  ```bash
  # 1. Prepare environment & dependencies
  composer install
  npm install && npm run build
  touch database/database.sqlite
  php artisan migrate

  # 2. Launch server accessible across the local network on port 8088
  php artisan serve --host=0.0.0.0 --port=8088
  ```

---

## Verification & Acceptance Criteria
1. **Port Isolation:** Application starts and binds exclusively to port **8088** without colliding with active host ports.
2. **Direct Filesystem Parity:** Adding or removing files directly inside the host laptop folder reflects immediately in the dashboard upon refresh.
3. **Local Network Accessibility:** The dashboard loads on any device on the same LAN via `http://<LAPTOP_LAN_IP>:8088`.
4. **Frictionless Sharing:** "Copy Link" generates a working URL that lets any LAN user download the target file directly.