<div x-data="predictionChart()" x-init="init()">
    {{-- Tabs --}}
    <div class="flex gap-2 mb-6 border-b border-zinc-200 dark:border-zinc-700 pb-3">
        <flux:button :variant="$activeView === 'predict' ? 'primary' : 'ghost'" wire:click="showPredict">
            <flux:icon name="forward" class="mr-1" /> Future Predictions
        </flux:button>
        <flux:button :variant="$activeView === 'history' ? 'primary' : 'ghost'" wire:click="loadHistory">
            <flux:icon name="clock" class="mr-1" /> Test History
        </flux:button>
    </div>

    @if($activeView === 'predict')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Controls --}}
            <flux:card class="p-6 lg:col-span-1">
                <h3 class="text-lg font-semibold mb-4">Prediction Settings</h3>

                {{-- Granularity --}}
                <div class="mb-4">
                    <flux:label class="mb-1">Granularity</flux:label>
                    <div class="flex gap-2">
                        <flux:button
                            :variant="$granularity === 'daily' ? 'primary' : 'ghost'"
                            wire:click="$set('granularity', 'daily')"
                            size="sm"
                        >Daily</flux:button>
                        <flux:button
                            :variant="$granularity === 'weekly' ? 'primary' : 'ghost'"
                            wire:click="$set('granularity', 'weekly')"
                            size="sm"
                        >Weekly</flux:button>
                    </div>
                </div>

                {{-- Start Date --}}
                <div class="mb-4">
                    <flux:label class="mb-1">Start Date</flux:label>
                    <flux:input type="date" wire:model="date" />
                </div>

                {{-- Days/Weeks --}}
                <div class="mb-4">
                    <flux:label class="mb-1">
                        {{ $granularity === 'weekly' ? 'Weeks' : 'Days' }} to Predict
                    </flux:label>
                    <flux:input type="number" wire:model="days" min="1" max="730" />
                </div>

                {{-- Model Info --}}
                @php $currentModel = $granularity === 'daily' ? $modelDaily : $modelWeekly; @endphp
                @if($currentModel['exists'])
                    <flux:badge variant="solid" color="green" class="mb-4">
                        Model trained ({{ implode(',', $currentModel['order']) }})
                    </flux:badge>
                @else
                    <flux:badge variant="solid" color="red" class="mb-4">
                        No model — train first
                    </flux:badge>
                @endif

                @if($errorMessage)
                    <flux:callout color="red" icon="exclamation-triangle" class="mb-4">
                        {{ $errorMessage }}
                    </flux:callout>
                @endif

                <flux:button
                    wire:click="predict"
                    variant="primary"
                    icon="play"
                    :loading="$loading"
                    class="w-full"
                >
                    {{ $loading ? 'Generating...' : 'Generate Predictions' }}
                </flux:button>
            </flux:card>

            {{-- Chart --}}
            <flux:card class="p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Occupancy Forecast</h3>

                @if($chartData)
                    <div class="relative" style="height: 400px;">
                        <canvas id="predictionChart"></canvas>
                    </div>

                    {{-- Legend --}}
                    <div class="flex flex-wrap gap-3 mt-4 text-xs">
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span>
                            Predicted Occupancy
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-blue-300 inline-block opacity-30"></span>
                            80% Confidence Range
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>
                            Low (&lt;50%)
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span>
                            Normal (50-80%)
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
                            High (&gt;80%)
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="mt-6 overflow-x-auto max-h-80 overflow-y-auto border rounded-lg border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="text-left p-2">Date</th>
                                    <th class="text-right p-2">Occupancy</th>
                                    <th class="text-right p-2">Low</th>
                                    <th class="text-right p-2">High</th>
                                    <th class="text-center p-2">Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($predictions as $i => $p)
                                    <tr class="border-t border-zinc-100 dark:border-zinc-800">
                                        <td class="p-2">{{ $p['date'] }}</td>
                                        <td class="text-right p-2 font-medium">{{ number_format($p['percentage_point'], 1) }}%</td>
                                        <td class="text-right p-2 text-zinc-400">{{ number_format($p['lower_bound'], 1) }}%</td>
                                        <td class="text-right p-2 text-zinc-400">{{ number_format($p['upper_bound'], 1) }}%</td>
                                        <td class="text-center p-2">
                                            <flux:badge
                                                variant="solid"
                                                size="sm"
                                                color="{{ $p['crowd_level'] === 'laag' ? 'green' : ($p['crowd_level'] === 'normaal' ? 'yellow' : 'red') }}"
                                            >
                                                {{ $p['crowd_level'] }}
                                            </flux:badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(!$loading)
                    <div class="flex flex-col items-center justify-center py-16 text-zinc-400">
                        <flux:icon name="chart-bar" class="w-16 h-16 mb-4" />
                        <p class="text-lg">Set parameters and click "Generate Predictions"</p>
                        <p class="text-sm mt-1">A chart with confidence bounds will appear here.</p>
                    </div>
                @else
                    <div class="flex items-center justify-center py-16">
                        <flux:icon name="arrow-path" class="w-8 h-8 animate-spin text-blue-500" />
                    </div>
                @endif
            </flux:card>
        </div>
    @endif

    @if($activeView === 'history')
        <flux:card class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Test Set Predictions — {{ ucfirst($granularity) }}</h3>
                <div class="flex gap-2">
                    <flux:button wire:click="$set('granularity', 'daily')" :variant="$granularity === 'daily' ? 'primary' : 'ghost'" size="sm">Daily</flux:button>
                    <flux:button wire:click="$set('granularity', 'weekly')" :variant="$granularity === 'weekly' ? 'primary' : 'ghost'" size="sm">Weekly</flux:button>
                </div>
            </div>

            @if($historicalData)
                <div class="relative" style="height: 400px;">
                    <canvas id="historyChart"></canvas>
                </div>

                <div class="mt-6 overflow-x-auto max-h-96 overflow-y-auto border rounded-lg border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="text-left p-2">Date</th>
                                <th class="text-right p-2">Predicted</th>
                                <th class="text-right p-2">Lower</th>
                                <th class="text-right p-2">Upper</th>
                                <th class="text-center p-2">Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historicalData as $p)
                                <tr class="border-t border-zinc-100 dark:border-zinc-800">
                                    <td class="p-2">{{ $p['date'] }}</td>
                                    <td class="text-right p-2 font-medium">{{ number_format($p['percentage_point'], 1) }}%</td>
                                    <td class="text-right p-2 text-zinc-400">{{ number_format($p['lower_bound'], 1) }}%</td>
                                    <td class="text-right p-2 text-zinc-400">{{ number_format($p['upper_bound'], 1) }}%</td>
                                    <td class="text-center p-2">
                                        <flux:badge
                                            variant="solid" size="sm"
                                            color="{{ $p['crowd_level'] === 'laag' ? 'green' : ($p['crowd_level'] === 'normaal' ? 'yellow' : 'red') }}"
                                        >{{ $p['crowd_level'] }}</flux:badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-zinc-400">
                    <flux:icon name="clock" class="w-16 h-16 mb-4" />
                    <p>No historical data loaded.</p>
                </div>
            @endif
        </flux:card>
    @endif
