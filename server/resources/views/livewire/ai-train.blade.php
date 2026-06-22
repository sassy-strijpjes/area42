<div x-data="{
    trainingLabel: '{{ $granularity }} model',
    crowdColor(level) {
        return level === 'laag' ? '#22c55e' : level === 'normaal' ? '#eab308' : '#ef4444';
    }
}">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Left: CSV Input --}}
        <flux:card class="p-6">
            <h3 class="text-lg font-semibold mb-4">Upload Training Data</h3>

            {{-- Granularity selector --}}
            <div class="mb-4">
                <flux:label class="mb-1">Granularity</flux:label>
                <div class="flex gap-2">
                    <flux:button
                        :variant="$granularity === 'daily' ? 'primary' : 'ghost'"
                        wire:click="$set('granularity', 'daily')"
                        size="sm"
                    >
                        Daily (stay_date)
                    </flux:button>
                    <flux:button
                        :variant="$granularity === 'weekly' ? 'primary' : 'ghost'"
                        wire:click="$set('granularity', 'weekly')"
                        size="sm"
                    >
                        Weekly (week_start)
                    </flux:button>
                </div>
            </div>

            {{-- CSV File Upload --}}
            <div class="mb-4">
                <flux:label class="mb-1">
                    Upload CSV file
                    <span class="text-zinc-400 font-normal">
                        ({{ $granularity === 'weekly' ? 'week_start' : 'stay_date' }}, occupancy_rate)
                    </span>
                </flux:label>
                <div class="mt-2">
                    <label
                        for="csv-upload"
                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed rounded-lg cursor-pointer border-zinc-300 dark:border-zinc-600 hover:border-zinc-400 dark:hover:border-zinc-500 bg-zinc-50 dark:bg-zinc-800 transition"
                    >
                        <div class="flex flex-col items-center justify-center pt-4 pb-4">
                            <flux:icon name="arrow-up-tray" class="w-8 h-8 text-zinc-400 mb-2" />
                            @if($csvFile)
                                <p class="text-sm text-zinc-700 dark:text-zinc-300 font-medium">{{ $csvFile->getClientOriginalName() }}</p>
                                <p class="text-xs text-zinc-500 mt-1">{{ round($csvFile->getSize() / 1024, 1) }} KB</p>
                            @else
                                <p class="text-sm text-zinc-500"><span class="font-medium text-blue-600 dark:text-blue-400">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-zinc-400 mt-1">CSV files only (max 10 MB)</p>
                            @endif
                        </div>
                        <input
                            id="csv-upload"
                            type="file"
                            wire:model.live="csvFile"
                            accept=".csv,.txt"
                            class="hidden"
                        />
                    </label>
                </div>
                @error('csvFile')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Preview --}}
            @if($parsedPreview)
                <div class="mb-4">
                    <flux:badge variant="solid" color="green" class="mb-2">
                        {{ $previewTotal }} rows detected
                    </flux:badge>
                    <div class="text-xs text-zinc-500 mb-1">Preview (first {{ $previewCount }} rows):</div>
                    <div class="overflow-x-auto border rounded-lg border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-zinc-50 dark:bg-zinc-800">
                                    <th class="text-left p-2">{{ $granularity === 'weekly' ? 'week_start' : 'stay_date' }}</th>
                                    <th class="text-right p-2">occupancy_rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($parsedPreview as $row)
                                    <tr class="border-t border-zinc-100 dark:border-zinc-800">
                                        <td class="p-2">{{ $row[$granularity === 'weekly' ? 'week_start' : 'stay_date'] }}</td>
                                        <td class="text-right p-2">{{ $row['occupancy_rate'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($errorMessage)
                <flux:callout color="red" icon="exclamation-triangle" class="mb-4">
                    {{ $errorMessage }}
                </flux:callout>
            @endif

            <flux:button
                wire:click="train"
                variant="primary"
                icon="play"
                :loading="$training"
                class="w-full"
            >
                {{ $training ? 'Training in progress...' : 'Start Training' }}
            </flux:button>
        </flux:card>

        {{-- Right: Results & Existing Metrics --}}
        <div class="space-y-6">
            {{-- Training Result --}}
            @if($result)
                <flux:card class="p-6 border-green-300 dark:border-green-700">
                    <div class="flex items-center gap-2 mb-3">
                        <flux:icon name="check-circle" class="w-5 h-5 text-green-600" />
                        <h3 class="text-lg font-semibold text-green-700 dark:text-green-400">Training Complete</h3>
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">Status</dt>
                            <dd class="font-medium capitalize">{{ $result['status'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">Granularity</dt>
                            <dd class="font-medium capitalize">{{ $result['granularity'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500">Model Saved</dt>
                            <dd class="font-medium text-xs">{{ $result['model'] }}</dd>
                        </div>
                    </dl>
                </flux:card>
            @endif

            {{-- Existing Model Metrics --}}
            @if(!empty($existingMetrics))
                <flux:card class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Existing Model Metrics</h3>
                    @foreach($existingMetrics as $g => $m)
                        @if($m)
                            <div class="mb-3 last:mb-0">
                                <div class="text-sm font-medium capitalize mb-1">{{ $g }}</div>
                                <div class="flex gap-4 text-xs text-zinc-500">
                                    <span>Train: {{ $m['train_rows'] ?? '--' }} rows</span>
                                    <span>Test: {{ $m['test_rows'] ?? '--' }} rows</span>
                                    <span>MAE: {{ $m['sarimax_mae'] ?? '--' }}</span>
                                    <span>Order: {{ isset($m['best_order']) ? implode(',', $m['best_order']) : '--' }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </flux:card>
            @endif
        </div>
    </div>

    {{-- Loading overlay — outside x-data so it renders at document root --}}
    <div wire:loading wire:target="train" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3); backdrop-filter: blur(4px);">
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); padding: 2rem; text-align: center; max-width: 400px; margin: 0 1rem;">
            <div style="width: 48px; height: 48px; border: 3px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1rem;"></div>
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.25rem;">Training {{ $granularity }} model...</h3>
            <p style="font-size: 0.875rem; color: #6b7280;">This may take up to a minute. Please don't close this page.</p>
        </div>
    </div>

    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</div>
