# StoreBridge

StoreBridge is a lightweight online storefront for Nigerian small businesses. Sellers can create a store, publish products, receive customer orders, and move the conversation to WhatsApp.

## What it does

StoreBridge currently provides:

- Seller registration and login
- One storefront per seller account
- Store settings, description, delivery note, and WhatsApp number
- Product creation with category, price, description, variations, availability, and image upload
- JPG, PNG, and WebP image uploads up to 2 MB
- Configurable upload storage path and public upload URL
- Public storefront and product pages
- Session-based shopping cart
- Customer checkout details
- Order creation and order-item snapshots
- WhatsApp handoff through a `wa.me` link
- Seller order list with order-status updates
- CSRF protection, password hashing, prepared SQL statements, and output escaping

## Current scope

StoreBridge is intentionally small and dependency-light. The current version does not include online payment processing, WhatsApp Cloud API integration, AI features, subscriptions, inventory management, or a multi-seller marketplace.

Product moderation, platform-wide administration, audit logging, and other governance features are planned separately and are not part of the current seller MVP.

## Technology

- PHP 8.2
- Apache
- MySQL-compatible database through PDO
- MariaDB 11 for local development
- Bootstrap 5
- PHP sessions
- Docker / Docker Compose for local development
- Production Dockerfile for portable deployment
- GitHub Actions for Docker build and PHP syntax checks

## Project structure

```text
.
├── auth/                    # Registration, login, logout
├── config/
│   ├── database.php         # Environment-based PDO connection
│   └── uploads.php          # Configurable upload directory and URL
├── database/
│   └── init.sql             # Database schema
├── includes/
│   ├── auth.php             # Sessions, auth, CSRF, helpers
│   ├── header.php           # Shared layout and navigation
│   └── footer.php           # Shared footer
├── public/
│   ├── store.php            # Public storefront
│   ├── product.php          # Product details
│   └── cart.php             # Cart and order submission
├── seller/
│   ├── dashboard.php        # Seller dashboard
│   ├── store.php            # Store settings
│   ├── products.php         # Seller product list
│   ├── add-product.php      # Product creation and uploads
│   └── orders.php           # Seller orders and statuses
├── uploads/
│   └── .gitkeep             # Keeps the default upload directory in Git
├── .devcontainer/           # GitHub Codespaces / local Docker development
├── .github/workflows/
│   └── docker-build.yml     # Automated Docker build and PHP syntax checks
├── Dockerfile               # Production image
└── index.php                # StoreBridge homepage
```

## Local development with GitHub Codespaces

The repository includes a Dev Container based on Docker Compose.

The local stack contains:

- PHP + Apache in the `web` service
- MariaDB 11 in the `db` service
- Port 80 forwarded for the website
- `database/init.sql` mounted for database initialization
- Development database credentials defined only in Docker Compose

Open the repository in GitHub Codespaces and let the Dev Container start the stack.

For a local Docker environment outside Codespaces:

```bash
docker compose -f .devcontainer/docker-compose.yml up --build
```

Then open the forwarded port 80.

## Configuration

### Database

The application reads database settings from environment variables:

| Variable | Purpose | Example |
|---|---|---|
| `DB_HOST` | Database hostname | `db` |
| `DB_PORT` | Database port | `3306` |
| `DB_NAME` | Database name | `catalogue` |
| `DB_USER` | Database user | `catalogue` |
| `DB_PASSWORD` | Database password | `change-me` |

The production application does not depend on the Compose service name. Set these variables to match the database service used by the deployment platform.

### Uploads

The application reads upload settings from:

| Variable | Purpose | Example |
|---|---|---|
| `UPLOAD_DIR` | Filesystem path used to store uploaded images | `/var/www/html/uploads` |
| `UPLOAD_URL` | Browser-visible URL prefix for uploaded images | `/uploads` |

Default values are suitable for a standard filesystem-backed deployment:

```text
UPLOAD_DIR=/var/www/html/uploads
UPLOAD_URL=/uploads
```

For persistent hosting, the upload directory should be mapped to persistent storage. If the filesystem is ephemeral, uploaded images may be lost when the application is redeployed or restarted.

If `UPLOAD_DIR` is moved outside the web root, the deployment must also provide a way for Apache (or another web layer) to serve that directory at the configured `UPLOAD_URL`.

## Production Docker

The repository includes a root `Dockerfile` that builds a standalone PHP + Apache image.

Build it with:

```bash
docker build -t storebridge:test .
```

The image:

- Installs `pdo_mysql`
- Enables Apache rewrite support
- Uses the project as the Apache document root
- Applies the project Apache configuration
- Sets default upload configuration
- Creates the upload directory with web-server ownership

Docker Compose is kept for local development; the application itself uses environment-based configuration and does not require Compose in production.

## Database initialization

The schema is stored in:

```text
database/init.sql
```

For local development, Docker Compose mounts this file into MariaDB's initialization directory.

On a production database service, do not assume the Compose initialization behavior exists. Initialize the production database using the platform's supported MySQL/MariaDB client or an appropriate migration/setup process.

## Typical seller flow

```text
Register
  ↓
Create store
  ↓
Add product
  ↓
Upload product image
  ↓
Publish / view public store
  ↓
Customer views product
  ↓
Add to cart
  ↓
Checkout
  ↓
Order saved
  ↓
WhatsApp handoff
  ↓
Seller manages order status
```

## Security

The current application includes:

- Password hashing with PHP `password_hash`
- `password_verify` during login
- PDO prepared statements
- Session-based authentication
- CSRF tokens on state-changing forms
- HTML output escaping with `htmlspecialchars`
- Seller queries scoped to the authenticated seller's store
- MIME validation for product image uploads
- A 2 MB upload-size limit
- No application secrets committed to the repository

Development credentials are contained in the local Docker Compose configuration and must be replaced with production secrets during deployment.

## Testing

The project has been exercised in GitHub Codespaces through the main seller/customer flow, including product creation, image upload and display, cart usage, order creation, and WhatsApp handoff.

GitHub Actions also runs the production Docker build and PHP syntax checks for changes on `portable-docker` and pull requests targeting `main`.

## Deployment notes

Before launching StoreBridge publicly, configure:

1. A production MySQL-compatible database
2. `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`
3. Persistent storage for `UPLOAD_DIR`
4. `UPLOAD_URL` matching how the deployment serves uploaded files
5. Database schema initialization from `database/init.sql`
6. HTTPS and the deployment platform's production settings

Do not use local development credentials in production.

## Development workflow

The repository uses feature branches and pull requests.

```text
feature branch
    ↓
testing
    ↓
Pull Request
    ↓
review
    ↓
main
```

The `main` branch is treated as the stable branch. Production-facing changes should be tested before they are merged.

## License

No public license has been added yet. Treat the repository as proprietary unless a license is added by the project owner.