</div>

<script>
function predictionChart() {
    return {
        predictionChartInstance: null,
        historyChartInstance: null,

        init() {
            this.$watch('$wire.chartData', (data) => {
                if (data) this.renderPredictionChart(data);
            });
            this.$watch('$wire.historicalData', (data) => {
                if (data) this.renderHistoryChart(data);
            });
        },

        renderPredictionChart(data) {
            if (this.predictionChartInstance) this.predictionChartInstance.destroy();
            const ctx = document.getElementById('predictionChart');
            if (!ctx) return;

            const labels = data.labels || [];
            const values = data.values || [];
            const lower = data.lower || [];
            const upper = data.upper || [];
            const crowdLevels = data.crowd_levels || [];

            const pointColors = crowdLevels.map(l =>
                l === 'laag' ? '#22c55e' : l === 'normaal' ? '#eab308' : '#ef4444'
            );

            this.predictionChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Upper Bound (80%)',
                            data: upper,
                            borderColor: 'rgba(59, 130, 246, 0.15)',
                            backgroundColor: 'rgba(59, 130, 246, 0.08)',
                            fill: '+1',
                            pointRadius: 0,
                            borderWidth: 1,
                            tension: 0.3,
                        },
                        {
                            label: 'Predicted Occupancy',
                            data: values,
                            borderColor: '#3b82f6',
                            backgroundColor: '#3b82f6',
                            pointBackgroundColor: pointColors,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            borderWidth: 2,
                            tension: 0.3,
                            fill: false,
                        },
                        {
                            label: 'Lower Bound (80%)',
                            data: lower,
                            borderColor: 'rgba(59, 130, 246, 0.15)',
                            backgroundColor: 'rgba(59, 130, 246, 0.02)',
                            pointRadius: 0,
                            borderWidth: 1,
                            tension: 0.3,
                            fill: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%`,
                            },
                        },
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            ticks: { maxTicksLimit: 15, maxRotation: 45 },
                            grid: { display: false },
                        },
                        y: {
                            min: 0,
                            max: 100,
                            ticks: { callback: v => v + '%' },
                            title: { display: true, text: 'Occupancy Rate (%)' },
                        },
                    },
                },
            });
        },

        renderHistoryChart(data) {
            if (this.historyChartInstance) this.historyChartInstance.destroy();
            const ctx = document.getElementById('historyChart');
            if (!ctx) return;

            const labels = data.map(d => d.date);
            const values = data.map(d => d.percentage_point);
            const lower = data.map(d => d.lower_bound);
            const upper = data.map(d => d.upper_bound);
            const crowdLevels = data.map(d => d.crowd_level);
            const pointColors = crowdLevels.map(l =>
                l === 'laag' ? '#22c55e' : l === 'normaal' ? '#eab308' : '#ef4444'
            );

            this.historyChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Upper Bound',
                            data: upper,
                            borderColor: 'rgba(139, 92, 246, 0.15)',
                            backgroundColor: 'rgba(139, 92, 246, 0.08)',
                            fill: '+1',
                            pointRadius: 0,
                            borderWidth: 1,
                            tension: 0.3,
                        },
                        {
                            label: 'Predicted Occupancy',
                            data: values,
                            borderColor: '#8b5cf6',
                            backgroundColor: '#8b5cf6',
                            pointBackgroundColor: pointColors,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                            borderWidth: 2,
                            tension: 0.3,
                            fill: false,
                        },
                        {
                            label: 'Lower Bound',
                            data: lower,
                            borderColor: 'rgba(139, 92, 246, 0.15)',
                            backgroundColor: 'rgba(139, 92, 246, 0.02)',
                            pointRadius: 0,
                            borderWidth: 1,
                            tension: 0.3,
                            fill: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%`,
                            },
                        },
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            ticks: { maxTicksLimit: 20, maxRotation: 45 },
                            grid: { display: false },
                        },
                        y: {
                            min: 0, max: 100,
                            ticks: { callback: v => v + '%' },
                            title: { display: true, text: 'Occupancy Rate (%)' },
                        },
                    },
                },
            });
        },
    };
}
</script>

    {{-- Loading overlay — outside x-data so it renders at document root --}}
    <div wire:loading wire:target="predict" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3); backdrop-filter: blur(4px);">
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); padding: 2rem; text-align: center; max-width: 400px; margin: 0 1rem;">
            <div style="width: 48px; height: 48px; border: 3px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1rem;"></div>
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem;">Generating predictions...</h3>
            <p style="font-size: 0.875rem; color: #6b7280;">Running the AI model. This should only take a moment.</p>
        </div>
    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</div>
