<?php

namespace App\Services;

use App\Models\Olt;
use App\Models\Onu;
use App\Models\PonPort;
use App\Models\ProvisionLog;
use Illuminate\Support\Facades\Log;

class ProvisioningService
{
    private $telnetService;
    private $olt;

    public function __construct(Olt $olt)
    {
        $this->olt = $olt;
        $this->telnetService = new TelnetService($olt);
    }

    public function provisionOnu(array $data)
    {
        try {
            // Validate data
            $this->validateProvisioningData($data);
            
            // Connect to OLT
            if (!$this->telnetService->connect()) {
                throw new \Exception('Failed to connect to OLT');
            }
            
            $results = [];
            
            // Step 1: Register ONU
            $results[] = $this->registerOnu($data);
            
            // Step 2: Configure TCONT
            if (!empty($data['tcont_id']) && !empty($data['bandwidth_profile'])) {
                $results[] = $this->configureTcont($data);
            }
            
            // Step 3: Configure GEM Port
            if (!empty($data['gemport_id'])) {
                $results[] = $this->configureGemport($data);
            }
            
            // Step 4: Configure Service Port
            if (!empty($data['service_port_id'])) {
                $results[] = $this->configureServicePort($data);
            }
            
            // Step 5: Configure PPPoE if needed
            if ($data['wan_mode'] === 'pppoe' && !empty($data['pppoe_username'])) {
                $results[] = $this->configurePppoe($data);
            }
            
            // Step 6: Configure WiFi
            if (!empty($data['wifi_ssid'])) {
                $results[] = $this->configureWifi($data);
            }
            
            // Save ONU to database
            $onu = $this->saveOnuToDatabase($data);
            
            // Update provision log
            $this->logProvisioning($data, $results, 'success', $onu->id);
            
            return [
                'success' => true,
                'onu_id' => $onu->id,
                'results' => $results
            ];
            
        } catch (\Exception $e) {
            $this->logProvisioning($data, [], 'failed', null, $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function autoProvisionOnu($slot, $port, $onuSn, $customerName, $options = [])
    {
        try {
            // Get next available ONU ID
            $ponPort = PonPort::where('olt_id', $this->olt->id)
                ->where('slot', $slot)
                ->where('port', $port)
                ->first();
            
            if (!$ponPort) {
                throw new \Exception('PON Port not found');
            }
            
            $onuId = $ponPort->getAvailableOnuId();
            if (!$onuId) {
                throw new \Exception('No available ONU ID on this PON port');
            }
            
            // Get default profiles
            $defaults = $this->getDefaultProfiles();
            
            $data = array_merge([
                'olt_id' => $this->olt->id,
                'pon_port_id' => $ponPort->id,
                'slot' => $slot,
                'pon_port' => $port,
                'onu_id' => $onuId,
                'onu_sn' => $onuSn,
                'onu_type' => $options['onu_type'] ?? 'F601',
                'customer_name' => $customerName,
                'tcont_id' => $defaults['tcont_id'] ?? 1,
                'bandwidth_profile' => $defaults['bandwidth_profile'] ?? 'DATA',
                'gemport_id' => $defaults['gemport_id'] ?? 1,
                'service_port_id' => $this->getNextServicePortId(),
                'vport' => 1,
                'user_vlan' => $options['user_vlan'] ?? 100,
                'c_vid' => $options['c_vid'] ?? 100,
                'wan_mode' => $options['wan_mode'] ?? 'pppoe',
                'pppoe_username' => $options['pppoe_username'] ?? '',
                'pppoe_password' => $options['pppoe_password'] ?? '',
                'wifi_ssid' => $options['wifi_ssid'] ?? '',
                'wifi_password' => $options['wifi_password'] ?? '',
            ], $options);
            
            return $this->provisionOnu($data);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function registerOnu($data)
    {
        $commands = [
            "interface gpon-onu_{$data['slot']}/{$data['pon_port']}",
            "onu {$data['onu_id']} type {$data['onu_type']} sn {$data['onu_sn']}",
            "exit"
        ];
        
        return [
            'step' => 'register_onu',
            'result' => $this->executeCommands($commands)
        ];
    }

    private function configureTcont($data)
    {
        $commands = [
            "interface gpon-onu_{$data['slot']}/{$data['pon_port']}:{$data['onu_id']}",
            "tcont {$data['tcont_id']} profile {$data['bandwidth_profile']}",
            "exit"
        ];
        
        return [
            'step' => 'configure_tcont',
            'result' => $this->executeCommands($commands)
        ];
    }

    private function configureGemport($data)
    {
        $tcontId = $data['tcont_id'] ?? 1;
        $commands = [
            "interface gpon-onu_{$data['slot']}/{$data['pon_port']}:{$data['onu_id']}",
            "gemport {$data['gemport_id']} name Gem{$data['gemport_id']} tcont {$tcontId}",
            "exit"
        ];
        
        return [
            'step' => 'configure_gemport',
            'result' => $this->executeCommands($commands)
        ];
    }

    private function configureServicePort($data)
    {
        $svlan = $data['c_vid'] ?? $data['user_vlan'];
        $commands = [
            "service-port {$data['service_port_id']} gpon-onu_{$data['slot']}/{$data['pon_port']}:{$data['onu_id']} " .
            "gemport {$data['gemport_id']} user-vlan {$data['user_vlan']} vlan {$svlan} svlan {$svlan} vport {$data['vport']}"
        ];
        
        return [
            'step' => 'configure_service_port',
            'result' => $this->executeCommands($commands)
        ];
    }

    private function configurePppoe($data)
    {
        $commands = [
            "interface gpon-onu_{$data['slot']}/{$data['pon_port']}:{$data['onu_id']}",
            "pppoe 1 nat enable user {$data['pppoe_username']} password {$data['pppoe_password']}",
            "pppoe 1 ip-host 1 respond-ping enable",
            "exit"
        ];
        
        return [
            'step' => 'configure_pppoe',
            'result' => $this->executeCommands($commands)
        ];
    }

    private function configureWifi($data)
    {
        $commands = [
            "interface gpon-onu_{$data['slot']}/{$data['pon_port']}:{$data['onu_id']}",
            "wifi enable",
            "ssid {$data['wifi_ssid']}",
            "wpa2 enable",
            "wpa2 password {$data['wifi_password']}",
            "exit"
        ];
        
        return [
            'step' => 'configure_wifi',
            'result' => $this->executeCommands($commands)
        ];
    }

    public function rebootOnu($slot, $port, $onuId)
    {
        try {
            if (!$this->telnetService->connect()) {
                throw new \Exception('Failed to connect to OLT');
            }
            
            $result = $this->telnetService->execute("reboot gpon-onu_{$slot}/{$port}:{$onuId}");
            
            $this->logAction('reboot_onu', "reboot gpon-onu_{$slot}/{$port}:{$onuId}", $result, 'success');
            
            return ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deleteOnu($slot, $port, $onuId)
    {
        try {
            if (!$this->telnetService->connect()) {
                throw new \Exception('Failed to connect to OLT');
            }
            
            $commands = [
                "interface gpon-onu_{$slot}/{$port}",
                "no onu {$onuId}",
                "exit"
            ];
            
            $result = $this->executeCommands($commands);
            
            $this->logAction('delete_onu', implode('; ', $commands), json_encode($result), 'success');
            
            return ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function factoryResetOnu($slot, $port, $onuId)
    {
        try {
            if (!$this->telnetService->connect()) {
                throw new \Exception('Failed to connect to OLT');
            }
            
            $result = $this->telnetService->execute("restore factory gpon-onu_{$slot}/{$port}:{$onuId}");
            
            $this->logAction('factory_reset', "restore factory gpon-onu_{$slot}/{$port}:{$onuId}", $result, 'success');
            
            return ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function executeCommands(array $commands)
    {
        $results = [];
        foreach ($commands as $command) {
            $output = $this->telnetService->execute($command);
            $results[] = [
                'command' => $command,
                'output' => $output,
                'success' => strpos(strtolower($output), 'error') === false
            ];
        }
        return $results;
    }

    private function validateProvisioningData($data)
    {
        $required = ['slot', 'pon_port', 'onu_id', 'onu_sn', 'onu_type', 'customer_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Required field missing: {$field}");
            }
        }
        
        // Check for duplicate ONU
        $existing = Onu::where('olt_id', $this->olt->id)
            ->where('slot', $data['slot'])
            ->where('pon_port', $data['pon_port'])
            ->where('onu_id', $data['onu_id'])
            ->first();
        
        if ($existing) {
            throw new \Exception("ONU ID {$data['onu_id']} already exists on PON port {$data['slot']}/{$data['pon_port']}");
        }
        
        // Check for duplicate SN
        $existingSn = Onu::where('onu_sn', $data['onu_sn'])->first();
        if ($existingSn) {
            throw new \Exception("ONU with SN {$data['onu_sn']} already exists");
        }
    }

    private function saveOnuToDatabase($data)
    {
        return Onu::create([
            'olt_id' => $data['olt_id'],
            'pon_port_id' => $data['pon_port_id'],
            'onu_id' => $data['onu_id'],
            'onu_sn' => $data['onu_sn'],
            'onu_type' => $data['onu_type'],
            'customer_name' => $data['customer_name'],
            'customer_id' => $data['customer_id'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'slot' => $data['slot'],
            'pon_port' => $data['pon_port'],
            'tcont_profile' => $data['bandwidth_profile'] ?? null,
            'gemport_template' => $data['gemport_id'] ?? null,
            'vlan_profile' => $data['user_vlan'] ?? null,
            'service_port_template' => $data['service_port_id'] ?? null,
            'wan_mode' => $data['wan_mode'] ?? 'pppoe',
            'pppoe_username' => $data['pppoe_username'] ?? null,
            'pppoe_password' => $data['pppoe_password'] ? encrypt($data['pppoe_password']) : null,
            'wifi_ssid' => $data['wifi_ssid'] ?? null,
            'wifi_password' => $data['wifi_password'] ? encrypt($data['wifi_password']) : null,
            'wifi_enabled' => !empty($data['wifi_ssid']),
            'status' => 'offline',
            'registered_at' => now(),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_active' => true
        ]);
    }

    private function getDefaultProfiles()
    {
        // Fetch from database or return defaults
        return [
            'tcont_id' => 1,
            'bandwidth_profile' => 'DATA',
            'gemport_id' => 1
        ];
    }

    private function getNextServicePortId()
    {
        $lastServicePort = Onu::where('olt_id', $this->olt->id)->max('service_port_template');
        return ($lastServicePort ?? 0) + 1;
    }

    private function logProvisioning($data, $results, $status, $onuId = null, $error = null)
    {
        ProvisionLog::create([
            'olt_id' => $this->olt->id,
            'onu_id' => $onuId,
            'action' => 'provision_onu',
            'command' => json_encode($data),
            'response' => json_encode($results),
            'status' => $status,
            'error_message' => $error,
            'user_id' => auth()->id() ?? null,
            'executed_at' => now()
        ]);
    }

    private function logAction($action, $command, $response, $status)
    {
        ProvisionLog::create([
            'olt_id' => $this->olt->id,
            'action' => $action,
            'command' => $command,
            'response' => is_string($response) ? $response : json_encode($response),
            'status' => $status,
            'user_id' => auth()->id() ?? null,
            'executed_at' => now()
        ]);
    }
}
