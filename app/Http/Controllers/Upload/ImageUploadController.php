<?php

namespace App\Http\Controllers\Upload;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class ImageUploadController extends Controller
{
    /**
     * Normalize an uploaded image to a web-friendly 8-bit JPEG and store in public disk.
     * Requires intervention/image package (composer require intervention/image)
     */
    public function uploadNormalize(Request $request)
    {
        $request->validate([
            'image' => 'required|file|image',
        ]);

        $file = $request->file('image');

        try {
            // Use Intervention Image statically to avoid depending on alias configuration
            $img = \Intervention\Image\ImageManagerStatic::make($file->getRealPath());

            // Fix orientation from EXIF if present
            try {
                $img->orientate();
            } catch (\Exception $e) {
                // ignore orientation errors
            }

            // Optional: resize to a max dimension to limit file size
            $img->resize(1600, 1600, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            // Re-encode to JPEG (8-bit per channel) and strip profiles
            $encoded = $img->encode('jpg', 90);

            $filename = 'uploads/' . uniqid('', true) . '.jpg';
            Storage::disk('public')->put($filename, (string) $encoded);

            $url = asset('storage/' . $filename);
            return response()->json(['path' => $filename, 'url' => $url], 201);
        } catch (\Throwable $e) {
            Log::error('Image normalization failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process image'], 500);
        }
    }
}
