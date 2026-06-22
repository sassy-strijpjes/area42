<?php

namespace App\Livewire;

use App\Services\AiPredictionService;
use Livewire\Component;

class AiPredict extends Component
{
    public string $date = '';
    public int $days = 30;
    public string $granularity = 'daily';
    public ?array $predictions = null;
    public ?array $chartData = null;
    public ?array $historicalData = null;
    public ?string $errorMessage = null;
    public bool $loading = false;
    public string $activeView = 'predict'; // 'predict' or 'history'

    public function mount(): void
    {
        $this->date = date('Y-m-d');
    }

    public function predict(AiPredictionService $service): void
    {
        $this->errorMessage = null;
        $this->predictions = null;
        $this->chartData = null;
        $this->loading = true;

        try {
            $this->predictions = $service->predict($this->date, $this->days, $this->granularity);
            $this->chartData = $this->buildChartData($this->predictions);
        } catch (\Exception $e) {
            $this->errorMessage = 'Prediction failed: ' . $e->getMessage();
        }

        $this->loading = false;
    }

    public function loadHistory(AiPredictionService $service): void
    {
        $this->activeView = 'history';
        $this->historicalData = $service->historicalPredictions($this->granularity);
    }

    public function showPredict(): void
    {
        $this->activeView = 'predict';
    }

    public function updatedGranularity(): void
    {
        $this->predictions = null;
        $this->chartData = null;
        $this->errorMessage = null;

        if ($this->activeView === 'history') {
            $service = app(AiPredictionService::class);
            $this->historicalData = $service->historicalPredictions($this->granularity);
        }
    }

    protected function buildChartData(array $predictions): array
    {
        $labels = [];
        $values = [];
        $lower = [];
        $upper = [];
        $crowdLevels = [];

        foreach ($predictions as $p) {
            $labels[] = $p['date'];
            $values[] = $p['percentage_point'];
            $lower[] = $p['lower_bound'];
            $upper[] = $p['upper_bound'];
            $crowdLevels[] = $p['crowd_level'];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'lower' => $lower,
            'upper' => $upper,
            'crowd_levels' => $crowdLevels,
        ];
    }

    public function render()
    {
        $service = app(AiPredictionService::class);
        $modelDaily = $service->modelInfo('daily');
        $modelWeekly = $service->modelInfo('weekly');

        return view('livewire.ai-predict', [
            'modelDaily' => $modelDaily,
            'modelWeekly' => $modelWeekly,
        ]);
    }
}
