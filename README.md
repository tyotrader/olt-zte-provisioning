# Smart OLT - ZTE OLT Provisioning System

A comprehensive, production-ready Web Provisioning System for ZTE OLT (C300 / C320 / C600 / ZXA10) designed for ISP Network Operations Centers (NOC).

## Features

- **OLT Management**: Add, edit, delete and monitor multiple ZTE OLTs
- **PON Port Monitoring**: Real-time monitoring of PON ports with ONU counts and signal levels
- **ONU Detection**: Automatic detection of unregistered ONUs via Telnet
- **ONU Provisioning**: Automated ONU registration and configuration
- **ONU Management**: Complete lifecycle management of ONUs
- **Service Configuration**: Manage TCONT profiles, GEM ports, VLANs
- **Fiber Topology Map**: Interactive Leaflet.js map showing OLT→ODP→ONU topology
- **SNMP Monitoring**: Real-time polling of ONU status, RX/TX power, traffic
- **Telnet Automation**: Full Telnet command execution for provisioning
- **WebSocket Real-time Updates**: Live dashboard with 0.5s refresh interval
- **JWT Authentication**: Secure API and web authentication

## Technology Stack

- **Backend**: PHP 7.4+, Laravel 8/9/10
- **Frontend**: Blade Templates, TailwindCSS, Chart.js, Leaflet.js
- **Database**: MySQL 8.0 / MariaDB 10.x
- **Cache/Queue**: Redis
- **WebSocket**: Soketi / Laravel WebSockets
- **Network**: SNMP v1/v2c/v3, Telnet/SSH

## Requirements

- PHP 7.4 or higher
- PHP Extensions: pdo_mysql, mbstring, gd, snmp, sockets, redis
- MySQL 8.0 or MariaDB 10.x
- Nginx or Apache
- Redis (optional, for queue/cache)
- Composer

## Installation

### Method 1: Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/your-repo/olt-provisioning.git
cd olt-provisioning

# Copy environment file
cp .env.example .env

# Start Docker containers
docker-compose up -d

# Install dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Seed default admin
docker-compose exec app php artisan db:seed --class=DefaultAdminSeeder

# Configure JWT
docker-compose exec app php artisan jwt:secret
```

### Method 2: Manual Installation (aaPanel/Ubuntu)

```bash
# 1. Create database in aaPanel MySQL
# Database: olt_provisioning
# User: oltuser
# Password: your_secure_password

# 2. Upload files to /www/wwwroot/olt.yourdomain.com

# 3. Install dependencies
cd /www/wwwroot/olt.yourdomain.com
composer install --no-dev

# 4. Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www:www /www/wwwroot/olt.yourdomain.com

# 5. Configure .env file
nano .env
# Update database credentials and APP_URL

# 6. Generate keys
php artisan key:generate
php artisan jwt:secret

# 7. Run migrations
php artisan migrate

# 8. Seed admin user
php artisan db:seed --class=DefaultAdminSeeder

# 9. Configure Nginx in aaPanel
# Add site with root: /www/wwwroot/olt.yourdomain.com/public
# Use the provided nginx.conf configuration
```

## Nginx Configuration (aaPanel)

```nginx
server {
    listen 80;
    server_name olt.yourdomain.com;
    root /www/wwwroot/olt.yourdomain.com/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-74.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Default Login

- **Username**: admin
- **Password**: admin

*Note: Change default password after first login!*

## Telnet Commands Reference

### Show Commands
```
show gpon onu uncfg                    # Show unregistered ONUs
show gpon onu state gpon-onu_1/1       # Show ONU state on PON port
show gpon onu detail-info gpon-onu_1/1:1  # Show ONU details
show gpon remote-onu optical-info gpon-onu_1/1:1  # Show optical info
show running-config interface gpon-onu_1/1:1  # Show ONU config
```

### Configuration Commands
```
configure terminal
interface gpon-onu_1/1
onu 1 type F601 sn ZNTS12345678
exit

interface gpon-onu_1/1:1
tcont 1 profile DATA
gemport 1 name Gem1 tcont 1
exit

service-port 100 gpon-onu_1/1:1 gemport 1 user-vlan 100 vlan 100
```

### ONU Operations
```
reboot gpon-onu_1/1:1
restore factory gpon-onu_1/1:1
no onu 1  # Delete ONU
```

## API Documentation

### Authentication
```bash
POST /api/login
{
    "username": "admin",
    "password": "admin"
}
```

### OLT Management
```bash
GET /api/olts
GET /api/olt/{id}/test-connection
GET /api/olt/{id}/stats
```

### ONU Operations
```bash
GET /api/onu/{id}/realtime
POST /api/onu/{id}/reboot
POST /api/onu/bulk-reboot
```

### Detection
```bash
POST /api/detection/scan
GET /api/detection/pending
GET /api/detection/stats
```

## WebSocket Configuration

Edit `.env`:
```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_HOST="${PUSHER_HOST}"
MIX_PUSHER_PORT="${PUSHER_PORT}"
```

Start Soketi:
```bash
npx @soketi/soketi start
```

## Cron Jobs

Add to crontab:
```bash
* * * * * cd /var/www/olt && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### SNMP Not Working
```bash
# Install SNMP extension
sudo apt-get install php-snmp snmp snmp-mibs-downloader
sudo download-mibs

# Test SNMP
snmpwalk -v 2c -c public <OLT_IP> 1.3.6.1.2.1.1.1.0
```

### Telnet Connection Failed
- Verify OLT IP and port (default: 23)
- Check firewall rules
- Verify credentials

### Permission Issues
```bash
chown -R www-data:www-data /var/www/olt
chmod -R 755 /var/www/olt/storage
```

## License

This project is licensed under the MIT License.

## Support

For support and feature requests, please contact your system administrator or create an issue in the repository.

## Credits

Developed for ISP Network Operations by lmn team
