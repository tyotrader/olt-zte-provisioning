<?php

namespace App\Services;

use App\Models\Olt;
use App\Models\Onu;

class SNMPService
{
    private $olt;
    private $community;
    private $version;
    private $timeout = 1000000; // 1 second
    private $retry = 1;

    // ZTE OLT SNMP OIDs
    const OID_SYSNAME = '1.3.6.1.2.1.1.5.0';
    const OID_SYSUPTIME = '1.3.6.1.2.1.1.3.0';
    const OID_SYSDESC = '1.3.6.1.2.1.1.1.0';
    
    // ZTE C300/C320 Specific OIDs
    const OID_CPU_USAGE = '1.3.6.1.4.1.3902.1015.2.1.1.3.1.5.0';
    const OID_TEMPERATURE = '1.3.6.1.4.1.3902.1015.2.1.1.3.1.21.0';
    const OID_MEMORY_USAGE = '1.3.6.1.4.1.3902.1015.2.1.1.3.1.9.0';
    
    // GPON OIDs
    const OID_PON_PORT_COUNT = '1.3.6.1.4.1.3902.1012.3.13.1.1.1.0';
    const OID_ONU_COUNT = '1.3.6.1.4.1.3902.1012.3.13.1.1.2.0';
    
    // ONU Status OIDs
    const OID_ONU_RX_POWER = '1.3.6.1.4.1.3902.1012.3.50.12.1.1.14';
    const OID_ONU_TX_POWER = '1.3.6.1.4.1.3902.1012.3.50.12.1.1.15';
    const OID_ONU_STATUS = '1.3.6.1.4.1.3902.1012.3.50.12.1.1.1';
    const OID_ONU_DISTANCE = '1.3.6.1.4.1.3902.1012.3.50.12.1.1.16';
    const OID_ONU_TEMP = '1.3.6.1.4.1.3902.1012.3.50.12.1.1.17';
    
    // Traffic OIDs
    const OID_IF_HC_OCTETS_IN = '1.3.6.1.2.1.31.1.1.1.6';
    const OID_IF_HC_OCTETS_OUT = '1.3.6.1.2.1.31.1.1.1.10';
    const OID_IF_HC_PACKETS_IN = '1.3.6.1.2.1.31.1.1.1.7';
    const OID_IF_HC_PACKETS_OUT = '1.3.6.1.2.1.31.1.1.1.11';

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
        $this->community = $olt->snmp_read_community ?? $olt->snmp_community;
        $this->version = $olt->snmp_version === 'v3' ? '3' : ($olt->snmp_version === 'v1' ? '1' : '2c');
        
