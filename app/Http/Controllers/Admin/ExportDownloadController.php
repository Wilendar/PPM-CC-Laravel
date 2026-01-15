<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export Download Controller.
 *
 * Handles secure download of export files from local storage.
 * Used by Visual Description Editor bulk export jobs.
 *
 * @package App\Http\Controllers\Admin
 * @since ETAP_07f Faza 6.2 - Bulk Operations
 */
class ExportDownloadController extends Controller
{
    /**
     * Download an export file.
     *
     * @param Request $request
     * @param string $file Base64-encoded file path
     * @return BinaryFileResponse|StreamedResponse
     */
    public function download(Request $request, string $file)
    {
        // Decode file path
        $filePath = base64_decode($file);

        // Security: Only allow exports from specific directories
        $allowedPrefixes = [
            'exports/descriptions/',
            'exports/products/',
            'exports/templates/',
        ];

        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($filePath, $prefix)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            abort(403, 'Nieautoryzowany dostep do pliku');
        }

        // Check file exists
        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'Plik nie istnieje');
        }

        // Get file name for download
        $fileName = basename($filePath);

        // Return download response
        return Storage::disk('local')->download($filePath, $fileName, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Delete an export file.
     *
     * @param Request $request
     * @param string $file Base64-encoded file path
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, string $file)
    {
        $filePath = base64_decode($file);

        // Security check
        if (!str_starts_with($filePath, 'exports/')) {
            return response()->json(['error' => 'Nieautoryzowana operacja'], 403);
        }

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json(['error' => 'Plik nie istnieje'], 404);
        }

        Storage::disk('local')->delete($filePath);

        return response()->json(['success' => true, 'message' => 'Plik usunieto']);
    }
}
