<?php

namespace App\Livewire;

use App\Services\AiPredictionService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

class AiTrain extends Component
{
    use WithFileUploads;

    public string $granularity = 'daily';
    public $csvFile = null;
    public ?string $csvRaw = null;
    public ?array $parsedPreview = null;
    public int $previewCount = 0;
    public int $previewTotal = 0;
    public bool $training = false;
    public ?array $result = null;
    public ?string $errorMessage = null;
    public array $metrics = [];

    public function updatedCsvFile(): void
    {
        // Livewire uploads are temporary — persist content now
        if ($this->csvFile && $this->csvFile->getRealPath()) {
            $this->csvRaw = file_get_contents($this->csvFile->getRealPath());
        }
        $this->parsePreview();
    }

    public function parsePreview(): void
    {
        $this->parsedPreview = null;
        $this->previewCount = 0;
        $this->previewTotal = 0;
        $this->errorMessage = null;

        if (empty(trim($this->csvRaw ?? ''))) {
            return;
        }

        $lines = explode("\n", trim($this->csvRaw));
        if (count($lines) < 2) {
            $this->errorMessage = 'CSV must have a header row and at least one data row.';
            return;
        }

        $header = str_getcsv(array_shift($lines));
        $dateCol = $this->granularity === 'weekly' ? 'week_start' : 'stay_date';

        if (!in_array($dateCol, $header) || !in_array('occupancy_rate', $header)) {
            $this->errorMessage = "CSV must have '{$dateCol}' and 'occupancy_rate' columns.";
            return;
        }

        $preview = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $row = str_getcsv($line);
            if (count($row) >= 2) {
                $preview[] = array_combine($header, $row);
            }
        }

        $this->parsedPreview = array_slice($preview, 0, 5);
        $this->previewCount = count($this->parsedPreview);
        $this->previewTotal = count($preview);
        $this->errorMessage = null;
    }

    public function train(AiPredictionService $service): void
    {
        $this->errorMessage = null;
        $this->result = null;

        if (empty(trim($this->csvRaw ?? ''))) {
            $this->errorMessage = 'Please upload a CSV file first.';
            return;
        }

        $lines = explode("\n", trim($this->csvRaw));
        $header = str_getcsv(array_shift($lines));
        $dateCol = $this->granularity === 'weekly' ? 'week_start' : 'stay_date';

        $data = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $row = str_getcsv($line);
            if (count($row) >= 2) {
                $combined = array_combine($header, $row);
                $data[] = [
                    'occupancy_rate' => (float) $combined['occupancy_rate'],
                    $dateCol => $combined[$dateCol],
                ];
            }
        }

        if (empty($data)) {
            $this->errorMessage = 'No valid data rows found.';
            return;
        }

        $this->training = true;

        try {
            $this->result = $service->train($data);
            $this->metrics = $service->metrics();
        } catch (\Exception $e) {
            $this->errorMessage = 'Training failed: ' . $e->getMessage();
            Log::error('AI Train failed', ['error' => $e->getMessage()]);
        }

        $this->training = false;
    }

    public function updatedGranularity(): void
    {
        $this->parsePreview();
    }

    public function render()
    {
        $service = app(AiPredictionService::class);
        $testSummary = $service->testSummary();

        $trustDaily = $testSummary ? (int) round(100 - ($testSummary['accuracy']['daily']['smape_pct'] ?? 100)) : null;
        $trustWeekly = $testSummary ? (int) round(100 - ($testSummary['accuracy']['weekly']['smape_pct'] ?? 100)) : null;

        return view('livewire.ai-train', [
            'existingMetrics' => $service->metrics(),
            'trustDaily' => $trustDaily,
            'trustWeekly' => $trustWeekly,
        ]);
    }
}
