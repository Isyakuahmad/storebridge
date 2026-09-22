# WhatsApp Catalogue MVP

Beginner-friendly PHP 8.2/Apache/MariaDB catalogue for Nigerian small businesses. Uses PDO prepared statements, Bootstrap 5, vanilla JavaScript-free PHP forms, sessions, and `wa.me`. It intentionally excludes Laravel, React, paid APIs, Paystack, Cloud API, AI, subscriptions, and inventory.

## Codespaces
Open in GitHub Codespaces. Compose starts PHP Apache on port 80 and MariaDB 11 on service hostname `db`; `database/init.sql` is mounted into MariaDB's automatic initialization directory. The web image installs `pdo_mysql` and enables rewrite. Open forwarded port 80. Local alternative: `docker compose -f .devcontainer/docker-compose.yml up --build`.

Register, create a store, add products, open the public store, browse categories, add to the session cart, submit checkout, and update order status from the seller dashboard. Test uploads with JPG/PNG/WebP under 2MB and an invalid file. Reset the database by removing the Compose volume.

## Files
`config/database.php` is the environment-based PDO connection. `includes/auth.php` provides sessions, escaping, CSRF, auth, store, cart, and currency helpers. Header/footer provide shared Bootstrap layout. `auth/*` handles registration, login, and logout. `seller/*` provides store, product, and order management with seller scoping. `public/*` provides catalogue, product, cart, checkout, order storage, and wa.me redirect. `database/init.sql` defines utf8mb4 InnoDB tables, foreign keys, indexes, timestamps, price/name snapshots, and one store per user. `index.php` is the landing page and health check. `.devcontainer/*` defines Codespaces, Docker Compose, PHP image, and Apache configuration. `uploads/.gitkeep` preserves the upload directory. `.gitignore` excludes uploads, secrets, and logs.

## Security
Passwords use `password_hash`; database operations use prepared statements; output uses `htmlspecialchars`; forms use session CSRF tokens; uploads validate MIME and 2MB size; seller queries scope by authenticated store; no secrets or real passwords are committed. Development credentials are in Compose only and must be replaced for deployment.
