<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MediaLibrayController extends Controller
{
    /**
     * Get all media items
     */
    public function index(Request $request)
    {
        $query = MediaLibrary::query();
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        
        $media = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $media
        ]);
    }

    /**
     * Upload new media
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,svg,mp4,avi,mov,wmv,webm|max:10240', // 10MB max
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('media-library', $fileName, 'public');
        
        $media = MediaLibrary::create([
            'name' => $request->name,
            'description' => $request->description,
            'file_path' => $path,
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'type' => $this->getMediaType($file->getMimeType()),
            'category' => $request->category,
            'tags' => $request->tags,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully',
            'data' => $media
        ], 201);
    }

    /**
     * Get a specific media item
     */
    public function show($id)
    {
        $media = MediaLibrary::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $media
        ]);
    }

    /**
     * Update media metadata
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $media = MediaLibrary::findOrFail($id);
        $media->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully',
            'data' => $media
        ]);
    }

    /**
     * Delete media
     */
    public function destroy($id)
    {
        $media = MediaLibrary::findOrFail($id);
        
        // Delete file from storage
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }
        
        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully'
        ]);
    }

    /**
     * Toggle media active status
     */
    public function toggleActive($id)
    {
        $media = MediaLibrary::findOrFail($id);
        $media->update(['is_active' => !$media->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Media status updated successfully',
            'data' => $media
        ]);
    }

    /**
     * Get media categories
     */
    public function categories()
    {
        $categories = MediaLibrary::distinct()->pluck('category')->filter()->values();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get media types
     */
    public function types()
    {
        $types = MediaLibrary::distinct()->pluck('type')->filter()->values();
        
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    private function getMediaType($mimeType)
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'video';
        } else {
            return 'other';
        }
    }
}
