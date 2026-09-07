<?php

use App\Services\AiPredictionService;
use App\Http\Middleware\Auth;
use App\Http\Middleware\PermissionMiddleware;

afterEach(function (): void {
    Mockery::close();
});

test('predict rejects an invalid forecast horizon before invoking Python', function () {
    $this->withoutMiddleware([Auth::class, PermissionMiddleware::class]);

    $this->postJson('/ai/predict', [
        'date' => '2026-09-07',
        'days' => 0,
        'granularity' => 'daily',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('days');
});

test('predict returns the documented response contract', function () {
    $this->withoutMiddleware([Auth::class, PermissionMiddleware::class]);

    $service = Mockery::mock(AiPredictionService::class);
    $service->shouldReceive('predict')
        ->once()
        ->with('2026-09-07', 2, 'daily')
        ->andReturn([
            [
                'date' => '2026-09-07',
                'percentage_point' => 72.5,
                'lower_bound' => 65.0,
                'upper_bound' => 80.0,
                'crowd_level' => 'normaal',
            ],
        ]);
    $this->app->instance(AiPredictionService::class, $service);

    $this->postJson('/ai/predict', [
        'date' => '2026-09-07',
        'days' => 2,
        'granularity' => 'daily',
    ])->assertOk()
        ->assertExactJson([
            'predictions' => [[
                'date' => '2026-09-07',
                'percentage_point' => 72.5,
                'lower_bound' => 65.0,
                'upper_bound' => 80.0,
                'crowd_level' => 'normaal',
            ]],
        ]);
});

test('training rejects a mixed daily and weekly payload', function () {
    $this->withoutMiddleware([Auth::class, PermissionMiddleware::class]);

    $this->postJson('/ai/train', [
        'data' => [
            ['date' => '2026-09-07', 'occupancy_rate' => 64.0],
            ['week_start' => '2026-09-07', 'occupancy_rate' => 68.0],
        ],
    ])->assertUnprocessable()
        ->assertExactJson([
            'message' => 'Training data cannot mix weekly (week_start) and daily (date or stay_date) rows.',
        ]);
});

test('predict reports a missing model as a conflict', function () {
    $this->withoutMiddleware([Auth::class, PermissionMiddleware::class]);

    $service = Mockery::mock(AiPredictionService::class);
    $service->shouldReceive('predict')
        ->once()
        ->with('2026-09-07', 2, 'weekly')
        ->andThrow(new RuntimeException('No trained weekly model is available. Train a weekly model before requesting a forecast.'));
    $this->app->instance(AiPredictionService::class, $service);

    $this->postJson('/ai/predict', [
        'date' => '2026-09-07',
        'days' => 2,
        'granularity' => 'weekly',
    ])->assertConflict()
        ->assertExactJson([
            'message' => 'No trained weekly model is available. Train a weekly model before requesting a forecast.',
        ]);
});

test('AI endpoints reject an unauthenticated API request', function () {
    $this->postJson('/ai/predict', [
        'date' => '2026-09-07',
        'days' => 2,
        'granularity' => 'daily',
    ])->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});
