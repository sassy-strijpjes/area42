<?php

namespace App\Http\Controllers;

use App\Services\AiPredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiPredictionController extends Controller
{
    /**
     * POST /ai/train
     * Upload full historical CSV to preprocess and train the SARIMAX model.
     * Takes ~2-8 sec. Run offline or on a schedule.
     */
    public function train(Request $request, AiPredictionService $service): JsonResponse
    {
        $validated = $request->validate([
            'data' => ['required', 'array', 'min:1'],
            'data.*.occupancy_rate' => ['required', 'numeric'],
            'data.*.week_start' => ['nullable', 'date_format:Y-m-d', 'required_without:data.*.date'],
            'data.*.date' => ['nullable', 'date_format:Y-m-d', 'required_without:data.*.week_start'],
        ]);

        $result = $service->train($validated['data']);

        return response()->json($result);
    }

    /**
     * POST /ai/predict
     * Predict N days ahead from a given start date using the pre-trained model.
     * Fast — ~10 ms. No training data needed.
     *
     * Body: { "date": "2026-06-17", "days": 30, "granularity": "daily" }
     */
    public function predict(Request $request, AiPredictionService $service): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'days' => ['required', 'integer', 'min:1', 'max:730'],
            'granularity' => ['nullable', 'in:daily,weekly'],
        ]);

        $date = $validated['date'];
        $days = (int) $validated['days'];
        $granularity = $validated['granularity'] ?? 'daily';

        $predictions = $service->predict($date, $days, $granularity);

        return response()->json([
            'predictions' => $predictions,
        ]);
    }
}
