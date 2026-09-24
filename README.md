# StoreBridge

StoreBridge is a lightweight online storefront for Nigerian small businesses. Sellers can create a store, publish products for review, receive customer orders, and move the conversation to WhatsApp.

## What it does

StoreBridge currently provides:

- Seller registration and login
- One storefront per seller account
- Store settings, delivery note, and WhatsApp number
- Product creation with category, price, description, variations, availability, and image upload
- JPG, PNG, and WebP image uploads up to 2 MB
- Configurable upload storage path and public upload URL
- Public storefront and product pages
- Session-based shopping cart
- Customer checkout details
- Order creation with product and price snapshots
- WhatsApp handoff through a `wa.me` link
- Seller order list with order-status updates
- Platform administration and product moderation
- Seller suspension and reactivation
- Audit logging for moderation and seller-account actions
- CSRF protection, password hashing, prepared SQL statements, and output escaping

## Product moderation

New products enter the `pending` moderation state and are not published to the public storefront until an administrator approves them.

Moderation states are:

- `pending`
- `approved`
- `flagged`
- `rejected`

Administrators can review product details and images, record a moderation note, approve or reject listings, or flag them for further review.

Seller accounts can also be suspended. Suspended sellers cannot log in, their storefront is unavailable publicly, and their products cannot be ordered through direct cart URLs.

## Platform administration

The first StoreBridge owner account must be promoted to the `admin` role after database setup.

After registering the owner account, run:

```sql
UPDATE users
SET role='admin'
WHERE email='owner@example.com';
```

Replace `owner@example.com` with the actual owner account email.

The admin area provides:

- Moderation queue
- Product status filtering
- Seller account management
- Audit log
- Platform-level product and seller visibility

Admin access is separate from normal seller access.

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
├── admin/
│   ├── index.php           # Admin dashboard
│   ├── products.php        # Product moderation
│   ├── users.php           # Seller management
│   └── audit-log.php       # Moderation/account audit trail
├── auth/                    # Registration, login, logout
├── config/
│   ├── database.php         # Environment-based PDO connection
│   └── uploads.php          # Configurable upload directory and URL
├── database/
│   ├── init.sql             # Fresh database schema
│   └── migrations/
│       └── 001_admin_moderation.sql
├── docker/
│   ├── 000-default.conf     # Production Apache configuration
│   └── entrypoint.sh        # Runtime upload-directory setup
├── includes/
│   ├── auth.php             # Sessions, auth, roles, CSRF, helpers
│   ├── header.php           # Shared layout and navigation
│   └── footer.php           # Shared footer
├── public/
│   ├── store.php            # Public storefront
│   ├── product.php          # Public product details
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
├── .dockerignore
├── .env.example             # Configuration template; contains no secrets
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
- Upload configuration set explicitly for the local environment

Open the repository in GitHub Codespaces and let the Dev Container start the stack.

For a local Docker environment outside Codespaces:

```bash
docker compose -f .devcontainer/docker-compose.yml up --build
```

Then open the forwarded port 80.

### Existing local database

If your database already existed before the admin/moderation changes, apply:

```text
database/migrations/001_admin_moderation.sql
```

The migration preserves existing products by marking them `approved`. New products are created as `pending`.

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

The application does not depend on the Docker Compose service name in production. Set these variables to match the database service used by the deployment platform.

### Uploads

The application reads:

| Variable | Purpose | Example |
|---|---|---|
| `UPLOAD_DIR` | Filesystem path used to store uploaded images | `/var/www/html/uploads` |
| `UPLOAD_URL` | Browser-visible URL prefix for uploaded images | `/uploads` |

Default values:

```text
UPLOAD_DIR=/var/www/html/uploads
UPLOAD_URL=/uploads
```

For production, `UPLOAD_DIR` should be backed by persistent storage. An ephemeral filesystem can lose uploaded images during redeployment or restart.

When the upload directory is outside the Apache document root, the deployment must also configure a web-accessible mapping that matches `UPLOAD_URL`.

### Environment template

Copy the safe template when creating a new environment:

```text
.env.example
```

Never commit a real `.env` file or production credentials.

## Production Docker

The root `Dockerfile` builds a standalone PHP + Apache image.

Build it locally with:

```bash
docker build -t storebridge:test .
```

The production image:

- Installs `pdo_mysql`
- Enables Apache rewrite support
- Applies the production Apache configuration
- Sets default upload configuration
- Initializes the upload directory at runtime
- Adjusts upload-directory ownership before Apache starts

