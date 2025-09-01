<?php

namespace App\Http\Controllers\v1\Upload;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Http\Resources\UserResource;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }
    public function documentUploads(GlobalRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $uploadedImage = $this->uploadService->processSingleImageUpload($validatedData);

            return Utility::outputData(
                true,
                'Image uploaded successfully',
                $uploadedImage,
                201
            );
        } catch (Throwable $e) {
            Log::error("Error uploading image: " . $e->getMessage());
            return Utility::outputData(
                false,
                "Unable to process request, Please try again later",
                [],
                500
            );
        } catch (\Exception $e) {
        }
    }


    public function updateProfileImage(GlobalRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $user = Auth::user();

        // Update the image
        $user->image = $validatedData['image'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile image updated successfully',
            'data' => new UserResource($user),
        ]);
    }


}
