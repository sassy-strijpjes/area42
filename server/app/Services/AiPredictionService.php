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

        $inputFile = $tmpDirectory . DIRECTORY_SEPARATOR . 'ai_training_input_' . uniqid() . '.csv';
        $this->prepareTrainingCsv($data, $inputFile);

        $weeks = max(1, (int) ceil($days / 7));
        $pythonBinary = env('AI_PYTHON_BINARY', 'python');

        $this->runPythonScript($pythonBinary, $aiRoot, [
            'scripts/train_model.py',
            '--input', $inputFile,
            '--train-output', 'Data/Training/train_weekly.csv',
            '--test-output', 'Data/Test/test_weekly.csv',
            '--model-output', 'Models/occupancy_regressor.pkl',
            '--metrics-output', 'Reports/metrics.json',
            '--predictions-output', 'Reports/predictions.csv',
            '--predictions-json-output', 'Reports/predictions.json',
        ]);

        $predictionOutput = $aiRoot . DIRECTORY_SEPARATOR . 'Reports' . DIRECTORY_SEPARATOR . 'future_predictions.csv';
        $this->runPythonScript($pythonBinary, $aiRoot, [
            'scripts/predict_occupancy.py',
            '--model', 'Models/occupancy_regressor.pkl',
            '--history', $inputFile,
            '--weeks', (string) $weeks,
            '--output', 'Reports/future_predictions.csv',
        ]);

        $predictions = $this->loadPredictionsCsv($predictionOutput);

        @unlink($inputFile);

        return $predictions;
    }

    protected function prepareTrainingCsv(array $data, string $path): void
    {
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
                'week_start' => $date->format('Y-m-d'),
                'occupancy_rate' => (float) $occupancyValue,
            ];
        }

        usort($rows, static function (array $a, array $b) {
            return strcmp($a['week_start'], $b['week_start']);
        });

        $file = fopen($path, 'wb');
        if ($file === false) {
            throw new \RuntimeException('Unable to open temporary training file for writing: ' . $path);
        }

        fputcsv($file, ['week_start', 'occupancy_rate']);
        foreach ($rows as $row) {
            fputcsv($file, [$row['week_start'], $row['occupancy_rate']]);
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

    protected function loadPredictionsCsv(string $path): array
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

        $predictions = [];
        while (($row = fgetcsv($file)) !== false) {
            $record = array_combine($header, $row);
            if ($record === false) {
                continue;
            }

            $predictions[] = [
                'date' => $record['week_start'] ?? null,
                'percentage_point' => isset($record['predicted_occupancy']) ? (float) $record['predicted_occupancy'] : null,
            ];
        }

        fclose($file);

        return array_values($predictions);
    }
}
