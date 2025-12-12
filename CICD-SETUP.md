# CI/CD Pipeline Setup Guide

This guide walks through setting up the complete CI/CD SecOps pipeline for this project.

## Overview

The pipeline consists of four environments:
1. **Local Development** - Developer workstations
2. **IAT (Integration Acceptance Testing)** - Automated testing server
3. **Staging** - Pre-production environment
4. **Production** - Live environment

## Prerequisites

### GitHub Repository Setup

1. **Enable GitHub Actions**
   - Go to repository Settings → Actions → General
   - Enable "Allow all actions and reusable workflows"

2. **Enable GitHub Container Registry (GHCR)**
   - Go to Settings → Packages
   - Enable "Improved container support"

3. **Configure GitHub Secrets**
   - Go to Settings → Secrets and variables → Actions
   - Add the following secrets:

   ```
   IAT_WEBHOOK_URL              # Your IAT server webhook endpoint
   STAGING_WEBHOOK_URL          # Webhook for staging approval
   PORTAINER_STAGING_WEBHOOK    # Portainer staging webhook URL
   PORTAINER_PROD_WEBHOOK       # Portainer production webhook URL
   DB_PASSWORD                  # Production database password
   ```

### Self-Hosted IAT Server Setup

#### Option 1: Using GitHub Actions (Cloud)

The IAT workflow (`.github/workflows/iat-testing.yml`) can run on GitHub-hosted runners. This is simpler but uses GitHub Actions minutes.

To trigger IAT testing automatically:
1. Modify `dev-build.yml` to trigger the IAT workflow instead of sending a webhook
2. Add workflow dispatch trigger

#### Option 2: Self-Hosted Server

For a self-hosted IAT server, you need:

1. **Server Requirements**
   - Ubuntu 20.04+ or similar Linux distribution
   - Docker and Docker Compose installed
   - Public IP or domain name
   - Sufficient resources (2+ CPU cores, 4GB+ RAM recommended)

2. **Install Webhook Receiver**

   Create a simple webhook receiver:
   
   ```bash
   # Install Node.js webhook receiver
   npm install -g webhook-cli
   
   # Create webhook handler script
   cat > /opt/iat/webhook-handler.sh << 'EOF'
   #!/bin/bash
   # IAT Webhook Handler
   
   set -e
   
   # Parse webhook data
   IMAGE_TAG=$(echo "$1" | jq -r '.image')
   BRANCH=$(echo "$1" | jq -r '.branch')
   COMMIT=$(echo "$1" | jq -r '.commit')
   
   echo "Received webhook for image: $IMAGE_TAG"
   
   # Change to project directory
   cd /opt/iat/simpleTestNginxComposerDocker
   
   # Pull latest code
   git fetch origin
   git checkout "$BRANCH"
   git pull origin "$BRANCH"
   
   # Set image tag
   export IMAGE_TAG="$IMAGE_TAG"
   
   # Start services
   docker compose -f docker-compose.iat.yml up -d
   
   # Wait for services to be healthy
   sleep 15
   
   # Run tests
   docker compose -f docker-compose.iat.yml exec -T php-fpm composer install --no-interaction
   docker compose -f docker-compose.iat.yml exec -T php-fpm composer test
   docker compose -f docker-compose.iat.yml exec -T php-fpm composer lint
   
   # Run Playwright tests
   cd tests
   npm ci
   npx playwright test --config=playwright.config.js
   
   # If tests pass, trigger staging webhook
   if [ $? -eq 0 ]; then
       echo "Tests passed! Triggering staging deployment..."
       curl -X POST "$STAGING_WEBHOOK_URL" \
           -H "Content-Type: application/json" \
           -d "{\"image\": \"$IMAGE_TAG\", \"status\": \"approved\"}"
   else
       echo "Tests failed!"
       exit 1
   fi
   
   # Cleanup
   cd /opt/iat/simpleTestNginxComposerDocker
   docker compose -f docker-compose.iat.yml down -v
   EOF
   
   chmod +x /opt/iat/webhook-handler.sh
   ```

