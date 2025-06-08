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
   ```
   - Edit `.env` to set your MySQL passwords, database name, etc.
   - **Yandex Maps API Key**: For the contact page map to work, you need to provide an API key.
     1. Get your free API key from the [Yandex Cloud Developer's Cabinet](https://developer.tech.yandex.ru/).
     2. Add it to your `.env` file: `YANDEX_MAPS_API_KEY=your_yandex_maps_api_key_here`

3. **Build and start containers:**
   ```bash
   docker-compose build --no-cache
   docker-compose up -d
   ```

4. **Initialize the database:**
   - Open phpMyAdmin: [http://localhost:8081](http://localhost:8081)
   - Log in with user: `root`, password: `password`
   - Create a database (e.g., `service_center` with `utf8mb4_unicode_ci` collation).
   - Go to the created database and use the "Import" tab to upload the `database.sql` file.
   - Alternatively, you can run this command in your terminal:
     ```bash
     docker-compose exec db mysql -uuser -ppassword service_center < databse.sql
     ```

5. **Access the application:**
   - Main page: [http://localhost:8080](http://localhost:8080)
   - phpMyAdmin: [http://localhost:8081](http://localhost:8081)

## Notes
- Composer dependencies are installed automatically during the `docker-compose build` step.
- The `public/uploads` and `public/images/services` directories are also created automatically inside the container.
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

## Generating and Loading Test Data

1. **Generate test data:**
   ```bash
   php public/generate_test_data.php
   ```
   This will create the file `public/test_data.sql`.

2. **Clear old test data (do not delete the admin, id=1 is admin!):**
   Go to phpMyAdmin or run in MySQL:
   ```sql
   DELETE FROM request_comments;
   DELETE FROM requests;
   DELETE FROM users WHERE id > 1; -- id=1 is the admin user
   ALTER TABLE users AUTO_INCREMENT = 2;
   ALTER TABLE requests AUTO_INCREMENT = 1;
   ALTER TABLE request_comments AUTO_INCREMENT = 1;
   ```

3. **Load test data into the database via Docker:**
   In the project root, use the following command (do NOT use PowerShell, use cmd.exe on Windows or a Linux/macOS terminal):
   ```sh
   docker exec -i service-center-mysql mysql -u root -proot service_center < public/test_data.sql
   ```

   If you use a different container name or user, adjust the command accordingly.