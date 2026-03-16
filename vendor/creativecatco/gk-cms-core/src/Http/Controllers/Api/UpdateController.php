<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use CreativeCatCo\GkCmsCore\Services\UpdateService;

class UpdateController extends Controller
{
    /**
     * Run pre-flight checks before starting an update.
     */
    public function preflight(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $service = new UpdateService();
        $result = $service->preFlightChecks();

        return response()->json([
            'success' => true,
            'preflight' => $result,
            'channel' => $service->getChannel(),
        ]);
    }

    /**
     * Trigger the CMS update as a background process.
     */
    public function apply(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $service = new UpdateService();

        // Run pre-flight checks
        $preflight = $service->preFlightChecks();
        $criticalChecks = ['PHP Version', 'Vendor Directory'];

        // Add channel-specific critical checks
        if ($service->getChannel() === 'composer') {
            $criticalChecks[] = 'Composer';
        } else {
            $criticalChecks[] = 'File Download';
            $criticalChecks[] = 'Zip Extension';
        }

        $criticalFails = collect($preflight['checks'])
            ->filter(fn($c) => !$c['pass'] && in_array($c['label'], $criticalChecks))
            ->values();

        if ($criticalFails->isNotEmpty()) {
            $failMessages = $criticalFails->map(fn($c) => $c['message'])->implode('; ');
            return response()->json([
                'success' => false,
                'status' => 'preflight_failed',
                'error' => "Pre-flight checks failed: {$failMessages}",
                'preflight' => $preflight,
            ]);
        }

        // For release channel, the download URL must be provided
        $downloadUrl = $request->input('download_url');

        // Start the update
        $result = $service->startUpdate($downloadUrl);

        return response()->json($result);
    }

    /**
     * Poll the update progress by reading the log file.
     */
    public function status(Request $request): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $service = new UpdateService();
        $status = $service->getStatus();

        // If complete, also return the new version
        if ($status['status'] === 'complete') {
            $status['new_version'] = $service->getInstalledVersion();
        }

        return response()->json(array_merge(['success' => true], $status));
    }
}
