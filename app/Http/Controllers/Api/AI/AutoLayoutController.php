<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\AutoLayoutService;
use Illuminate\Http\Request;

class AutoLayoutController extends Controller
{
    protected $autoLayoutService;

    public function __construct(AutoLayoutService $autoLayoutService)
    {
        $this->autoLayoutService = $autoLayoutService;
    }

    public function suggest(Request $request)
    {
        $request->validate([
            'contentType' => 'required|string|max:100',
            'contentDescription' => 'required|string|max:1000',
            'layoutStyle' => 'sometimes|string|max:100',
            'aspectRatio' => 'sometimes|string|in:1:1,16:9,9:16,4:3,3:4',
        ]);

        $layout = $this->autoLayoutService->suggestLayout(
            $request->input('contentType'),
            $request->input('contentDescription'),
            $request->input('layoutStyle'),
            $request->input('aspectRatio', '16:9')
        );

        return response()->json($layout);
    }
}
