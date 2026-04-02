<?php

namespace App\Services;

use App\Models\Olt;
use App\Models\ProvisionLog;

class TelnetService
{
    private $connection;
    private $olt;
    private $timeout;
    private $prompt;
    private $connected = false;
    
    // ZTE OLT Prompts
    const PROMPT_USERNAME = 'Username:';
    const PROMPT_PASSWORD = 'Password:';
    const PROMPT_MAIN = '#>';
    const PROMPT_CONFIG = '(config)#';
    const PROMPT_INTERFACE = '(config-if)#';
    const PROMPT_ONU = '(config-onu)#';

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
        $this->timeout = $olt->timeout ?? 10;
        $this->prompt = self::PROMPT_MAIN;
    }

    public function connect()
    {
        try {
            $this->connection = fsockopen($this->olt->ip_address, $this->olt->telnet_port, $errno, $errstr, $this->timeout);
            
            if (!$this->connection) {
                throw new \Exception("Telnet connection failed: $errstr ($errno)");
            }
            
            stream_set_timeout($this->connection, $this->timeout);
            
            // Read initial banner
            $this->readUntil([self::PROMPT_USERNAME, 'login:', 'UserName:']);
            
            // Send username
            $this->write($this->olt->telnet_username);
            $this->readUntil([self::PROMPT_PASSWORD, 'Password:']);
            
            // Send password
            $this->write($this->olt->getDecryptedPasswordAttribute());
            $output = $this->readUntil([self::PROMPT_MAIN, '>', '#', 'Login failed']);
            
            if (strpos($output, 'Login failed') !== false || strpos($output, 'fail') !== false) {
                throw new \Exception('Authentication failed');
            }
            
            $this->connected = true;
            
            // Disable pagination
            $this->execute('terminal length 0');
            
            return true;
        } catch (\Exception $e) {
            $this->logCommand('connect', '', $e->getMessage(), 'failed');
            return false;
        }
    }

    public function disconnect()
    {
        if ($this->connection) {
            $this->write('exit');
            fclose($this->connection);
            $this->connected = false;
        }
    }

    public function execute($command)
    {
        if (!$this->connected) {
            throw new \Exception('Not connected to OLT');
        }
        
        $this->write($command);
        $output = $this->readUntil($this->prompt);
        
        // Clean output (remove command echo and prompt)
        $lines = explode("\n", $output);
        array_shift($lines); // Remove command echo
        array_pop($lines);   // Remove prompt
        
        return implode("\n", $lines);
    }

    public function executeMultiple(array $commands)
    {
        $results = [];
        foreach ($commands as $command) {
            $results[] = [
                'command' => $command,
                'output' => $this->execute($command)
            ];
        }
        return $results;
    }

    public function enterConfigMode()
    {
        $this->execute('configure terminal');
        $this->prompt = self::PROMPT_CONFIG;
    }

    public function exitConfigMode()
    {
        $this->execute('exit');
        $this->prompt = self::PROMPT_MAIN;
    }

    public function enterInterface($slot, $port)
    {
        $this->execute("interface gpon-onu_{$slot}/{$port}");
        $this->prompt = self::PROMPT_INTERFACE;
    }

    public function enterOnuConfig($slot, $port, $onuId)
    {
        $this->execute("onu {$onuId} type F601 sn 12345678");
        $this->execute("onu {$onuId} profile line DATA ip DATA");
        $this->prompt = self::PROMPT_ONU;
    }

    // ONU Provisioning Methods
    public function registerOnu($slot, $port, $onuId, $sn, $type = 'F601')
    {
        $commands = [
            "interface gpon-onu_{$slot}/{$port}",
            "onu {$onuId} type {$type} sn {$sn}",
            "exit"
        ];
        return $this->executeMultiple($commands);
    }

    public function configureTcont($slot, $port, $onuId, $tcontId, $profile)
    {
        $commands = [
            "interface gpon-onu_{$slot}/{$port}:{$onuId}",
            "tcont {$tcontId} profile {$profile}",
            "exit"
        ];
        return $this->executeMultiple($commands);
    }

    public function configureGemport($slot, $port, $onuId, $gemportId, $tcontId)
    {
        $commands = [
            "interface gpon-onu_{$slot}/{$port}:{$onuId}",
            "gemport {$gemportId} name Gem{$gemportId} tcont {$tcontId}",
            "exit"
        ];
        return $this->executeMultiple($commands);
    }

    public function configureServicePort($oltId, $slot, $port, $onuId, $vport, $userVlan, $svlan, $gemport)
    {
        $commands = [
            "service-port {$oltId} gpon-onu_{$slot}/{$port}:{$onuId} gemport {$gemport} user-vlan {$userVlan} vlan {$svlan} svlan {$svlan} vport {$vport}"
        ];
        return $this->executeMultiple($commands);
    }

    public function configurePppoe($slot, $port, $onuId, $username, $password, $vlan = null)
    {
        $commands = [
            "interface gpon-onu_{$slot}/{$port}:{$onuId}",
            "pppoe 1 nat enable user {$username} password {$password}",
            "pppoe 1 ip-host 1",
            "exit"
        ];
        return $this->executeMultiple($commands);
    }

    public function configureWifi($slot, $port, $onuId, $ssid, $wifiPassword)
    {
        $commands = [
            "interface gpon-onu_{$slot}/{$port}:{$onuId}",
            "wifi enable",
            "ssid {$ssid}",
            "wpa2 enable",
            "wpa2 password {$wifiPassword}",
            "exit"
        ];
        return $this->executeMultiple($commands);
    }

    public function rebootOnu($slot, $port, $onuId)
    {
        return $this->execute("reboot gpon-onu_{$slot}/{$port}:{$onuId}");
    }

    public function deleteOnu($slot, $port, $onuId)
    {
        $commands = [
            "interface gpon-onu_{$slot}/{$port}",
            "no onu {$onuId}",
            "exit"
        ];
        return $this->executeMultiple($commands);
    }

    public function getUnregisteredOnus()
    {
        $output = $this->execute('show gpon onu uncfg');
        return $this->parseUnregisteredOnus($output);
    }

    public function getOnuDetail($slot, $port, $onuId)
    {
        $output = $this->execute("show gpon onu detail-info gpon-onu_{$slot}/{$port}:{$onuId}");
        return $this->parseOnuDetail($output);
    }

    public function getOnuOptical($slot, $port, $onuId)
    {
        $output = $this->execute("show gpon remote-onu optical-info gpon-onu_{$slot}/{$port}:{$onuId}");
        return $this->parseOpticalInfo($output);
    }

    public function getPonPortStatus($slot, $port)
    {
        $output = $this->execute("show gpon onu state gpon-onu_{$slot}/{$port}");
        return $this->parsePonStatus($output);
    }

    // Parsing Methods
    private function parseUnregisteredOnus($output)
    {
        $onus = [];
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            if (preg_match('/gpon-onu_(\d+)\/(\d+):(\d+)\s+(\S+)\s+(\S+)/', $line, $matches)) {
                $onus[] = [
                    'slot' => $matches[1],
                    'port' => $matches[2],
                    'onu_id' => $matches[3],
                    'sn' => $matches[4],
                    'type' => $matches[5]
                ];
            }
        }
        
        return $onus;
    }

    private function parseOnuDetail($output)
    {
        $detail = [];
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $detail[trim($key)] = trim($value);
            }
        }
        
        return $detail;
    }

    private function parseOpticalInfo($output)
    {
        $optical = [];
        if (preg_match('/RX power:\s*([\d\.\-]+)/i', $output, $matches)) {
            $optical['rx_power'] = $matches[1];
        }
        if (preg_match('/TX power:\s*([\d\.\-]+)/i', $output, $matches)) {
            $optical['tx_power'] = $matches[1];
        }
        if (preg_match('/Distance:\s*([\d\.]+)/i', $output, $matches)) {
            $optical['distance'] = $matches[1];
        }
        if (preg_match('/Temperature:\s*([\d\.]+)/i', $output, $matches)) {
            $optical['temperature'] = $matches[1];
        }
        
        return $optical;
    }

    private function parsePonStatus($output)
    {
        $status = [
            'total' => 0,
            'online' => 0,
            'offline' => 0,
            'onus' => []
        ];
        
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (preg_match('/(\d+)\s+(\S+)\s+(\S+)\s+(\S+)/', $line, $matches)) {
                $status['onus'][] = [
                    'onu_id' => $matches[1],
                    'sn' => $matches[2],
                    'status' => $matches[3],
                    'auth' => $matches[4]
                ];
                $status['total']++;
                if (strtolower($matches[3]) == 'online') {
                    $status['online']++;
                } else {
                    $status['offline']++;
                }
            }
        }
        
        return $status;
    }

    // Private helper methods
    private function write($data)
    {
        fwrite($this->connection, $data . "\r\n");
    }

    private function readUntil($prompts)
    {
        if (!is_array($prompts)) {
            $prompts = [$prompts];
        }
        
        $output = '';
        while (!feof($this->connection)) {
            $char = fread($this->connection, 1);
            $output .= $char;
            
            foreach ($prompts as $prompt) {
                if (substr($output, -strlen($prompt)) === $prompt) {
                    return $output;
                }
            }
        }
        
        return $output;
    }

    private function logCommand($action, $command, $response, $status)
    {
        ProvisionLog::create([
            'olt_id' => $this->olt->id,
            'action' => $action,
            'command' => $command,
            'response' => $response,
            'status' => $status,
            'executed_at' => now()
        ]);
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
