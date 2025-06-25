<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    /**
     * Get all templates
     */
    public function index()
    {
        $templates = Template::orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Create a new template
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:image,video',
            'template_data' => 'required|string',
            'thumbnail_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $template = Template::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'data' => $template
        ], 201);
    }

    /**
     * Get a specific template
     */
    public function show($id)
    {
        $template = Template::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $template
        ]);
    }

    /**
     * Update a template
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:image,video',
            'template_data' => 'sometimes|required|string',
            'thumbnail_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $template = Template::findOrFail($id);
        $template->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => $template
        ]);
    }

    /**
     * Delete a template
     */
    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }

    /**
     * Toggle template active status
     */
    public function toggleActive($id)
    {
        $template = Template::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Template status updated successfully',
            'data' => $template
        ]);
    }
}