        // Enable PHP SNMP extension
        if (!extension_loaded('snmp')) {
            throw new \Exception('SNMP extension not loaded');
        }
    }

    public function getSystemInfo()
    {
        return [
            'sysname' => $this->get(self::OID_SYSNAME),
            'sysuptime' => $this->get(self::OID_SYSUPTIME),
            'sysdesc' => $this->get(self::OID_SYSDESC),
        ];
    }

    public function getOltStatus()
    {
        return [
            'cpu_usage' => $this->getNumeric(self::OID_CPU_USAGE),
            'temperature' => $this->getNumeric(self::OID_TEMPERATURE),
            'memory_usage' => $this->getNumeric(self::OID_MEMORY_USAGE),
            'pon_port_count' => $this->getNumeric(self::OID_PON_PORT_COUNT),
            'onu_count' => $this->getNumeric(self::OID_ONU_COUNT),
        ];
    }

    public function pollOnu(Onu $onu)
    {
        $onuIndex = $this->calculateOnuIndex($onu->slot, $onu->pon_port, $onu->onu_id);
        
        return [
            'rx_power' => $this->getOnuRxPower($onuIndex),
            'tx_power' => $this->getOnuTxPower($onuIndex),
            'status' => $this->getOnuStatus($onuIndex),
            'distance' => $this->getOnuDistance($onuIndex),
            'temperature' => $this->getOnuTemperature($onuIndex),
        ];
    }

    public function pollOnuTraffic(Onu $onu)
    {
        $ifIndex = $this->getOnuIfIndex($onu);
        
        if (!$ifIndex) {
            return null;
        }
        
        return [
            'rx_bytes' => $this->getNumeric(self::OID_IF_HC_OCTETS_IN . ".{$ifIndex}") ?? 0,
            'tx_bytes' => $this->getNumeric(self::OID_IF_HC_OCTETS_OUT . ".{$ifIndex}") ?? 0,
            'rx_packets' => $this->getNumeric(self::OID_IF_HC_PACKETS_IN . ".{$ifIndex}") ?? 0,
            'tx_packets' => $this->getNumeric(self::OID_IF_HC_PACKETS_OUT . ".{$ifIndex}") ?? 0,
        ];
    }

    public function getOnuRxPower($onuIndex)
    {
        $value = $this->getNumeric(self::OID_ONU_RX_POWER . ".{$onuIndex}");
        return $value !== null ? round($value / 10000, 2) : null;
    }

    public function getOnuTxPower($onuIndex)
    {
        $value = $this->getNumeric(self::OID_ONU_TX_POWER . ".{$onuIndex}");
        return $value !== null ? round($value / 10000, 2) : null;
    }

    public function getOnuStatus($onuIndex)
    {
        $value = $this->getNumeric(self::OID_ONU_STATUS . ".{$onuIndex}");
        return $value == 1 ? 'online' : 'offline';
    }

    public function getOnuDistance($onuIndex)
    {
        $value = $this->getNumeric(self::OID_ONU_DISTANCE . ".{$onuIndex}");
        return $value !== null ? round($value / 10, 2) : null;
    }

    public function getOnuTemperature($onuIndex)
    {
        $value = $this->getNumeric(self::OID_ONU_TEMP . ".{$onuIndex}");
        return $value !== null ? round($value, 2) : null;
    }

    public function walkOnuTable()
    {
        return $this->walk(self::OID_ONU_STATUS);
    }

    public function getUplinkTraffic($interfaceIndex = 1)
    {
        return [
            'rx_bytes' => $this->getNumeric(self::OID_IF_HC_OCTETS_IN . ".{$interfaceIndex}") ?? 0,
            'tx_bytes' => $this->getNumeric(self::OID_IF_HC_OCTETS_OUT . ".{$interfaceIndex}") ?? 0,
        ];
    }

    // Private methods
    private function get($oid)
    {
        try {
            $result = snmpget($this->olt->ip_address, $this->community, $oid, $this->timeout, $this->retry);
            return $this->cleanSnmpValue($result);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getNumeric($oid)
    {
        $value = $this->get($oid);
        return is_numeric($value) ? (float) $value : null;
    }

    private function walk($oid)
    {
        try {
            $results = snmpwalk($this->olt->ip_address, $this->community, $oid, $this->timeout, $this->retry);
            $parsed = [];
            foreach ($results as $key => $value) {
                $parsed[$key] = $this->cleanSnmpValue($value);
            }
            return $parsed;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function cleanSnmpValue($value)
    {
        if ($value === false) return null;
        
        // Remove type prefixes
        $value = preg_replace('/^(STRING|INTEGER|Counter32|Counter64|Gauge32|OID|Hex-STRING|IpAddress):\s*/', '', $value);
        $value = trim($value, '"\' ');
        
        return $value;
    }

    private function calculateOnuIndex($slot, $port, $onuId)
    {
        // ZTE ONU Index calculation formula
        // Index = slot * 10000000 + port * 100000 + onu_id
        return ($slot * 10000000) + ($port * 100000) + $onuId;
    }

    private function getOnuIfIndex(Onu $onu)
    {
        // This would need to be retrieved from the OLT's interface table
        // For now, return calculated index
        return $this->calculateOnuIndex($onu->slot, $onu->pon_port, $onu->onu_id);
    }

    public function testConnection()
    {
        try {
            $sysName = $this->get(self::OID_SYSNAME);
            return !empty($sysName);
        } catch (\Exception $e) {
            return false;
        }
    }
}
