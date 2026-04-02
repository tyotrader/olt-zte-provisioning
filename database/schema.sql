-- ZTE OLT Provisioning System - Database Schema
-- This file contains raw SQL for database setup

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    fullname VARCHAR(255) NULL,
    role VARCHAR(50) DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OLTs table
CREATE TABLE IF NOT EXISTS olts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    olt_name VARCHAR(255) NOT NULL,
    ip_address VARCHAR(255) NOT NULL,
    olt_model VARCHAR(50) DEFAULT 'C300',
    location VARCHAR(255) NULL,
    description TEXT NULL,
    snmp_community VARCHAR(255) DEFAULT 'public',
    snmp_read_community VARCHAR(255) DEFAULT 'public',
    snmp_write_community VARCHAR(255) NULL,
    snmp_port INT DEFAULT 161,
    snmp_version VARCHAR(10) DEFAULT 'v2c',
    telnet_username VARCHAR(255) NOT NULL,
    telnet_password TEXT NOT NULL,
    telnet_port INT DEFAULT 23,
    timeout INT DEFAULT 10,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_poll TIMESTAMP NULL,
    status VARCHAR(50) DEFAULT 'unknown',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PON Ports table
CREATE TABLE IF NOT EXISTS pon_ports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    olt_id BIGINT UNSIGNED NOT NULL,
    slot INT NOT NULL,
    port INT NOT NULL,
    pon_type VARCHAR(50) DEFAULT 'GPON',
    max_onu INT DEFAULT 128,
    current_onu_count INT DEFAULT 0,
    online_onu INT DEFAULT 0,
    offline_onu INT DEFAULT 0,
    average_rx_power DECIMAL(8,2) NULL,
    admin_status VARCHAR(50) DEFAULT 'up',
    oper_status VARCHAR(50) DEFAULT 'up',
    utilization DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_port (olt_id, slot, port),
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ODPs table
CREATE TABLE IF NOT EXISTS odps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    odp_name VARCHAR(255) UNIQUE NOT NULL,
    olt_id BIGINT UNSIGNED NOT NULL,
    pon_port_id BIGINT UNSIGNED NULL,
    location VARCHAR(255) NULL,
    address TEXT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    total_ports INT DEFAULT 16,
    used_ports INT DEFAULT 0,
    description TEXT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE CASCADE,
    FOREIGN KEY (pon_port_id) REFERENCES pon_ports(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ONU Detection table
CREATE TABLE IF NOT EXISTS onu_detection (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    olt_id BIGINT UNSIGNED NOT NULL,
    slot INT NOT NULL,
    pon_port INT NOT NULL,
    onu_sn VARCHAR(255) NOT NULL,
    onu_password VARCHAR(255) NULL,
    onu_type VARCHAR(100) NULL,
    loid VARCHAR(255) NULL,
    loid_password VARCHAR(255) NULL,
    firmware_version VARCHAR(100) NULL,
    hardware_version VARCHAR(100) NULL,
    discovery_time TIMESTAMP NOT NULL,
    status VARCHAR(50) DEFAULT 'detected',
    is_ignored TINYINT(1) DEFAULT 0,
    registered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_detection (olt_id, slot, pon_port, onu_sn),
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ONUs table
CREATE TABLE IF NOT EXISTS onus (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    olt_id BIGINT UNSIGNED NOT NULL,
    pon_port_id BIGINT UNSIGNED NOT NULL,
    odp_id BIGINT UNSIGNED NULL,
    onu_id INT NOT NULL,
    onu_sn VARCHAR(255) UNIQUE NOT NULL,
    onu_type VARCHAR(100) DEFAULT 'F601',
    customer_name VARCHAR(255) NOT NULL,
    customer_id VARCHAR(255) NULL,
    address TEXT NULL,
    phone VARCHAR(50) NULL,
    slot INT NOT NULL,
    pon_port INT NOT NULL,
    tcont_profile VARCHAR(255) NULL,
    gemport_template VARCHAR(255) NULL,
    vlan_profile VARCHAR(255) NULL,
    service_port_template VARCHAR(255) NULL,
    wan_mode VARCHAR(50) DEFAULT 'pppoe',
    pppoe_username VARCHAR(255) NULL,
    pppoe_password TEXT NULL,
    static_ip VARCHAR(255) NULL,
    static_gateway VARCHAR(255) NULL,
    static_subnet VARCHAR(255) NULL,
    wifi_ssid VARCHAR(255) NULL,
    wifi_password TEXT NULL,
    wifi_enabled TINYINT(1) DEFAULT 1,
    rx_power DECIMAL(8,2) NULL,
    tx_power DECIMAL(8,2) NULL,
    distance DECIMAL(8,2) NULL,
    temperature DECIMAL(6,2) NULL,
    firmware_version VARCHAR(100) NULL,
    uptime VARCHAR(100) NULL,
    status VARCHAR(50) DEFAULT 'offline',
    last_seen TIMESTAMP NULL,
    registered_at TIMESTAMP NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    notes TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_onu (olt_id, slot, pon_port, onu_id),
    KEY status_last_seen (status, last_seen),
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE CASCADE,
    FOREIGN KEY (pon_port_id) REFERENCES pon_ports(id) ON DELETE CASCADE,
    FOREIGN KEY (odp_id) REFERENCES odps(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Profile Tables
CREATE TABLE IF NOT EXISTS tcont_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_name VARCHAR(255) UNIQUE NOT NULL,
    tcont_id INT NOT NULL,
    bandwidth_profile VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bandwidth_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_name VARCHAR(255) UNIQUE NOT NULL,
    profile_type VARCHAR(50) DEFAULT 'fixed',
    fixed_bw INT DEFAULT 0,
    assure_bw INT DEFAULT 0,
    max_bw INT NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gemport_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) UNIQUE NOT NULL,
    gemport_id INT NOT NULL,
    tcont_profile VARCHAR(255) NOT NULL,
    traffic_class VARCHAR(50) DEFAULT 'be',
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_port_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) UNIQUE NOT NULL,
    service_port_id INT NOT NULL,
    vport INT DEFAULT 1,
    user_vlan INT NOT NULL,
    c_vid INT NULL,
    vlan_mode VARCHAR(50) DEFAULT 'tag',
    translation_mode VARCHAR(100) DEFAULT 'vlan-stacking',
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vlan_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_name VARCHAR(255) UNIQUE NOT NULL,
    vlan_id INT NOT NULL,
    vlan_name VARCHAR(255) NULL,
    vlan_type VARCHAR(50) DEFAULT 'residential',
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs Tables
CREATE TABLE IF NOT EXISTS provision_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    olt_id BIGINT UNSIGNED NOT NULL,
    onu_id BIGINT UNSIGNED NULL,
    action VARCHAR(255) NOT NULL,
    command TEXT NOT NULL,
    response TEXT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    error_message TEXT NULL,
    user_id BIGINT UNSIGNED NULL,
    executed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE CASCADE,
    FOREIGN KEY (onu_id) REFERENCES onus(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS onu_traffic (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    onu_id BIGINT UNSIGNED NOT NULL,
    rx_bytes BIGINT NOT NULL,
    tx_bytes BIGINT NOT NULL,
    rx_packets BIGINT NOT NULL,
    tx_packets BIGINT NOT NULL,
    rx_errors BIGINT DEFAULT 0,
    tx_errors BIGINT DEFAULT 0,
    recorded_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY onu_recorded (onu_id, recorded_at),
    FOREIGN KEY (onu_id) REFERENCES onus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(50) DEFAULT 'info',
    category VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    context JSON NULL,
    ip_address VARCHAR(255) NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin)
INSERT INTO users (username, password, fullname, email, role, is_active) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@localhost', 'admin', 1)
ON DUPLICATE KEY UPDATE username = username;