3. **Setup Webhook Listener**

   ```bash
   # Install and configure webhook service
   sudo apt-get install webhook
   
   # Create webhook configuration
   cat > /etc/webhook.conf << 'EOF'
   [
     {
       "id": "iat-trigger",
       "execute-command": "/opt/iat/webhook-handler.sh",
       "command-working-directory": "/opt/iat",
       "pass-arguments-to-command": [
         {
           "source": "payload"
         }
       ]
     }
   ]
   EOF
   
   # Start webhook service
   webhook -hooks /etc/webhook.conf -port 9000 -verbose
   ```

4. **Configure Firewall**

   ```bash
   # Allow webhook port
   sudo ufw allow 9000/tcp
   ```

5. **Get Webhook URL**
   
   Your webhook URL will be:
   ```
   http://your-server-ip:9000/hooks/iat-trigger
   ```
   
   Add this to GitHub Secrets as `IAT_WEBHOOK_URL`

### Portainer Setup

#### Install Portainer on Staging and Production Servers

1. **Install Portainer**

   ```bash
   # Create volume for Portainer data
   docker volume create portainer_data
   
   # Run Portainer
   docker run -d \
     -p 8000:8000 \
     -p 9443:9443 \
     --name portainer \
     --restart=always \
     -v /var/run/docker.sock:/var/run/docker.sock \
     -v portainer_data:/data \
     portainer/portainer-ce:latest
   ```

2. **Access Portainer**
   - Open browser to `https://your-server-ip:9443`
   - Create admin account
   - Select "Docker" environment

3. **Create Stack**

   - Go to **Stacks** → **Add Stack**
   - Name: `todo-app-staging` (or `todo-app-production`)
   - Build method: **Repository**
   - Repository URL: Your GitHub repo URL
   - Repository reference: `refs/heads/staging` (or `refs/heads/main`)
   - Compose path: `docker-compose.staging.yml` (or `docker-compose.prod.yml`)
   - Add environment variables:
     ```
     DB_PASSWORD=your-secure-password
     DOCKER_REGISTRY=ghcr.io
     DOCKER_IMAGE=jamesmcmurran/simpletestnginxcomposerdocker
     ```

4. **Create Webhook**

   - In your stack, go to **Webhooks** tab
   - Click **Add webhook**
   - Copy the webhook URL
   - Add to GitHub Secrets:
     - `PORTAINER_STAGING_WEBHOOK` for staging
     - `PORTAINER_PROD_WEBHOOK` for production

## Branch Strategy

```
feature/* ────> dev ────> IAT Testing ────> staging ────> main (production)
               (auto)     (automated)       (manual)     (auto)
```

### Branch Descriptions

