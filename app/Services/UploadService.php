<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{

    public function processSingleImageUpload($validatedData): array
    {
        $image = $validatedData['image'] ?? null;

        if (!$image || !$image->isValid()) {
            throw new Exception('Invalid image provided');
        }

        $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

        // This will store the file in storage/app/public/kyc_images
        $path = $image->storeAs('images', $filename, 'public');

        if (!$path) {
            throw new Exception('Failed to store image');
        }

        // This will return a public URL like /storage/kyc_images/filename.jpg
        $imageUrl = Storage::url($path);

        return [
            'url' => url( $imageUrl),
        ];
    }

}
