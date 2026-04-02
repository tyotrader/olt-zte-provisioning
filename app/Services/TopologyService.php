<?php

namespace App\Services;

use App\Models\Olt;
use App\Models\Odp;
use App\Models\Onu;

class TopologyService
{
    public function getMapData()
    {
        $olts = Olt::with(['ponPorts.onus', 'odps.onus'])
            ->active()
            ->get();
        
        $markers = [];
        $lines = [];
        
        foreach ($olts as $olt) {
            // Add OLT marker
            $markers[] = [
                'id' => "olt_{$olt->id}",
                'type' => 'olt',
                'name' => $olt->olt_name,
                'lat' => $olt->latitude,
                'lng' => $olt->longitude,
                'data' => [
                    'ip' => $olt->ip_address,
                    'model' => $olt->olt_model,
                    'total_pon' => $olt->ponPorts->count(),
                    'total_onu' => $olt->onus->count(),
                    'online_onu' => $olt->onus->where('status', 'online')->count()
                ]
            ];
            
            // Add ODP markers
            foreach ($olt->odps as $odp) {
                $markers[] = [
                    'id' => "odp_{$odp->id}",
                    'type' => 'odp',
                    'name' => $odp->odp_name,
                    'lat' => $odp->latitude,
                    'lng' => $odp->longitude,
                    'data' => [
                        'olt_name' => $olt->olt_name,
                        'pon_port' => $odp->ponPort->port ?? null,
                        'connected_onu' => $odp->onus->count(),
                        'total_ports' => $odp->total_ports,
                        'available_ports' => $odp->total_ports - $odp->used_ports
                    ]
                ];
                
                // Draw line from OLT to ODP
                if ($olt->latitude && $olt->longitude && $odp->latitude && $odp->longitude) {
                    $lines[] = [
                        'from' => "olt_{$olt->id}",
                        'to' => "odp_{$odp->id}",
                        'type' => 'olt-odp',
                        'coords' => [
                            [$olt->latitude, $olt->longitude],
                            [$odp->latitude, $odp->longitude]
                        ]
                    ];
                }
                
                // Add ONU markers and lines
                foreach ($odp->onus as $onu) {
                    if ($onu->latitude && $onu->longitude) {
                        $markers[] = [
                            'id' => "onu_{$onu->id}",
                            'type' => 'onu',
                            'name' => $onu->customer_name,
                            'lat' => $onu->latitude,
                            'lng' => $onu->longitude,
                            'status' => $onu->status,
                            'signal_status' => $onu->signal_status,
                            'data' => [
                                'sn' => $onu->onu_sn,
                                'onu_id' => $onu->onu_id,
                                'olt_name' => $olt->olt_name,
                                'pon_port' => $onu->pon_port,
                                'rx_power' => $onu->rx_power,
                                'tx_power' => $onu->tx_power,
                                'distance' => $onu->distance,
                                'status' => $onu->status
                            ]
                        ];
                        
                        // Draw line from ODP to ONU
                        $lines[] = [
                            'from' => "odp_{$odp->id}",
                            'to' => "onu_{$onu->id}",
                            'type' => 'odp-onu',
                            'status' => $onu->status,
                            'coords' => [
                                [$odp->latitude, $odp->longitude],
                                [$onu->latitude, $onu->longitude]
                            ]
                        ];
                    }
                }
            }
            
            // Add ONUs not connected to any ODP
            $unmappedOnus = $olt->onus->whereNull('odp_id');
            foreach ($unmappedOnus as $onu) {
                if ($onu->latitude && $onu->longitude) {
                    $markers[] = [
                        'id' => "onu_{$onu->id}",
                        'type' => 'onu',
                        'name' => $onu->customer_name,
                        'lat' => $onu->latitude,
                        'lng' => $onu->longitude,
                        'status' => $onu->status,
                        'signal_status' => $onu->signal_status,
                        'data' => [
                            'sn' => $onu->onu_sn,
                            'onu_id' => $onu->onu_id,
                            'olt_name' => $olt->olt_name,
                            'pon_port' => $onu->pon_port,
                            'rx_power' => $onu->rx_power,
                            'tx_power' => $onu->tx_power,
                            'status' => $onu->status
                        ]
                    ];
                    
                    // Direct line from OLT to unmapped ONU
                    $lines[] = [
                        'from' => "olt_{$olt->id}",
                        'to' => "onu_{$onu->id}",
                        'type' => 'olt-onu',
                        'status' => $onu->status,
                        'dashed' => true,
                        'coords' => [
                            [$olt->latitude, $olt->longitude],
                            [$onu->latitude, $onu->longitude]
                        ]
                    ];
                }
            }
        }
        
        return [
            'markers' => $markers,
            'lines' => $lines
        ];
    }

    public function searchNodes($query)
    {
        $results = [];
        
        // Search OLTs
        $olts = Olt::where('olt_name', 'like', "%{$query}%")
            ->orWhere('ip_address', 'like', "%{$query}%")
            ->get();
        
        foreach ($olts as $olt) {
            $results[] = [
                'type' => 'olt',
                'id' => $olt->id,
                'name' => $olt->olt_name,
                'lat' => $olt->latitude,
                'lng' => $olt->longitude
            ];
        }
        
        // Search ODPs
        $odps = Odp::where('odp_name', 'like', "%{$query}%")
            ->orWhere('location', 'like', "%{$query}%")
            ->get();
        
        foreach ($odps as $odp) {
            $results[] = [
                'type' => 'odp',
                'id' => $odp->id,
                'name' => $odp->odp_name,
                'lat' => $odp->latitude,
                'lng' => $odp->longitude
            ];
        }
        
        // Search ONUs
        $onus = Onu::where('customer_name', 'like', "%{$query}%")
            ->orWhere('onu_sn', 'like', "%{$query}%")
            ->orWhere('customer_id', 'like', "%{$query}%")
            ->get();
        
        foreach ($onus as $onu) {
            $results[] = [
                'type' => 'onu',
                'id' => $onu->id,
                'name' => $onu->customer_name,
                'sn' => $onu->onu_sn,
                'lat' => $onu->latitude,
                'lng' => $onu->longitude
            ];
        }
        
        return $results;
    }

    public function importCoordinates($type, $data)
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($data as $row) {
            try {
                if ($type === 'odp') {
                    $odp = Odp::where('odp_name', $row['name'])->first();
                    if ($odp) {
                        $odp->update([
                            'latitude' => $row['latitude'],
                            'longitude' => $row['longitude']
                        ]);
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "ODP {$row['name']} not found";
                    }
                } elseif ($type === 'onu') {
                    $onu = Onu::where('onu_sn', $row['name'])
                        ->orWhere('customer_name', $row['name'])
                        ->first();
                    if ($onu) {
                        $onu->update([
                            'latitude' => $row['latitude'],
                            'longitude' => $row['longitude']
                        ]);
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "ONU {$row['name']} not found";
                    }
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
            }
        }
        
        return $results;
    }

    public function getOltMarkers()
    {
        return Olt::select('id', 'olt_name', 'ip_address', 'latitude', 'longitude', 'status')
            ->active()
            ->get();
    }

    public function getOdpMarkers()
    {
        return Odp::with('olt:id,olt_name')
            ->select('id', 'odp_name', 'olt_id', 'latitude', 'longitude', 'total_ports', 'used_ports')
            ->get();
    }

    public function getOnuMarkers()
    {
        return Onu::with('olt:id,olt_name')
            ->select('id', 'onu_sn', 'customer_name', 'olt_id', 'latitude', 'longitude', 'status', 'rx_power')
            ->get();
    }
}