Docker Compose remains a development tool. The application itself uses environment-based configuration and does not require Compose in production.

## Database initialization

The schema for a fresh deployment is:

```text
database/init.sql
```

For an existing database, use the migrations in:

```text
database/migrations/
```

Do not assume Docker Compose initialization behavior exists on a production database service.

## First production administrator

Create the owner account through the normal registration flow, then promote that account directly in the production database:

```sql
UPDATE users
SET role='admin'
WHERE email='owner@example.com';
```

After promotion, sign in again to obtain the admin navigation.

Keep the production database credentials private. The admin role should only be granted to trusted platform owners or operators.

## Persistent uploads

StoreBridge is designed so uploaded images can be moved from the default local directory to persistent storage without changing application code.

For a filesystem-backed production service, a suitable arrangement is:

```text
UPLOAD_DIR=/var/www/html/uploads
UPLOAD_URL=/uploads
```

The production storage system should mount persistent storage at:

```text
/var/www/html/uploads
```

The Docker entrypoint prepares this directory on every container start.

For object storage such as S3-compatible storage, the application would need a separate storage adapter; that is not part of the current release.

## Railway deployment

Railway can deploy this repository directly from GitHub and automatically use the root `Dockerfile`. Railway's MySQL service exposes connection variables including `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, and `MYSQLDATABASE`. A Railway service can also attach a persistent volume at a chosen mount path.

For the first deployment, configure the StoreBridge service with:

```text
DB_HOST=<Railway MYSQLHOST>
DB_PORT=<Railway MYSQLPORT>
DB_NAME=<Railway MYSQLDATABASE>
DB_USER=<Railway MYSQLUSER>
DB_PASSWORD=<Railway MYSQLPASSWORD>

UPLOAD_DIR=/var/www/html/uploads
UPLOAD_URL=/uploads
```

Attach the persistent volume to:

```text
/var/www/html/uploads
```

Then initialize the production database from `database/init.sql`, promote the owner account to `admin`, and run the final live test.

References:

- Railway MySQL: https://docs.railway.com/databases/mysql
- Railway Dockerfiles: https://docs.railway.com/builds/dockerfiles
- Railway Volumes: https://docs.railway.com/volumes

## Typical seller/customer flow

```text
Seller registers
      ↓
Creates store
      ↓
Adds product
      ↓
Product enters moderation
      ↓
Admin reviews
      ↓
Product approved
      ↓
Product appears in public store
      ↓
Customer opens product
      ↓
Adds to cart
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
- Session-based authentication
- Role-based admin authorization
- Account-status enforcement for suspended accounts
- PDO prepared statements
- CSRF tokens on state-changing forms
- HTML output escaping with `htmlspecialchars`
- Seller queries scoped to the authenticated seller's store
- Public queries restricted to active sellers and approved products
- MIME validation for product image uploads
- A 2 MB upload-size limit
- No production secrets committed to the repository
- Audit logging for administrative moderation and account actions

Development credentials are defined only for local Docker Compose use and must be replaced with production secrets.

## Testing

The project has been exercised in GitHub Codespaces through the core seller/customer flow, including:

- Registration and login
- Store creation
- Product creation
- Product image upload
- Product image display
- Public storefront
- Product page
- Cart
- Order creation
- WhatsApp handoff
- Seller order management

GitHub Actions runs the production Docker build and PHP syntax checks for changes on `portable-docker` and pull requests targeting `main`.

Launch preparation additionally validates the moderation schema, production storage configuration, and admin authorization path before public deployment.

## Launch checklist

Before making StoreBridge publicly available:

- [ ] Production database provisioned
- [ ] Production schema initialized
- [ ] Owner account promoted to `admin`
- [ ] Production DB environment variables configured
- [ ] Persistent upload volume attached
- [ ] `UPLOAD_DIR` and `UPLOAD_URL` verified
- [ ] HTTPS enabled
- [ ] Seller moderation tested
- [ ] Seller suspension tested
- [ ] Audit log tested
- [ ] Customer order tested
- [ ] WhatsApp handoff tested
- [ ] Production deployment logs reviewed
- [ ] Final live smoke test completed

## Development workflow

StoreBridge uses feature branches and pull requests.

```text
feature / launch branch
        ↓
local testing
        ↓
GitHub Actions
        ↓
Pull Request
        ↓
review
        ↓
main
        ↓
production deployment
```

The `main` branch is treated as the stable branch. Production-facing changes should be tested before they are merged.

## License

No public license has been added yet. Treat this repository as proprietary unless a license is added by the project owner.
