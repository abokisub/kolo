# Installation Guide

## Requirements

- PHP >= 8.1
- MySQL >= 5.7
- Composer
- Node.js >= 16.x
- cPanel or VPS with Apache/Nginx

---

## Installation Steps

### 1. Upload Files

Upload the entire project to your server:
```
/home/username/yourapp/
```

### 2. Set Document Root (IMPORTANT!)

**In cPanel:**
1. Go to **Domains** → **Manage**
2. Find your domain
3. Set **Document Root** to: `/home/username/yourapp/public`
4. Click **Update**

**Why?** This is the Laravel standard and keeps your application secure by hiding sensitive files.

### 3. Configure Environment

Copy and configure `.env` file:
```bash
cd /home/username/yourapp
cp .env.example .env
nano .env
```

Update these values:
```env
APP_NAME="Your App Name"
APP_URL=https://yourdomain.com

DB_DATABASE=your_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Add your API keys and settings
```

### 4. Install Dependencies

```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies and build React
cd frontend
npm install
npm run build
cd ..
```

### 5. Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 6. Generate Application Key

```bash
php artisan key:generate
```

### 7. Run Migrations

```bash
php artisan migrate --force
```

### 8. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9. Build React Admin Panel

The React admin should already be built in step 4, but if needed:
```bash
cd frontend
npm run build
```

The build files will be in `frontend/build/` and served by Laravel.

---

## Post-Installation

### Update Flutter App Base URL

In your Flutter app, update the API base URL:
```dart
// lib/services/api_service.dart
static const String baseUrl = 'https://yourdomain.com';
```

### Test Installation

1. **Visit:** `https://yourdomain.com` - Should show React admin login
2. **Test API:** `curl https://yourdomain.com/api/health` - Should return JSON
3. **Test Flutter:** Login with the mobile app

---

## Troubleshooting

### Issue: "Index of /" showing

**Solution:** Document root is not set correctly. It must point to `/public` folder.

### Issue: 500 Error

**Solution:** 
1. Check `.env` file is configured
2. Run `php artisan key:generate`
3. Check `storage/logs/laravel.log` for errors

### Issue: React admin not loading

**Solution:**
1. Ensure React is built: `cd frontend && npm run build`
2. Check `resources/views/index.blade.php` exists
3. Clear cache: `php artisan cache:clear`

### Issue: API returning HTML instead of JSON

**Solution:** Check `routes/web.php` - the catch-all route should be at the bottom.

---

## Security Checklist

- [ ] `.env` file is configured and not publicly accessible
- [ ] Document root points to `/public` folder
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production` in production
- [ ] SSL certificate is installed
- [ ] Database credentials are strong
- [ ] File permissions are correct (755 for directories, 644 for files)

---

## Updating the Application

```bash
# Backup database first!
mysqldump -u user -p database > backup.sql

# Pull latest code
git pull origin Master

# Update dependencies
composer install --optimize-autoloader --no-dev
cd frontend && npm install && npm run build && cd ..

# Run migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Support

For issues or questions, contact: [Your Support Email]
