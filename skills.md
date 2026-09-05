# Skills & Engineering Guidelines: Laravel Minimalist Dark Mode Cloud Drive

## Core Technical Stack Expertise
- **Backend Architecture:** Laravel 12, PHP 8.2+, Eloquent ORM, Laravel Storage Facade (`Storage::disk()`), SQLite.
- **Frontend Layer:** Blade, Tailwind CSS (via Vite/CDN), Alpine.js for lightweight reactivity, Lucide / Heroicons.
- **Containerization:** Docker, Docker Compose, PHP-FPM with Nginx or FrankenPHP.

---

## Engineering Rules & Best Practices

### 1. Security & Path Traversal Prevention
- **Mandatory Validation:** Never trust user input paths. Always sanitize and validate path variables against the root disk path.
- **Implementation Pattern:**
  ```php
  use Illuminate\Support\Facades\Storage;

  protected function getSafePath(string $path): string {
      $cleanPath = ltrim($path, '/');
      $fullPath = Storage::disk('local_drive')->path($cleanPath);
      
      // Ensure it stays within root
      $rootPath = Storage::disk('local_drive')->path('');
      if (!str_starts_with(realpath($fullPath) ?: $fullPath, realpath($rootPath))) {
          abort(403, 'Access denied: Path traversal detected.');
      }
      return $cleanPath;
  }
  ```

### 2. Error Handling & Consistent Responses
- Catch file system exceptions (`FileNotFoundException`, `IOException`) and return clean JSON response payloads (`{ "success": false, "message": "..." }`) or appropriate HTTP error codes.

---

## Design System & UI/UX Guidelines

### 1. Color Palette (Strict Dark Mode)
- **Background Root:** `#0d1117` (Deep GitHub-style dark slate)
- **Surface / Card Background:** `#161b22` (Slightly lighter dark card layer)
- **Elevated / Hover State:** `#21262d` (Hover rows, dropdown menus)
- **Borders & Dividers:** `#30363d` (Subtle muted borders)
- **Primary Text:** `#f0f6fc` (Crisp off-white)
- **Secondary / Muted Text:** `#8b949e` (Subtle gray for metadata/sizes)
- **Accent Color:** `#38bdf8` (Tailwind Sky-400 / Electric blue for primary actions, active states, and focus rings)
- **Danger / Delete Accent:** `#f87171` (Tailwind Red-400)

### 2. Typography & Layout Hierarchy
- **Font Family:** Inter, system-ui, -apple-system, sans-serif.
- **Font Weights:** Normal (`400`), Medium (`500`), Semibold (`600`).
- **Layout Structure:** Google Drive-style split layout with sidebar navigation and main content area.
- **Border Radius:** `rounded-lg` (8px) for containers/modals, `rounded-md` (6px) for interactive elements.

### 3. Interactive States & Micro-Interactions
- **Transitions:** Smooth transitions on hover and active states (`transition-colors duration-150 ease-in-out`).
- **Feedback Toasts:** Floating notification banner at the bottom right for actions like "Link copied to clipboard!".
- **Empty States:** Clean centered icons with muted text placeholders when folders are empty.