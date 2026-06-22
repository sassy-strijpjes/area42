<?php

namespace App\Livewire;

use App\Services\AiPredictionService;
use Livewire\Component;

class AiOverview extends Component
{
    public array $modelDaily;
    public array $modelWeekly;
    public ?array $testSummary;

    public function mount(AiPredictionService $service): void
    {
        $this->refreshData($service);
    }

    public function refreshData(AiPredictionService $service): void
    {
        $this->modelDaily = $service->modelInfo('daily');
        $this->modelWeekly = $service->modelInfo('weekly');
        $this->testSummary = $service->testSummary();
    }

    /**
     * Trust = 100 - SMAPE%. SMAPE measures how far off predictions are on average.
     * 0% error = 100% trust, 100% error = 0% trust.
     */
    public function trustScore(string $granularity): ?int
    {
        $smape = $this->testSummary['accuracy'][$granularity]['smape_pct'] ?? null;
        if ($smape === null) return null;
        return (int) round(100 - $smape);
    }

    public function render()
    {
        return view('livewire.ai-overview');
    }
}
