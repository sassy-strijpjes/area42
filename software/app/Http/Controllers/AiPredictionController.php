<?php

namespace App\Http\Controllers;

use App\Services\AiPredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiPredictionController extends Controller
{
    public function trainAndPredict(Request $request, AiPredictionService $service): JsonResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'array', 'min:1'],
            'data.*.occupancy_rate' => ['required', 'numeric'],
            'data.*.week_start' => ['nullable', 'date_format:Y-m-d', Rule::requiredWithout('data.*.date')],
            'data.*.date' => ['nullable', 'date_format:Y-m-d', Rule::requiredWithout('data.*.week_start')],
            'days' => ['required', 'integer', 'min:1'],
        ]);

        $data = $validated['data'];
        $days = (int) $validated['days'];

        $predictions = $service->trainAndPredict($data, $days);

        return response()->json([
            'predictions' => $predictions,
        ]);
    }
}
