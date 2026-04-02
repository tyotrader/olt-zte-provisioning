<?php

namespace App\Controllers;

use App\Models\Olt;
use App\Models\OnuDetection;
use App\Services\DetectionService;
use Illuminate\Http\Request;

class DetectionController extends Controller
{
    public function index()
    {
        $detections = OnuDetection::with('olt')
            ->where('status', 'detected')
            ->where('is_ignored', false)
            ->orderBy('discovery_time', 'desc')
            ->paginate(50);
        
        $olts = Olt::active()->get();
        $stats = (new DetectionService(Olt::first() ?? new Olt))->getStats();

        return view('detection.index', compact('detections', 'olts', 'stats'));
    }

    public function scan(Request $request)
    {
        $oltId = $request->input('olt_id');
        
        if ($oltId) {
            $olt = Olt::findOrFail($oltId);
            $service = new DetectionService($olt);
            $result = $service->scanUnregisteredOnus();
        } else {
            $service = new DetectionService(Olt::first() ?? new Olt);
            $result = $service->scanAllOlts();
        }

        return response()->json($result);
    }

    public function getPending()
    {
        $service = new DetectionService(Olt::first() ?? new Olt);
        $detections = $service->getPendingDetections();

        return response()->json([
            'count' => $detections->count(),
            'onus' => $detections
        ]);
    }

    public function ignore($id)
    {
        $service = new DetectionService(Olt::first() ?? new Olt);
        $detection = $service->ignoreOnu($id);

        return response()->json([
            'success' => true,
            'message' => 'ONU ignored',
            'detection' => $detection
        ]);
    }

    public function unignore($id)
    {
        $service = new DetectionService(Olt::first() ?? new Olt);
        $detection = $service->unignoreOnu($id);

        return response()->json([
            'success' => true,
            'message' => 'ONU unignored',
            'detection' => $detection
        ]);
    }

    public function destroy($id)
    {
        $service = new DetectionService(Olt::first() ?? new Olt);
        $service->deleteDetection($id);

        return response()->json([
            'success' => true,
            'message' => 'Detection record deleted'
        ]);
    }

    public function getStats()
    {
        $service = new DetectionService(Olt::first() ?? new Olt);
        $stats = $service->getStats();

        return response()->json($stats);
    }

    public function autoScan()
    {
        // This method is called by the scheduler
        $service = new DetectionService(Olt::first() ?? new Olt);
        $results = $service->scanAllOlts();

        // Log the scan
        \Log::info('Auto scan completed', ['results' => $results]);

        return $results;
    }
}
