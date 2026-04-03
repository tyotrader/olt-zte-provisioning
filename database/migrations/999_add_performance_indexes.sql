-- Performance Indexes for OLT Provisioning System

-- ONU table indexes
CREATE INDEX IF NOT EXISTS idx_onu_olt_id ON onu(olt_id);
CREATE INDEX IF NOT EXISTS idx_onu_pon_port_id ON onu(pon_port_id);
CREATE INDEX IF NOT EXISTS idx_onu_status ON onu(status);
CREATE INDEX IF NOT EXISTS idx_onu_customer_name ON onu(customer_name);
CREATE INDEX IF NOT EXISTS idx_onu_onu_sn ON onu(onu_sn);
CREATE INDEX IF NOT EXISTS idx_onu_rx_power ON onu(rx_power);
CREATE INDEX IF NOT EXISTS idx_onu_is_active ON onu(is_active);
CREATE INDEX IF NOT EXISTS idx_onu_last_seen ON onu(last_seen);
CREATE INDEX IF NOT EXISTS idx_onu_slot_port ON onu(slot, pon_port);
CREATE INDEX IF NOT EXISTS idx_onu_coordinates ON onu(latitude, longitude);

-- OLT table indexes
CREATE INDEX IF NOT EXISTS idx_olt_ip_address ON olt(ip_address);
CREATE INDEX IF NOT EXISTS idx_olt_is_active ON olt(is_active);
CREATE INDEX IF NOT EXISTS idx_olt_status ON olt(status);

-- PON Port table indexes
CREATE INDEX IF NOT EXISTS idx_pon_port_olt_id ON pon_port(olt_id);
CREATE INDEX IF NOT EXISTS idx_pon_port_slot_port ON pon_port(slot, port);
CREATE INDEX IF NOT EXISTS idx_pon_port_status ON pon_port(status);

-- ODP table indexes
CREATE INDEX IF NOT EXISTS idx_odp_olt_id ON odp(olt_id);
CREATE INDEX IF NOT EXISTS idx_odp_latitude_longitude ON odp(latitude, longitude);

-- ONU Detection table indexes
CREATE INDEX IF NOT EXISTS idx_onu_detection_olt_id ON onu_detection(olt_id);
CREATE INDEX IF NOT EXISTS idx_onu_detection_status ON onu_detection(status);
CREATE INDEX IF NOT EXISTS idx_onu_detection_created_at ON onu_detection(created_at);
CREATE INDEX IF NOT EXISTS idx_onu_detection_slot_port ON onu_detection(slot, pon_port);

-- Provision Log table indexes
CREATE INDEX IF NOT EXISTS idx_provision_log_olt_id ON provision_log(olt_id);
CREATE INDEX IF NOT EXISTS idx_provision_log_onu_id ON provision_log(onu_id);
CREATE INDEX IF NOT EXISTS idx_provision_log_status ON provision_log(status);
CREATE INDEX IF NOT EXISTS idx_provision_log_executed_at ON provision_log(executed_at);

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_onu_olt_status ON onu(olt_id, status);
CREATE INDEX IF NOT EXISTS idx_onu_olt_active ON onu(olt_id, is_active);
CREATE INDEX IF NOT EXISTS idx_onu_detection_olt_pending ON onu_detection(olt_id, status);

-- Full-text search indexes (MySQL 5.6+)
ALTER TABLE onu ADD FULLTEXT INDEX ft_onu_search (customer_name, customer_id, phone, address);
ALTER TABLE olt ADD FULLTEXT INDEX ft_olt_search (olt_name, location, description);
