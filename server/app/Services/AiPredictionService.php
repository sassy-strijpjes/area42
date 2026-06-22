<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AiPredictionService
{
    /**
     * Train the model: accept a full CSV, preprocess, train SARIMAX, save.
     * Called offline or via POST /ai/train. Takes ~2-8 sec.
     */
    public function train(array $data): array
    {
        $aiRoot = $this->aiRoot();
        $tmpDir = storage_path('app/ai');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $isWeekly = $this->isWeeklyData($data);
        $granularity = $isWeekly ? 'weekly' : 'daily';
        $dateCol = $isWeekly ? 'week_start' : 'stay_date';

        $csvPath = $tmpDir . DIRECTORY_SEPARATOR . 'ai_training_' . uniqid() . '.csv';
        $this->writeCsv($data, $csvPath, $dateCol);

        $this->runPython($aiRoot, [
            'scripts/train_model.py',
            '--granularity', $granularity,
            ($isWeekly ? '--weekly-input' : '--daily-input'), $csvPath,
        ]);

        @unlink($csvPath);

        $modelName = $isWeekly ? 'weekly' : 'daily';
        return [
            'status' => 'trained',
            'granularity' => $granularity,
            'model' => "Models/occupancy_{$modelName}.pkl",
        ];
    }

    /**
     * Predict: use the pre-trained SARIMAX model.
     * Fast — loads model + generates forecast in ~10 ms.
     * POST /ai/predict  { "date": "2026-06-17", "days": 30 }
     */
    public function predict(string $startDate, int $days, string $granularity = 'daily'): array
    {
        $aiRoot = $this->aiRoot();
        $outputFile = 'future_predictions_' . $granularity . '.csv';

        $this->runPython($aiRoot, [
            'scripts/predict_occupancy.py',
            '--granularity', $granularity,
            '--date', $startDate,
            '--days', (string) $days,
        ]);

        return $this->loadPredictionsCsv(
            $aiRoot . '/Reports/' . $outputFile,
            $granularity === 'weekly'
        );
    }

    // ── Helpers ────────────────────────────────────────────────────

    protected function aiRoot(): string
    {
        $root = realpath(base_path('../ai')) ?: realpath(base_path('ai'));
        if ($root === false || !is_dir($root)) {
            throw new \RuntimeException(
                'AI folder not found. Expected: ' . base_path('../ai')
            );
        }
        return $root;
    }

    protected function isWeeklyData(array $data): bool
    {
        foreach ($data as $item) {
            if (isset($item['week_start'])) return true;
            if (isset($item['date']) || isset($item['stay_date'])) return false;
        }
        return false;
    }

    protected function writeCsv(array $data, string $path, string $dateCol): void
    {
        $rows = [];
        foreach ($data as $i => $item) {
            $dateValue = $item['week_start'] ?? $item['date'] ?? $item['stay_date'] ?? null;
            $occValue = $item['occupancy_rate'] ?? null;
            if ($dateValue === null || $occValue === null) {
                throw new \InvalidArgumentException("Item $i missing date or occupancy_rate");
            }
            $date = \DateTime::createFromFormat('Y-m-d', (string) $dateValue);
            if ($date === false) {
                $date = new \DateTime((string) $dateValue);
            }
            if (!is_numeric($occValue)) {
                throw new \InvalidArgumentException("occupancy_rate must be numeric at index $i");
            }
            $rows[] = [$dateCol => $date->format('Y-m-d'), 'occupancy_rate' => (float) $occValue];
        }
        usort($rows, fn($a, $b) => strcmp($a[$dateCol], $b[$dateCol]));

        $file = fopen($path, 'wb');
        fputcsv($file, [$dateCol, 'occupancy_rate']);
        foreach ($rows as $r) {
            fputcsv($file, [$r[$dateCol], $r['occupancy_rate']]);
        }
        fclose($file);
    }

    protected function runPython(string $workingDir, array $arguments): void
    {
        // Override PHP max execution time for long-running Python processes
        set_time_limit(300);

        $python = env('AI_PYTHON_BINARY', 'python');
        $process = new Process(array_merge([$python], $arguments), $workingDir);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $msg = sprintf(
                "AI process failed. Cmd: %s. Exit: %s. Error: %s",
                implode(' ', $arguments),
                $process->getExitCode(),
                $process->getErrorOutput(),
            );
            Log::error($msg);
            throw new ProcessFailedException($process);
        }
    }

    protected function loadPredictionsCsv(string $path, bool $isWeekly): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Prediction file not found: $path");
        }
        $f = fopen($path, 'rb');
        $header = fgetcsv($f);
        if ($header === false) {
            fclose($f);
            throw new \RuntimeException("Empty prediction file: $path");
        }

        $dateCol = $isWeekly ? 'week_start' : 'stay_date';
        $predictions = [];
        while (($row = fgetcsv($f)) !== false) {
            $rec = array_combine($header, $row);
            if ($rec === false) continue;
            $predictions[] = [
                'date' => $rec[$dateCol] ?? null,
                'percentage_point' => isset($rec['predicted_occupancy']) ? (float) $rec['predicted_occupancy'] : null,
                'lower_bound' => isset($rec['lower_bound']) ? (float) $rec['lower_bound'] : null,
                'upper_bound' => isset($rec['upper_bound']) ? (float) $rec['upper_bound'] : null,
                'crowd_level' => $rec['crowd_level'] ?? null,
            ];
        }
        fclose($f);
        return array_values($predictions);
    }

    /**
     * Get model info (metadata) for a granularity.
     */
    public function modelInfo(string $granularity = 'daily'): array
    {
        $aiRoot = $this->aiRoot();
        $metaPath = $aiRoot . '/Models/occupancy_' . $granularity . '_metadata.json';
        $modelPath = $aiRoot . '/Models/occupancy_' . $granularity . '.pkl';

        if (!file_exists($metaPath)) {
            return ['exists' => false, 'granularity' => $granularity];
        }

        $meta = json_decode(file_get_contents($metaPath), true);
        return [
            'exists' => true,
            'granularity' => $granularity,
            'model_name' => $meta['model_name'] ?? null,
            'order' => $meta['order'] ?? null,
            'exog_columns' => $meta['exog_columns'] ?? null,
            'baseline_strategy' => $meta['baseline_model']['strategy'] ?? null,
            'model_file' => $modelPath,
            'model_size' => file_exists($modelPath) ? filesize($modelPath) : 0,
        ];
    }

    /**
     * Get training metrics for all granularities.
     */
    public function metrics(): array
    {
        $aiRoot = $this->aiRoot();
        $result = [];
        foreach (['daily', 'weekly'] as $g) {
            $path = $aiRoot . '/Reports/metrics_' . $g . '.json';
            if (file_exists($path)) {
                $result[$g] = json_decode(file_get_contents($path), true);
            } else {
                $result[$g] = null;
            }
        }
        return $result;
    }

    /**
     * Get test summary metrics (accuracy/speed).
     */
    public function testSummary(): ?array
    {
        $path = $this->aiRoot() . '/Reports/test_summary.json';
        if (!file_exists($path)) return null;
        return json_decode(file_get_contents($path), true);
    }

    /**
     * Get historical test predictions for a given granularity.
     * Returns the predictions CSV as an array suitable for charting.
     */
    public function historicalPredictions(string $granularity = 'daily'): array
    {
        $aiRoot = $this->aiRoot();
        $path = $aiRoot . '/Reports/predictions_' . $granularity . '.csv';
        return $this->loadPredictionsCsv($path, $granularity === 'weekly');
    }
}
