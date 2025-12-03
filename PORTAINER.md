# Deploying on Portainer

This guide explains how to deploy the simpleTestNginxComposerDocker application on Portainer.

## Prerequisites

- A running Portainer instance (Community or Business Edition)
- Access to a Docker environment connected to Portainer
- Git repository access or the ability to upload files

## Deployment Methods

### Method 1: Deploy from Git Repository (Recommended)

1. **Log in to Portainer**
   - Navigate to your Portainer instance in your web browser
   - Log in with your credentials

2. **Create a New Stack**
   - Go to **Stacks** in the left sidebar
   - Click **+ Add stack**

3. **Configure the Stack**
   - **Name**: Enter a name for your stack (e.g., `nginx-php-app`)
   - **Build method**: Select **Repository**
   - **Repository URL**: Enter your Git repository URL
     ```
     https://github.com/<your-username>/<your-repository>
     ```
   - **Repository reference**: Leave as `refs/heads/main` or specify your branch
   - **Compose path**: `docker-compose.yml`

4. **Set Environment Variables** (Optional)
   - Scroll to the **Environment variables** section
   - Click **+ add an environment variable**
   - Add the following variable if you want to change the default port:
     - **name**: `NGINX_PORT`
     - **value**: `8080` (or any other port you prefer)

5. **Deploy the Stack**
   - Click **Deploy the stack**
   - Portainer will clone the repository and deploy your application

6. **Access the Application**
   - Once deployed, the application will be accessible at:
     ```
     http://<your-docker-host-ip>:8080
     ```
   - Replace `8080` with your configured `NGINX_PORT` if you changed it

### Method 2: Deploy with Web Editor

1. **Log in to Portainer**
   - Navigate to your Portainer instance
   - Log in with your credentials

2. **Create a New Stack**
   - Go to **Stacks** in the left sidebar
   - Click **+ Add stack**

3. **Configure the Stack**
   - **Name**: Enter a name for your stack (e.g., `nginx-php-app`)
   - **Build method**: Select **Web editor**

4. **Paste the Docker Compose Content**
   - Copy the contents of `docker-compose.yml` from this repository
   - Paste it into the web editor

5. **Upload Additional Files**
   - Since the compose file references local files (`./nginx/nginx.conf` and `./src`), you'll need to handle these files:
   
   **Option A**: Use Custom Template
   - Create a custom template in Portainer that includes all files
   
   **Option B**: Modify the Compose File
   - Build custom images that include the nginx config and PHP files
   - Push these images to a registry
   - Update the compose file to use these images

6. **Set Environment Variables**
   - In the **Environment variables** section, add:
     - **name**: `NGINX_PORT`
     - **value**: `8080` (or your preferred port)

7. **Deploy the Stack**
   - Click **Deploy the stack**

### Method 3: Deploy with Upload

1. **Prepare Your Files**
   - Clone or download this repository
   - Optionally, create a `.env` file from `.env.example`

2. **Create a ZIP Archive**
   - Create a ZIP file containing:
     - `docker-compose.yml`
     - `nginx/nginx.conf`
     - `src/index.php`
     - `.env` (optional)

3. **Deploy in Portainer**
   - Go to **Stacks** → **+ Add stack**
   - **Name**: Enter your stack name
   - **Build method**: Select **Upload**
   - Upload your ZIP file
   - Click **Deploy the stack**

## Configuration Options

### Environment Variables

The following environment variables can be configured:

| Variable | Default | Description |
|----------|---------|-------------|
| `NGINX_PORT` | `8080` | External port on which Nginx will be accessible |

### Customization

To customize the application:

1. **Modify PHP Code**
   - Edit files in the `src/` directory
   - The changes will be reflected immediately (no rebuild needed)

2. **Modify Nginx Configuration**
   - Edit `nginx/nginx.conf`
   - Restart the stack in Portainer for changes to take effect

3. **Change Port**
   - Update the `NGINX_PORT` environment variable in Portainer
   - Restart the stack

## Managing Your Deployment

### Viewing Logs

1. Go to **Stacks** in Portainer
2. Click on your stack name
3. You'll see the list of containers
4. Click on a container name to view its logs

### Updating the Stack

1. Go to **Stacks** in Portainer
2. Click on your stack name
3. Click **Editor** to modify the compose file
4. Or click **Pull and redeploy** if using Git repository
5. Click **Update the stack**

### Stopping/Starting the Stack

1. Go to **Stacks** in Portainer
2. Click on your stack name
3. Use the **Stop** or **Start** button at the top

### Removing the Stack

1. Go to **Stacks** in Portainer
2. Click on your stack name
3. Click **Delete this stack**
4. Confirm the deletion

## Troubleshooting

### Container Fails to Start

1. Check the container logs in Portainer
2. Verify that the port `8080` (or your custom port) is not already in use
3. Ensure all required files are present in the stack

### Cannot Access the Application

1. Verify the stack is running in Portainer
2. Check that the `NGINX_PORT` is correctly mapped
3. Ensure your firewall allows traffic on the configured port
4. Try accessing: `http://<docker-host-ip>:<NGINX_PORT>`

### PHP Files Not Processing

1. Check logs for the `test_php-fpm` container
2. Verify the volume mounts are correct
3. Ensure the `test_nginx` container can reach `test_php-fpm`

## Notes for Production

- Consider using named volumes for persistent data
- Set up proper logging and monitoring
- Use secrets management for sensitive configuration
- Implement proper backup strategies
- Consider using Docker Swarm or Kubernetes for high availability
- Add health checks to your services
- Use reverse proxy with SSL/TLS for secure connections

## Additional Resources

- [Portainer Documentation](https://docs.portainer.io/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [PHP-FPM Documentation](https://www.php.net/manual/en/install.fpm.php)
