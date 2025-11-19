<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class FileStorageController extends Controller
{
    /**
     * Serve files from public storage
     */
    public function serveFile($path)
    {
        if (! Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $fileContent = Storage::disk('public')->get($path);
        $filePath = Storage::disk('public')->path($path);
        $mimeType = mime_content_type($filePath);

        return response($fileContent, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline')
            ->header('Cache-Control', 'public, max-age=31536000');
    }
}
