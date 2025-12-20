# Railway Deployment Guide

This guide walks you through deploying the Leaseweb Server Explorer to Railway.

---

## Prerequisites

1. A [Railway](https://railway.app) account (free tier available)
2. A [GitHub](https://github.com) account
3. Git installed on your local machine
4. The project code ready for deployment

---

## Step 1: Push to GitHub

### 1.1 Create a New GitHub Repository

1. Go to [GitHub](https://github.com) and sign in
2. Click the **+** icon in the top right → **New repository**
3. Configure repository:
   - **Repository name**: `leaseweb-server-explorer`
   - **Description**: "Server listing and filtering REST API with Angular frontend"
   - **Visibility**: Public or Private
4. Click **Create repository**

### 1.2 Push Your Code

```bash
# Navigate to your project
cd leaseweb-servers

# Add GitHub as remote (replace with your repository URL)
git remote add origin https://github.com/YOUR_USERNAME/leaseweb-server-explorer.git

# Push to GitHub
git branch -M main
git push -u origin main
```

---

## Step 2: Create Railway Project

### 2.1 Sign Up / Log In to Railway

1. Go to [railway.app](https://railway.app)
2. Click **Login** or **Start a New Project**
3. Sign in with GitHub (recommended for easy deployment)

### 2.2 Create New Project

1. From the Railway dashboard, click **New Project**
2. Select **Deploy from GitHub repo**
3. If prompted, authorize Railway to access your GitHub repositories
4. Find and select your `leaseweb-server-explorer` repository
5. Click **Deploy Now**

---

## Step 3: Configure Environment Variables

Railway will auto-detect the Dockerfile and start building. Before the build completes, configure environment variables:

### 3.1 Open Project Settings

1. Click on your deployed service
2. Go to the **Variables** tab
3. Add the following environment variables:

| Variable | Value | Description |
|----------|-------|-------------|
| `APP_ENV` | `prod` | Symfony environment |
| `APP_SECRET` | `<generate-32-char-string>` | Symfony secret key |
| `APP_DEBUG` | `0` | Disable debug mode |

### 3.2 Generate APP_SECRET

You can generate a secure secret using:

```bash
# Option 1: Using OpenSSL
openssl rand -hex 32

# Option 2: Using PHP
php -r "echo bin2hex(random_bytes(32));"

# Option 3: Online generator
# Visit: https://generate-secret.vercel.app/32
```

Example: `a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef12`

---

## Step 4: Configure Domain (Optional)

### 4.1 Generate Railway Domain

1. In your service, go to **Settings** tab
2. Scroll to **Networking** section
3. Click **Generate Domain**
4. Railway will provide a URL like: `your-app-name.up.railway.app`

### 4.2 Custom Domain (Optional)

1. In **Settings** → **Networking**
2. Click **Add Custom Domain**
3. Enter your domain (e.g., `servers.yourdomain.com`)
4. Add the provided CNAME record to your DNS settings

---

## Step 5: Verify Deployment

### 5.1 Check Build Logs

1. Click on the service in Railway
2. Go to **Deployments** tab
3. Click on the latest deployment
4. View build logs to ensure everything compiled successfully

Expected log output:
```
Building Docker image...
Step 1/X: FROM node:20-alpine AS frontend-builder
...
Step X/X: CMD ["/start.sh"]
Successfully built image
Deploying...
Deployment successful!
```

### 5.2 Test the Application

Once deployed, test the following URLs:

| URL | Expected Result |
|-----|-----------------|
| `https://your-app.up.railway.app/` | Redirects to API documentation |
| `https://your-app.up.railway.app/app` | Angular frontend loads |
| `https://your-app.up.railway.app/api/servers` | JSON response with servers |
| `https://your-app.up.railway.app/api/servers?sort=ram&order=desc` | Servers sorted by RAM |
| `https://your-app.up.railway.app/api/servers?priceMin=50&priceMax=200` | Price-filtered servers |
| `https://your-app.up.railway.app/api/filters` | JSON response with filter options |
| `https://your-app.up.railway.app/api/doc` | Swagger UI documentation |

### New API Features

The API includes these features:
- **Sorting**: `?sort=price|ram|storage|model&order=asc|desc`
- **Price Range**: `?priceMin=50&priceMax=200`
- **Root Redirect**: `/` automatically redirects to `/api/doc`

---

## Step 6: Automatic Deployments

Railway automatically deploys when you push to GitHub:

```bash
# Make changes to your code
git add .
git commit -m "Update feature X"
git push origin main

# Railway will automatically:
# 1. Detect the push
# 2. Build the Docker image
# 3. Deploy the new version
# 4. Switch traffic to the new deployment
```

---

## Troubleshooting

### Build Fails

**Problem**: Docker build fails
**Solution**: Check build logs for specific errors. Common issues:
- Missing dependencies in Dockerfile
- Syntax errors in configuration files

```bash
# Test locally first
docker build -t leaseweb-test .
docker run -p 8080:8080 leaseweb-test
```

### Application Not Loading

**Problem**: 502 Bad Gateway or application won't load
**Solutions**:
1. Check deployment logs for PHP errors
2. Verify environment variables are set correctly
3. Ensure database is initialized (fixtures loaded)

### Database Issues

**Problem**: "No data" or database errors
**Solution**: The startup script should automatically run migrations and fixtures. If not:

```bash
# Connect to Railway shell (if available)
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --no-interaction
```

### CORS Errors

**Problem**: Frontend can't reach API
**Solution**: The production config allows all origins. If you've restricted it, update `CORS_ALLOW_ORIGIN`:

```
CORS_ALLOW_ORIGIN='^https?://(your-app\.up\.railway\.app|localhost)(:[0-9]+)?$'
```

---

## Resource Usage

### Free Tier Limits

Railway's free tier includes:
- 500 hours of runtime per month
- 100 GB bandwidth
- 1 GB RAM per service

This project typically uses:
- ~256 MB RAM
- Minimal CPU (API is lightweight)
- ~50 MB storage (SQLite database)

### Scaling (If Needed)

For production workloads, Railway offers paid plans with:
- More RAM and CPU
- Multiple replicas
- Enhanced support

---

## Monitoring

### View Logs

1. Go to your service in Railway
2. Click **Logs** tab
3. View real-time application logs

### Metrics

Railway provides basic metrics:
- CPU usage
- Memory usage
- Network traffic
- Deployment history

---

## Rollback

If a deployment causes issues:

1. Go to **Deployments** tab
2. Find a previous working deployment
3. Click the **...** menu
4. Select **Rollback to this deployment**

---

## Summary

Your deployment checklist:

- [ ] Code pushed to GitHub
- [ ] Railway project created
- [ ] GitHub repo connected
- [ ] Environment variables configured
  - [ ] `APP_ENV=prod`
  - [ ] `APP_SECRET=<your-secret>`
  - [ ] `APP_DEBUG=0`
- [ ] Domain generated/configured
- [ ] Application tested and working
  - [ ] Root URL redirects to API docs
  - [ ] Sorting functionality works
  - [ ] Price range filter works
- [ ] Swagger documentation accessible
- [ ] All 53+ tests passing

---

## Quick Reference

| Action | Location |
|--------|----------|
| View logs | Railway → Service → Logs |
| Add variables | Railway → Service → Variables |
| Generate domain | Railway → Service → Settings → Networking |
| Rollback | Railway → Service → Deployments → Rollback |
| Redeploy | Push to GitHub or Railway → Service → Redeploy |

**Your live URL**: `https://[your-app-name].up.railway.app`

