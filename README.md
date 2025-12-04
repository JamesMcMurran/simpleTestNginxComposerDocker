# simpleTestNginxComposerDocker

A Todo Tracker application demonstrating a complete CI/CD SecOps pipeline with Docker, Nginx, PHP-FPM, PostgreSQL, and automated testing.

## 🎯 Project Overview

This project showcases a production-ready CI/CD pipeline with:
- **Local Development** with pre-commit hooks for code quality
- **Integration Acceptance Testing (IAT)** with automated Playwright tests
- **Staging Environment** for pre-production validation
- **Production Deployment** with automated rollback capabilities

## 🏗️ Architecture

### Application Stack
- **Nginx**: Web server (Alpine-based)
- **PHP-FPM 8.2**: Application server (Alpine-based)
- **PostgreSQL 16**: Database (Alpine-based)
- **Todo Application**: Simple CRUD application demonstrating CI/CD workflow

### CI/CD Pipeline Flow

```
Local Dev (Pre-commit Hooks)
    ↓
Dev/IAT Branch (Build & Push Image)
    ↓
IAT Server (Automated Testing)
    ↓
Staging (Approved Image)
    ↓
Main Branch (Production Deploy)
    ↓
Production (Portainer Webhook)
```

## 📋 Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Git
- Composer (for local development)
- Node.js 18+ (for Playwright tests)

## 🚀 Quick Start

### Local Development

1. **Clone the repository**
   ```bash
   git clone https://github.com/JamesMcMurran/simpleTestNginxComposerDocker.git
   cd simpleTestNginxComposerDocker
   ```

2. **Install pre-commit hook** (optional but recommended)
   ```bash
   cp hooks/pre-commit .git/hooks/pre-commit
   chmod +x .git/hooks/pre-commit
   ```

3. **Start the application**
   ```bash
   docker compose -f docker-compose.local.yml up -d
   ```

4. **Access the application**
   ```
   http://localhost:8080
   ```

5. **Stop the services**
   ```bash
   docker compose -f docker-compose.local.yml down
   ```

### Running Tests Locally

#### Unit Tests (PHPUnit)
```bash
# Install dependencies
composer install

# Run unit tests
composer test

# Run linting
composer lint

# Auto-fix linting issues
composer lint-fix
```

#### End-to-End Tests (Playwright)
```bash
cd tests

# Install dependencies
npm install

# Run tests (with local Docker stack)
npm test

# Run tests in headed mode
npm run test:headed

# Debug tests
npm run test:debug
```

## 🔧 Environment Configurations

### 1. Local Development (`docker-compose.local.yml`)
- **Purpose**: Development and debugging
- **Features**: 
  - Hot-reload with volume mounts
  - PostgreSQL exposed on port 5432
  - Local data persistence
- **Usage**: `docker compose -f docker-compose.local.yml up -d`

### 2. IAT Testing (`docker-compose.iat.yml`)
- **Purpose**: Automated integration and acceptance testing
- **Features**:
  - Uses pre-built Docker images
  - Includes Playwright for E2E testing
  - Isolated test environment
- **Usage**: `docker compose -f docker-compose.iat.yml up -d`
- **Run Tests**: `docker compose -f docker-compose.iat.yml --profile testing up`

### 3. Staging (`docker-compose.staging.yml`)
- **Purpose**: Pre-production validation
- **Features**:
  - Production-like configuration
  - Uses approved images from IAT
  - Environment-specific secrets
- **Usage**: Deployed via Portainer webhook after IAT approval

### 4. Production (`docker-compose.prod.yml`)
- **Purpose**: Live production environment
- **Features**:
  - Resource limits configured
  - SSL/TLS support
  - Production-grade security
  - Automated backups (configure separately)
- **Usage**: Deployed via Portainer webhook on main branch merge

## 🔄 CI/CD Pipeline Details

### Local Development Workflow

1. **Pre-commit Hook** runs automatically before each commit:
   - PHP CodeSniffer (PSR-12 standard)
   - PHPUnit unit tests
   - Prevents commits with failing tests or lint errors

2. **Development Process**:
   ```bash
   # Create feature branch
   git checkout -b feature/my-feature
   
   # Make changes and test locally
   docker compose -f docker-compose.local.yml up -d
   
   # Commit triggers pre-commit hook
   git commit -m "Add new feature"
   ```

### IAT (Dev) Branch Workflow

**Trigger**: Push to `dev`, `develop`, or `iat` branch

**GitHub Actions** (`.github/workflows/dev-build.yml`):
1. Builds Docker image with all dependencies pre-installed
2. Pushes image to GitHub Container Registry (GHCR)
3. Sends webhook to self-hosted IAT server

**IAT Server** receives webhook and:
1. Pulls the pre-built image
2. Runs full test suite:
   - PHP linting
   - Unit tests
   - Playwright E2E tests
3. Reports results back to GitHub
4. If all tests pass, triggers staging deployment

### Staging Deployment

**Trigger**: Manual or automated after IAT approval

**GitHub Actions** (`.github/workflows/staging-deploy.yml`):
1. Tags approved IAT image as `staging`
2. Pushes to registry
3. Sends webhook to Portainer (staging environment)
4. Portainer pulls and deploys new image

### Production Deployment

**Trigger**: Push/merge to `main` or `master` branch

**GitHub Actions** (`.github/workflows/prod-deploy.yml`):
1. Tags staging image as `latest` and `prod-{SHA}`
2. Pushes to registry
3. Creates release tag
4. Sends webhook to Portainer (production)
5. Production automatically pulls and deploys

