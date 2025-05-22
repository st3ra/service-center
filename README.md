# Service Center

A web application for service centers: booking, request management, analytics.

## Requirements
- Docker Desktop
- Git

## Quick Start

1. **Clone the repository:**
   ```bash
   git clone git@github.com:your_username/service-center.git
   cd service-center
   ```

2. **Copy and configure environment variables:**
   ```bash
   cp .env.example .env
   # Edit .env (MySQL passwords, DB name, etc.)
   ```

3. **Build and start containers:**
   ```bash
   docker-compose build --no-cache
   docker-compose up -d
   ```

4. **Install dependencies via Docker Composer:**
   ```bash
   docker-compose exec php composer install --no-interaction --prefer-dist --optimize-autoloader
   ```

5. **Set permissions for upload and temp folders:**
   ```bash
   docker-compose exec php bash
   chown -R www-data:www-data /var/www/html/images/services /var/www/html/public/uploads /var/www/html/tmp
   chmod -R 755 /var/www/html/images/services /var/www/html/public/uploads /var/www/html/tmp
   exit
   ```

6. **Initialize the database:**
   - Open phpMyAdmin: [http://localhost:8081](http://localhost:8081)
   - Create a database (e.g., `service_center`)
   - Import `database.sql` via the "Import" tab or run:
     ```bash
     docker-compose exec db mysql -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < database.sql
     ```

7. **Access the application:**
   - Main page: [http://localhost:8080](http://localhost:8080)
   - phpMyAdmin: [http://localhost:8081](http://localhost:8081)

## Notes
- The entire project is mounted into the container: `- ./:/var/www/html`
- Apache DocumentRoot: `/var/www/html/public`
- The `tmp` directory is used by mPDF for temporary files. It is present in the repository (empty), and its contents are ignored by git.
- For mPDF, use tempDir: `__DIR__ . '/../tmp'`
- In public scripts, use autoload: `require_once __DIR__ . '/../vendor/autoload.php';`

## Test Users
- **Admin**: `admin@example.com` / `password`
- **Client**: `client@example.com` / `password`

## Project Structure
- `public/` — web-accessible files (pages, assets, includes)
- `vendor/` — Composer dependencies
- `tmp/` — mPDF temporary files (already present!)
- `database.sql` — database schema and test data
- `docker-compose.yml` — Docker configuration
- `.env.example` — environment variables template