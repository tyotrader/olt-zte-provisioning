# Installation Guide - ZTE OLT Provisioning System

## Table of Contents
1. [System Requirements](#system-requirements)
2. [Ubuntu + aaPanel Installation](#ubuntu--aapanel-installation)
3. [Docker Installation](#docker-installation)
4. [Post-Installation](#post-installation)
5. [Troubleshooting](#troubleshooting)

## System Requirements

### Minimum Requirements
- Ubuntu 20.04 LTS or 22.04 LTS
- 2 CPU Cores
- 4GB RAM
- 20GB Disk Space
- PHP 7.4+
- MySQL 8.0 or MariaDB 10.x
- Nginx 1.18+

### Recommended Requirements
- 4 CPU Cores
- 8GB RAM
- 50GB SSD Disk Space
- PHP 8.0+
- MySQL 8.0
- Redis 6.0+

## Ubuntu + aaPanel Installation

### Step 1: Install aaPanel

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install aaPanel
wget -O install.sh http://www.aapanel.com/script/install-ubuntu_6.0_en.sh
sudo bash install.sh
```

Access aaPanel at `http://your-server-ip:7800` and complete the setup.

### Step 2: Install Required Software

In aaPanel:
1. Go to "App Store"
2. Install:
   - Nginx 1.24
   - PHP 7.4 (or 8.0)
   - MySQL 8.0
   - phpMyAdmin

### Step 3: Install PHP Extensions

In aaPanel:
1. Go to "PHP 7.4" → "Install Extensions"
2. Install:
   - fileinfo
   - redis
   - snmp
   - sockets
   - gd
   - mbstring
   - exif
   - pcntl
   - bcmath

### Step 4: Create Database

In aaPanel:
1. Go to "Database" → "Add Database"
2. Create:
   - Database Name: `olt_provisioning`
   - Username: `oltuser`
   - Password: Generate strong password
   - Note down the credentials

Or via SSH:
```bash
mysql -u root -p
CREATE DATABASE olt_provisioning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'oltuser'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON olt_provisioning.* TO 'oltuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 5: Upload Application

```bash
# Create directory
mkdir -p /www/wwwroot/olt.yourdomain.com

# Upload files via SFTP or:
cd /www/wwwroot/olt.yourdomain.com
git clone https://github.com/your-repo/olt-provisioning.git .

# Set permissions
chown -R www:www /www/wwwroot/olt.yourdomain.com
chmod -R 755 /www/wwwroot/olt.yourdomain.com
```

### Step 6: Configure Nginx

In aaPanel:
1. Go to "Website" → "Add Site"
2. Enter:
   - Domain: `olt.yourdomain.com` (or your IP)
   - Root Directory: `/www/wwwroot/olt.yourdomain.com/public`
   - PHP Version: 7.4

3. Edit site configuration, add:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/tmp/php-cgi-74.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### Step 7: Install Dependencies

```bash
cd /www/wwwroot/olt.yourdomain.com

# Install Composer if not installed
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Set permissions again
chown -R www:www /www/wwwroot/olt.yourdomain.com
chmod -R 755 storage bootstrap/cache
```

### Step 8: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env file
nano .env
```

Update the following:
```
APP_NAME="Smart OLT"
APP_URL=http://olt.yourdomain.com

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=olt_provisioning
DB_USERNAME=oltuser
DB_PASSWORD=your_secure_password

# If using Redis (optional)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Step 9: Generate Keys and Run Migrations

```bash
# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run database migrations
php artisan migrate

# Seed default admin user
php artisan db:seed --class=DefaultAdminSeeder
```

### Step 10: Configure Cron Jobs

```bash
# Edit crontab
crontab -e

# Add this line:
* * * * * cd /www/wwwroot/olt.yourdomain.com && php artisan schedule:run >> /dev/null 2>&1
```

### Step 11: Restart Services

```bash
# In aaPanel or via SSH:
/etc/init.d/nginx restart
/etc/init.d/php-fpm-74 restart
```

## Docker Installation

### Prerequisites
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### Deploy with Docker Compose
```bash
# Clone repository
git clone https://github.com/your-repo/olt-provisioning.git
cd olt-provisioning

# Copy environment file
cp .env.example .env

# Build and start containers
docker-compose up -d --build

# Install dependencies
docker-compose exec app composer install

# Generate keys
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret

# Run migrations
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed --class=DefaultAdminSeeder

# Check status
docker-compose ps
```

Access the application at `http://your-server-ip`

## Post-Installation

### 1. Access the Application

Open browser and navigate to:
- `http://olt.yourdomain.com` (if using domain)
- `http://your-server-ip` (if using IP)

### 2. Default Login
- Username: `admin`
- Password: `admin`

### 3. Change Default Password

1. Login with default credentials
2. Go to "Account Settings"
3. Change password immediately

### 4. Add Your First OLT

1. Go to "OLT Management"
2. Click "Add OLT"
3. Fill in:
   - OLT Name
   - IP Address
   - OLT Model (C300/C320/C600/ZXA10)
   - Telnet Username/Password
   - SNMP Community
4. Click "Test Connection"
5. Save if successful

### 5. Run ONU Detection

1. Go to "ONU Detection"
2. Click "Scan Now"
3. System will scan for unregistered ONUs

## Troubleshooting

### Issue: 500 Internal Server Error

```bash
# Check permissions
chmod -R 755 /www/wwwroot/olt.yourdomain.com/storage
chown -R www:www /www/wwwroot/olt.yourdomain.com

# Check logs
tail -f /www/wwwroot/olt.yourdomain.com/storage/logs/laravel.log
```

### Issue: Database Connection Failed

```bash
# Test connection
mysql -u oltuser -p -e "USE olt_provisioning; SHOW TABLES;"

# Check .env configuration
cat /www/wwwroot/olt.yourdomain.com/.env | grep DB_
```

### Issue: SNMP Not Working

```bash
# Install SNMP tools
apt-get install snmp snmp-mibs-downloader

# Test SNMP connection
snmpwalk -v 2c -c public <OLT_IP> 1.3.6.1.2.1.1.1.0

# Check PHP SNMP extension
php -m | grep snmp
```

### Issue: Telnet Connection Failed

```bash
# Test telnet
telnet <OLT_IP> 23

# Check firewall
ufw allow 23/tcp

# Check if OLT responds
ping <OLT_IP>
```

### Issue: Permission Denied on Storage

```bash
# Fix permissions
chmod -R 777 /www/wwwroot/olt.yourdomain.com/storage
chmod -R 777 /www/wwwroot/olt.yourdomain.com/bootstrap/cache
```

## Security Recommendations

1. **Change Default Password**: Immediately after installation
2. **Use HTTPS**: Configure SSL certificate in aaPanel
3. **Firewall**: Only allow necessary ports (80, 443, 22)
4. **Regular Updates**: Keep system and packages updated
5. **Backups**: Configure automated database backups
6. **SNMP Security**: Use SNMPv3 with authentication

## Support

For additional support:
- Check logs in `storage/logs/`
- Review aaPanel error logs
- Contact your system administrator
