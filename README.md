# simpleTestNginxComposerDocker

A simple Docker Compose setup with Nginx and PHP-FPM serving a "Hello World" PHP application.

## Setup

This project includes:
- **test_nginx**: Nginx web server (alpine-based)
- **test_php-fpm**: PHP-FPM 8.2 (alpine-based)
- A simple "Hello World" PHP application

## Usage

### Start the services

```bash
docker compose up -d
```

### Access the application

Open your browser and navigate to:
```
http://localhost:8080
```

You should see "Hello World!" displayed.

### Stop the services

```bash
docker compose down
```

## Project Structure

```
.
├── docker-compose.yml      # Docker Compose configuration
├── nginx/
│   └── nginx.conf         # Nginx server configuration
├── src/
│   └── index.php          # PHP application
└── README.md              # This file
```

## Services

- **test_nginx**: Exposes port 8080, proxies PHP requests to test_php-fpm
- **test_php-fpm**: Runs PHP-FPM on port 9000 (internal)
- Both services share the `src/` directory containing the PHP code