## 🔐 Security Features

- **Pre-commit hooks** enforce code quality
- **Automated linting** catches security issues early
- **Container scanning** (add with GitHub Advanced Security)
- **Non-root user** in Docker containers
- **Environment-based secrets** management
- **Resource limits** in production
- **Health checks** for all services

## 📊 Required GitHub Secrets

Configure these in your GitHub repository settings:

```
IAT_WEBHOOK_URL              # Webhook URL for IAT server
STAGING_WEBHOOK_URL          # Webhook URL for staging approval
PORTAINER_STAGING_WEBHOOK    # Portainer webhook for staging
PORTAINER_PROD_WEBHOOK       # Portainer webhook for production
DB_PASSWORD                  # Production database password
```

## 🔌 Portainer Webhook Configuration

### Setup Webhooks in Portainer

1. Navigate to **Stacks** in Portainer
2. Select your stack (staging or production)
3. Click **Webhooks** tab
4. Create new webhook
5. Copy webhook URL to GitHub Secrets

### Webhook Format
```
https://portainer.example.com/api/webhooks/<webhook-id>
```

## 🧪 Testing Strategy

### Unit Tests (PHP)
- Location: `tests/Unit/`
- Framework: PHPUnit 10
- Coverage: Core business logic and utilities
- Run: `composer test`

### E2E Tests (Playwright)
- Location: `tests/e2e/`
- Framework: Playwright
- Coverage: User workflows and API endpoints
- Browsers: Chromium
- Run: `npm test` (in tests directory)

### Testing Checklist
- ✅ Homepage loads successfully
- ✅ Todo list displays from database
- ✅ New todos can be created
- ✅ Todos can be marked complete/incomplete
- ✅ Todos can be deleted
- ✅ Form validation works
- ✅ API endpoints return correct JSON

## 📁 Project Structure

```
.
├── .github/
│   └── workflows/           # GitHub Actions workflows
│       ├── dev-build.yml    # Build on dev/iat branch
│       ├── iat-testing.yml  # IAT automated testing
│       ├── staging-deploy.yml
│       └── prod-deploy.yml
├── database/
│   └── init.sql            # Database initialization
├── hooks/
│   └── pre-commit          # Pre-commit hook script
├── nginx/
│   └── nginx.conf          # Nginx configuration
├── src/
│   └── index.php           # Todo application
├── tests/
│   ├── Unit/               # PHPUnit tests
│   ├── e2e/                # Playwright tests
│   ├── package.json        # Node.js dependencies
│   └── playwright.config.js
├── Dockerfile              # Custom PHP-FPM image
├── docker-compose.local.yml
├── docker-compose.iat.yml
├── docker-compose.staging.yml
├── docker-compose.prod.yml
├── composer.json           # PHP dependencies
├── phpunit.xml            # PHPUnit configuration
└── README.md
```

## 🐛 Troubleshooting

### Database Connection Issues
```bash
# Check if PostgreSQL is healthy
docker compose -f docker-compose.local.yml ps

# View PostgreSQL logs
docker compose -f docker-compose.local.yml logs postgres

# Restart services
docker compose -f docker-compose.local.yml restart
```

### Pre-commit Hook Not Running
```bash
# Verify hook is executable
ls -la .git/hooks/pre-commit

# Make executable if needed
chmod +x .git/hooks/pre-commit

# Test hook manually
.git/hooks/pre-commit
```

### Playwright Tests Failing
```bash
# Ensure services are running
docker compose -f docker-compose.local.yml up -d

# Check service health
curl http://localhost:8080

# View browser traces
npx playwright show-report
```

## 🔄 Rollback Procedures

### Staging Rollback
```bash
# Use previous staging image
docker pull ghcr.io/jamesmcmurran/simpletestnginxcomposerdocker:staging-previous
docker tag ghcr.io/jamesmcmurran/simpletestnginxcomposerdocker:staging-previous staging
# Trigger Portainer webhook
```

### Production Rollback
```bash
# Find rollback image (tagged with commit SHA)
docker pull ghcr.io/jamesmcmurran/simpletestnginxcomposerdocker:prod-<previous-sha>

# Tag as latest
docker tag ghcr.io/jamesmcmurran/simpletestnginxcomposerdocker:prod-<previous-sha> latest
docker push ghcr.io/jamesmcmurran/simpletestnginxcomposerdocker:latest

# Trigger Portainer webhook to redeploy
```

## 📝 Development Guidelines

### Code Style
- Follow PSR-12 coding standards
- Use meaningful variable names
- Add PHPDoc comments for functions
- Keep functions small and focused

### Git Workflow
1. Create feature branch from `dev`
2. Make changes and test locally
3. Commit (pre-commit hook runs automatically)
4. Push to feature branch
5. Create PR to `dev` branch
6. After merge, automatic IAT testing triggers
7. Once approved, merge to `staging` branch
8. Finally, merge `staging` to `main` for production

### Adding New Features
1. Update database schema in `database/init.sql`
2. Modify `src/index.php` for new functionality
3. Add unit tests in `tests/Unit/`
4. Add E2E tests in `tests/e2e/`
5. Update documentation

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes (pre-commit hook will run)
4. Push to the branch
5. Create a Pull Request

## 📄 License

This project is open source and available for demonstration purposes.

## 🙏 Acknowledgments

- Docker for containerization
- Nginx for web serving
- PHP community for excellent tools
- Playwright for reliable E2E testing
- PostgreSQL for robust database