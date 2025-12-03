# simpleTestNginxComposerDocker

A simple Docker Compose setup with Nginx and PHP-FPM serving a "Hello World" PHP application.

## Setup

This project includes:
- **test_nginx**: Nginx web server (alpine-based)
- **test_php-fpm**: PHP-FPM 8.2 (alpine-based)
- A simple "Hello World" PHP application

## Deployment Options

This application can be deployed in multiple ways:
- **Local Development**: Using Docker Compose (see usage below)
- **Portainer**: For container management and deployment ([See Portainer Guide](PORTAINER.md))

## Usage

### Local Development

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

### Configuration

You can customize the external port by creating a `.env` file:

```bash
cp .env.example .env
```

Then edit `.env` and change the `NGINX_PORT` value:
```
NGINX_PORT=8080
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