- **feature/*** - Individual feature branches
- **dev** - Development branch, triggers automatic build and IAT testing
- **staging** - Staging branch, deploys to staging environment
- **main** - Production branch, deploys to production

## Workflow

### 1. Local Development

```bash
# Install pre-commit hook
cp hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit

# Create feature branch
git checkout -b feature/new-feature

# Make changes
# ... edit files ...

# Test locally
docker compose -f docker-compose.local.yml up -d

# Commit (triggers pre-commit hook)
git commit -m "Add new feature"

# Push to GitHub
git push origin feature/new-feature
```

### 2. Merge to Dev Branch

```bash
# Create PR from feature branch to dev
# After approval, merge

# This triggers:
# 1. GitHub Actions builds Docker image
# 2. Image pushed to GHCR
# 3. Webhook sent to IAT server
```

### 3. IAT Testing (Automated)

When webhook is received:
1. IAT server pulls latest image
2. Runs full test suite (lint + unit + E2E)
3. If all tests pass:
   - Sends approval to staging webhook
   - Tags image for staging
4. If tests fail:
   - Notifies developers
   - Stops deployment

### 4. Staging Deployment

After IAT approval:
1. Staging webhook triggered (manual or automatic)
2. GitHub Actions tags image as `staging`
3. Portainer webhook triggered
4. Staging environment pulls and deploys new image

### 5. Promote to Production

```bash
# After testing in staging
# Create PR from staging to main
# After approval, merge

# This triggers:
# 1. Production workflow runs
# 2. Image tagged as 'latest'
# 3. Rollback image created with SHA
# 4. Portainer webhook triggered
# 5. Production deploys automatically
```

## Testing the Pipeline

### Test Local Setup

```bash
docker compose -f docker-compose.local.yml up -d
curl http://localhost:8080
docker compose -f docker-compose.local.yml down
```

### Test Pre-commit Hook

```bash
# Make a change
echo "test" >> README.md

# Commit (hook should run)
git add README.md
git commit -m "Test commit"

# Hook should run linting and tests
```

### Test Dev Build

```bash
# Push to dev branch
git checkout -b dev
git push origin dev

# Check GitHub Actions tab
# Should see workflow running
```

### Test IAT Webhook

```bash
# Manually trigger webhook
curl -X POST "http://your-iat-server:9000/hooks/iat-trigger" \
  -H "Content-Type: application/json" \
  -d '{
    "image": "ghcr.io/jamesmcmurran/simpletestnginxcomposerdocker:dev",
    "branch": "dev",
    "commit": "abc123",
    "repository": "jamesmcmurran/simpletestnginxcomposerdocker"
  }'
```

### Test Portainer Webhook

```bash
# Manually trigger Portainer deployment
curl -X POST "your-portainer-webhook-url"
```

## Monitoring and Debugging

### Check GitHub Actions

1. Go to repository → Actions tab
2. View workflow runs
3. Check logs for each step

### Check IAT Server

```bash
# View webhook service logs
journalctl -u webhook -f

# Check running containers
docker ps

# View container logs
docker compose -f docker-compose.iat.yml logs
```

### Check Portainer

1. Login to Portainer web interface
2. Go to Containers
3. View logs for each service
4. Check stack status

## Troubleshooting

### Build Fails on Dev Branch

- Check Dockerfile syntax
- Verify base images are accessible
- Check GitHub Actions logs

### IAT Tests Fail

- Check test logs in IAT server
- Verify database connection
- Ensure all services are healthy
- Check Playwright test results

### Staging Deployment Fails

- Verify Portainer webhook URL
- Check network connectivity
- Review Portainer logs
- Verify image exists in registry

### Production Deployment Fails

- Check Portainer webhook
- Verify production secrets
- Review logs in Portainer
- Check resource limits

## Security Best Practices

1. **Use secrets for sensitive data**
   - Never commit passwords or tokens
   - Use GitHub Secrets for CI/CD
   - Use environment variables in production

2. **Keep images updated**
   - Regularly update base images
   - Monitor for security vulnerabilities
   - Use Dependabot for dependency updates

3. **Limit access**
   - Use separate credentials for each environment
   - Implement least privilege access
   - Rotate credentials regularly

4. **Enable audit logging**
   - Log all deployments
   - Monitor webhook calls
   - Track image pulls

5. **Backup strategy**
   - Regular database backups
   - Keep rollback images
   - Document recovery procedures

## Maintenance

### Regular Tasks

- **Weekly**: Review workflow runs and test results
- **Monthly**: Update dependencies and base images
- **Quarterly**: Security audit and penetration testing
- **As needed**: Rotate secrets and credentials

### Updating the Pipeline

To modify the pipeline:

1. Update workflow files in `.github/workflows/`
2. Test changes in a feature branch
3. Merge to dev for testing
4. Deploy to production after validation

## Support

For issues or questions:
- Check GitHub Issues
- Review workflow logs
- Consult this documentation
- Contact DevOps team
