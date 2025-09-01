<?php

namespace App\Http\Controllers\v1\Banner;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalRequest;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BannerController extends Controller
{
    /**
     * Get list of supported banks
     */

    public function index(){

        $banners = Banner::all();
        return view('dashboard.banner.index', compact('banners'));
    }


    /**
     * Handle banner upload
     */

    /**
     * Handle banner upload
     */
    public function uploadBanner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please check your input and try again.');
        }

        try {
            $file = $request->file('banner_image');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('images', $filename, 'public');
            $imageUrl = url('storage/' . $path);
            // Save to database
            Banner::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'image_url' => $imageUrl,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'active', // Default to active
            ]);

            return redirect()->back()->with('success', 'Banner uploaded successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to upload banner. Please try again.');
        }
    }
    /**
     * Activate a banner
     */
    public function activate($id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $banner->update(['status' => 'active']);

            return redirect()->back()->with('success', 'Banner activated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to activate banner.');
        }
    }

    /**
     * Deactivate a banner
     */
    public function deactivate($id)
    {
        try {
            $banner = Banner::findOrFail($id);
            $banner->update(['status' => 'inactive']);

            return redirect()->back()->with('success', 'Banner deactivated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to deactivate banner.');
        }
    }

    /**
     * Delete a banner
     */
    public function delete($id)
    {
        try {
            $banner = Banner::findOrFail($id);

            if (Storage::disk('public')->exists($banner->file_path)) {
                Storage::disk('public')->delete($banner->file_path);
            }

            // Delete from database
            $banner->delete();

            return redirect()->back()->with('success', 'Banner deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete banner.');
        }
    }

    /**
     * Get active banners (API endpoint)
     */
    public function getActiveBanners()
    {
        $activeBanners = Banner::ActiveBanners();

        return response()->json([
            'success' => true,
            'data' => $activeBanners,
            'count' => $activeBanners->count()
        ]);
    }


}
