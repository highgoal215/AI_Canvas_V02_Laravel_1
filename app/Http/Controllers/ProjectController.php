<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectHistory;
use App\Services\Export\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    protected $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Get all projects for the authenticated user
     */
    public function index()
    {
        $projects = auth()->user()->projects()->with('histories')->orderBy('updated_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Create a new project
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|in:image,video',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $project = auth()->user()->projects()->create($request->only(['title', 'type']));

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => $project
        ], 201);
    }

    /**
     * Get a specific project with its history
     */
    public function show($id)
    {
        $project = auth()->user()->projects()->with('histories')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    /**
     * Update project title
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $project = auth()->user()->projects()->findOrFail($id);
        $project->update($request->only(['title']));

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => $project
        ]);
    }

    /**
     * Delete a project
     */
    public function destroy($id)
    {
        $project = auth()->user()->projects()->findOrFail($id);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully'
        ]);
    }

    /**
     * Save project state (create history entry)
     */
    public function saveState(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'data_snapshot' => 'required|string',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $project = auth()->user()->projects()->findOrFail($id);
        
        $history = $project->histories()->create([
            'data_snapshot' => $request->data_snapshot,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project state saved successfully',
            'data' => $history
        ], 201);
    }

    /**
     * Export project
     */
    public function export(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'data_snapshot' => 'required|string',
            'format' => 'required|string',
            'resolution' => 'required|string',
            'quality' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $project = auth()->user()->projects()->findOrFail($id);
        $user = auth()->user();
        $limits = $user->getExportLimits();

        // Check format restrictions
        if (!in_array($request->format, $limits['formats'])) {
            return response()->json([
                'success' => false,
                'message' => 'Export format not allowed for your subscription tier'
            ], 403);
        }

        // Check resolution restrictions
        $allowedResolutions = ['720p', '1080p', '4k'];
        $requestedResolution = $request->resolution;
        $maxResolutionIndex = array_search($limits['max_resolution'], $allowedResolutions);
        $requestedResolutionIndex = array_search($requestedResolution, $allowedResolutions);
        
        if ($requestedResolutionIndex > $maxResolutionIndex) {
            return response()->json([
                'success' => false,
                'message' => 'Resolution not allowed for your subscription tier'
            ], 403);
        }

        try {
            $exportUrl = $this->exportService->exportImage(
                $request->data_snapshot,
                $request->format,
                $request->resolution,
                $request->quality ?? 90,
                $user
            );

            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'data' => [
                    'url' => $exportUrl,
                    'format' => $request->format,
                    'resolution' => $request->resolution,
                    'watermark' => $limits['watermark']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get project history
     */
    public function history($id)
    {
        $project = auth()->user()->projects()->with('histories')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $project->histories
        ]);
    }

    /**
     * Restore project from history
     */
    public function restoreFromHistory($projectId, $historyId)
    {
        $project = auth()->user()->projects()->findOrFail($projectId);
        $history = $project->histories()->findOrFail($historyId);

        return response()->json([
            'success' => true,
            'message' => 'History restored successfully',
            'data' => [
                'data_snapshot' => $history->data_snapshot,
                'comment' => $history->comment,
                'created_at' => $history->created_at
            ]
        ]);
    }
}
