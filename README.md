# Service Center

A web application for booking repair services and managing requests.

## Prerequisites

- Docker Desktop installed
- Git installed
- A GitHub account with SSH keys configured

## Setup and Installation

1. **Clone the repository**:
   ```bash
   git clone git@github.com:your_username/service-center.git
   cd service-center
   ```

2. **Configure environment variables**:
   Copy the `.env.example` file to `.env` and set your desired values:
   ```bash
   cp .env.example .env
   ```
   Edit `.env` to specify MySQL credentials (e.g., `MYSQL_ROOT_PASSWORD`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`).

3. **Start Docker containers**:
   ```bash
   docker-compose up -d
   ```
   This will start the PHP, MySQL, and phpMyAdmin containers.

4. **Set up file permissions**:
   ```bash
   docker-compose exec php bash
   chown -R www-data:www-data /var/www/html/images/services /var/www/html/uploads
   chmod -R 755 /var/www/html/images/services /var/www/html/uploads
   exit
   ```

5. **Initialize the database**:
   - Open phpMyAdmin at `http://localhost:8081` (use credentials from `.env`, e.g., `MYSQL_USER` and `MYSQL_PASSWORD`).
   - Create a database named `service_center` (if not already created).
   - Go to the "SQL" tab, paste the contents of `database.sql`, and execute to create tables and insert test data.

   Alternatively, import the SQL file via the phpMyAdmin "Import" tab or run:
   ```bash
   docker-compose exec db mysql -u $MYSQL_USER -p$MYSQL_PASSWORD service_center < database.sql
   ```

6. **Access the application**:
   - Main page: `http://localhost:8080`
   - phpMyAdmin: `http://localhost:8081`

## Test Credentials
- **Admin**: `admin@example.com` / `password`
- **Client**: `client@example.com` / `password`

## Project Structure
- `public/`: Web-accessible files (pages, assets, includes)
- `database.sql`: SQL script for database schema and test data
- `docker-compose.yml`: Docker configuration
- `.env.example`: Template for environment variables