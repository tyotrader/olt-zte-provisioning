<?php

namespace App\Controllers;

use App\Services\TopologyService;
use Illuminate\Http\Request;

class MapController extends Controller
{
    protected $topologyService;

    public function __construct()
    {
        $this->topologyService = new TopologyService();
    }

    public function index()
    {
        return view('map.index');
    }

    public function getData()
    {
        $data = $this->topologyService->getMapData();
        
        return response()->json($data);
    }

    public function getOltMarkers()
    {
        $olts = $this->topologyService->getOltMarkers();
        
        return response()->json($olts);
    }

    public function getOdpMarkers()
    {
        $odps = $this->topologyService->getOdpMarkers();
        
        return response()->json($odps);
    }

    public function getOnuMarkers()
    {
        $onus = $this->topologyService->getOnuMarkers();
        
        return response()->json($onus);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $results = $this->topologyService->searchNodes($query);
        
        return response()->json($results);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'type' => 'required|in:odp,onu'
        ]);

        $file = $request->file('file');
        $type = $request->input('type');
        
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        // Skip header
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 3) {
                $data[] = [
                    'name' => $row[0],
                    'latitude' => $row[1],
                    'longitude' => $row[2]
                ];
            }
        }
        
        fclose($handle);

        $results = $this->topologyService->importCoordinates($type, $data);
        
        return response()->json([
            'success' => true,
            'imported' => $results['success'],
            'failed' => $results['failed'],
            'errors' => $results['errors']
        ]);
    }

    public function export()
    {
        $data = $this->topologyService->getMapData();
        
        $csv = "Type,Name,Latitude,Longitude\n";
        
        foreach ($data['markers'] as $marker) {
            $csv .= "{$marker['type']},{$marker['name']},{$marker['lat']},{$marker['lng']}\n";
        }
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="topology_export.csv"');
    }
}
