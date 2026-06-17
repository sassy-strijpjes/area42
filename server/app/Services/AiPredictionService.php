<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AiPredictionService
{
    public function trainAndPredict(array $data, int $days): array
    {
        $aiRoot = realpath(base_path('../ai')) ?: realpath(base_path('ai'));

        if ($aiRoot === false || !is_dir($aiRoot)) {
            throw new \RuntimeException('AI folder not found. Create an ai folder inside the area42 root. Expected location: ' . base_path('../ai'));
        }

        $tmpDirectory = storage_path('app/ai');
        if (!is_dir($tmpDirectory)) {
            mkdir($tmpDirectory, 0755, true);
        }

        // Determine whether input uses weekly or daily keys
        $isWeekly = $this->isWeeklyData($data);

        // Save user data as history CSV for prediction seeding
        $historyFile = $tmpDirectory . DIRECTORY_SEPARATOR . 'ai_history_input_' . uniqid() . '.csv';
        $this->prepareHistoryCsv($data, $historyFile, $isWeekly);

        $pythonBinary = env('AI_PYTHON_BINARY', 'python');

        // Train the model on the full processed dataset
        $granularity = $isWeekly ? 'weekly' : 'daily';
        $this->runPythonScript($pythonBinary, $aiRoot, [
            'scripts/train_model.py',
            '--granularity', $granularity,
        ]);

        // Run predictions using the user's history as the starting point
        $predictionOutput = $aiRoot . DIRECTORY_SEPARATOR . 'Reports' . DIRECTORY_SEPARATOR . 'future_predictions.csv';
        $this->runPythonScript($pythonBinary, $aiRoot, [
            'scripts/predict_occupancy.py',
            '--granularity', $granularity,
            '--history', $historyFile,
            '--periods', (string) $days,
            '--output', 'Reports/future_predictions.csv',
        ]);

        $predictions = $this->loadPredictionsCsv($predictionOutput, $isWeekly);

        @unlink($historyFile);

        return $predictions;
    }

    protected function isWeeklyData(array $data): bool
    {
        foreach ($data as $item) {
            if (isset($item['week_start'])) {
                return true;
            }
            if (isset($item['date'])) {
                return false;
            }
        }
        // Default to daily if no keys found
        return false;
    }

    /**
     * Write user-submitted historical data as a CSV that predict_occupancy.py
     * can consume. The column name depends on the granularity.
     */
    protected function prepareHistoryCsv(array $data, string $path, bool $isWeekly): void
    {
        $dateCol = $isWeekly ? 'week_start' : 'stay_date';
        $rows = [];

        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('Each item in data must be an object with week_start or date and occupancy_rate. Item index: ' . $index);
            }

            $dateValue = $item['week_start'] ?? $item['date'] ?? null;
            $occupancyValue = $item['occupancy_rate'] ?? null;

            if ($dateValue === null || $occupancyValue === null) {
                throw new \InvalidArgumentException('Each data item must contain week_start or date and occupancy_rate. Item index: ' . $index);
            }

            $date = \DateTime::createFromFormat('Y-m-d', (string) $dateValue);
            if ($date === false) {
                $date = new \DateTime((string) $dateValue);
            }

            if ($date === false) {
                throw new \InvalidArgumentException('Invalid date format for item index: ' . $index . '. Use YYYY-MM-DD.');
            }

            if (!is_numeric($occupancyValue)) {
                throw new \InvalidArgumentException('occupancy_rate must be numeric for item index: ' . $index);
            }

            $rows[] = [
                $dateCol => $date->format('Y-m-d'),
                'occupancy_rate' => (float) $occupancyValue,
            ];
        }

        usort($rows, static function (array $a, array $b) use ($dateCol) {
            return strcmp($a[$dateCol], $b[$dateCol]);
        });

        $file = fopen($path, 'wb');
        if ($file === false) {
            throw new \RuntimeException('Unable to open temporary history file for writing: ' . $path);
        }

        fputcsv($file, [$dateCol, 'occupancy_rate']);
        foreach ($rows as $row) {
            fputcsv($file, [$row[$dateCol], $row['occupancy_rate']]);
        }

        fclose($file);
    }

    protected function runPythonScript(string $pythonBinary, string $workingDir, array $arguments): void
    {
        $command = array_merge([$pythonBinary], $arguments);
        $process = new Process($command, $workingDir);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $message = sprintf(
                "AI process failed. Command: %s. Exit code: %s. Output: %s. Error: %s",
                implode(' ', $command),
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput(),
            );
            Log::error($message);
            throw new ProcessFailedException($process);
        }
    }

    protected function loadPredictionsCsv(string $path, bool $isWeekly): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('Prediction output file not found: ' . $path);
        }

        $file = fopen($path, 'rb');
        if ($file === false) {
            throw new \RuntimeException('Unable to read prediction output file: ' . $path);
        }

        $header = fgetcsv($file);
        if ($header === false) {
            fclose($file);
            throw new \RuntimeException('Prediction output file is empty: ' . $path);
        }

        // Daily CSVs use "stay_date", weekly CSVs use "week_start"
        $dateCol = $isWeekly ? 'week_start' : 'stay_date';

        $predictions = [];
        while (($row = fgetcsv($file)) !== false) {
            $record = array_combine($header, $row);
            if ($record === false) {
                continue;
            }

            $dateValue = $record[$dateCol] ?? null;
            // Weekly predictions include year in the period; extract date portion
            if ($dateValue === null && isset($record['period'])) {
                $dateValue = $record['period']; // e.g. "2026-W25"
            }

            $predictions[] = [
                'date' => $dateValue,
                'percentage_point' => isset($record['predicted_occupancy']) ? (float) $record['predicted_occupancy'] : null,
            ];
        }

        fclose($file);

        return array_values($predictions);
    }
}
