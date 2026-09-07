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

    /** Historical score = 100 - historical SMAPE; this is not classification accuracy. */
    public function trustScore(string $granularity): ?int
    {
        $score = $this->testSummary['accuracy'][$granularity]['historical_score_pct'] ?? null;
        return is_numeric($score) ? (int) round((float) $score) : null;
    }

    public function render()
    {
        return view('livewire.ai-overview');
    }
}
