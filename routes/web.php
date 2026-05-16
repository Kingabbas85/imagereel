<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

// Helper: find a file across common storage locations
function findStorageFile(string $path): ?string {
    $tries = [
        \Storage::disk('local')->path($path),
        \Storage::disk('local')->path('private/' . $path),
        \Storage::disk('public')->path($path),
        storage_path('app/' . $path),
        storage_path('app/private/' . $path),
        storage_path('app/public/' . $path),
    ];
    foreach ($tries as $t) {
        if (file_exists($t) && filesize($t) > 0) return $t;
    }
    return null;
}

// Serve image files for cropper preview (auth protected)
Route::get('/admin/preview/image', function () {
    $path = request('path');
    if (!$path) abort(404);
    $full = findStorageFile($path);
    if (!$full) abort(404);
    return response()->file($full);
})->middleware(['web', 'auth'])->name('admin.preview.image');

// Save a base64-encoded cropped image to permanent storage
Route::post('/admin/save-cropped-image', function () {
    $base64  = request('image');   // data:image/jpeg;base64,...
    $targetW = max(1, (int) request('target_w', 576));
    $targetH = max(1, (int) request('target_h', 1024));

    if (!$base64) return response()->json(['error' => 'No image data'], 400);

    $raw = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
    if (!$raw) return response()->json(['error' => 'Invalid base64'], 400);

    $filename = 'cropped_' . uniqid() . '.jpg';
    $path     = 'images/' . $filename;

    \Storage::disk('local')->put($path, $raw);

    if (!\Storage::disk('local')->exists($path)) {
        return response()->json(['error' => 'File save failed'], 500);
    }

    return response()->json(['success' => true, 'path' => $path]);
})->middleware(['web', 'auth']);

// Crop and save an image (auth protected)
Route::post('/admin/crop-image', function () {
    $path    = request('path');
    $crop    = request('crop');   // {x, y, width, height}
    $targetW = max(1, (int) request('target_w', 576));
    $targetH = max(1, (int) request('target_h', 1024));

    if (!$path || !$crop) return response()->json(['error' => 'Missing params'], 400);

    $fullPath = findStorageFile($path);
    if (!$fullPath) return response()->json(['error' => 'File not found: ' . $path], 404);

    $mime = mime_content_type($fullPath);

    $src = match(true) {
        str_contains($mime, 'jpeg') => imagecreatefromjpeg($fullPath),
        str_contains($mime, 'png')  => imagecreatefrompng($fullPath),
        str_contains($mime, 'webp') => imagecreatefromwebp($fullPath),
        default                     => null,
    };
    if (!$src) return response()->json(['error' => 'Unsupported image type: ' . $mime], 400);

    $dst = imagecreatetruecolor($targetW, $targetH);

    // Preserve transparency for PNG
    if (str_contains($mime, 'png')) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    } else {
        // White background for JPEG
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    }

    imagecopyresampled(
        $dst, $src,
        0, 0,
        (int)($crop['x'] ?? 0),
        (int)($crop['y'] ?? 0),
        $targetW, $targetH,
        max(1, (int)($crop['width']  ?? imagesx($src))),
        max(1, (int)($crop['height'] ?? imagesy($src)))
    );

    $saved = match(true) {
        str_contains($mime, 'png')  => imagepng($dst,  $fullPath, 8),
        str_contains($mime, 'webp') => imagewebp($dst, $fullPath, 90),
        default                     => imagejpeg($dst, $fullPath, 92),
    };

    imagedestroy($src);
    imagedestroy($dst);

    if (!$saved) return response()->json(['error' => 'Could not save cropped image'], 500);

    return response()->json(['success' => true]);
})->middleware(['web', 'auth']);

// Serve audio files for waveform preview (auth protected)
Route::get('/admin/preview/audio', function () {
    $path = request('path');
    if (!$path) abort(404);
    $full = findStorageFile($path);
    if (!$full) { \Log::warning('Audio preview not found: ' . $path); abort(404); }
    return response()->file($full);
})->middleware(['web', 'auth'])->name('admin.preview.audio');
