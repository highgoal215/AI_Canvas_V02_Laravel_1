<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectHistory extends Model
{
    //
    protected $fillable = ['project_id', 'data_snpshot', 'comment'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // app/Http/Controllers/ProjectController.php
public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'type' => 'required|in:image,video',
    ]);

    $project = auth()->user()->projects()->create($request->only(['title', 'type']));

    return response()->json($project);
}

public function index()
{
    return auth()->user()->projects()->with('histories')->get();
}

public function export($id, Request $request)
{
    $project = auth()->user()->projects()->findOrFail($id);
    $snapshot = $request->input('snapshot');

    $url = (new ExportService())->exportImage($snapshot, $request->input('format', 'png'), $request->input('resolution', '1080p'), auth()->user());

    return response()->json(['url' => $url]);
}

}
