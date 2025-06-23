<?php

namespace App\Http\Controllers\Api\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\BackgroundRemoverService;
use Illuminate\Http\Request;

class BackgroundRemoverController extends Controller
{
    protected $backgroundRemoverService;

    public function __construct(BackgroundRemoverService $backgroundRemoverService)
    {
        $this->backgroundRemoverService = $backgroundRemoverService;
    }

    public function remove(Request $request)
    {
        $request->validate([
            'image_url' => 'required|url',
        ]);

        try {
            $resultUrl = $this->backgroundRemoverService->remove(
                $request->input('image_url')
            );
            return response()->json(['url' => $resultUrl]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
