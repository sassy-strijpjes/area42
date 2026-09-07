<div>
    <flux:button icon="arrow-path" wire:click="refreshData" variant="ghost" size="sm" class="mb-6">
        Refresh
    </flux:button>

    {{-- Historical model scores --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        {{-- Daily historical score --}}
        <flux:card class="p-6 text-center">
            @if($modelDaily['exists'])
                @php $dailyScore = $this->trustScore('daily'); @endphp
                <div class="relative inline-flex items-center justify-center mb-4">
                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" stroke-width="10" />
                        <circle cx="60" cy="60" r="52" fill="none"
                            stroke="{{ $dailyScore >= 90 ? '#22c55e' : ($dailyScore >= 75 ? '#eab308' : '#ef4444') }}"
                            stroke-width="10" stroke-linecap="round"
                            stroke-dasharray="{{ 2 * 3.14159 * 52 }}"
                            stroke-dashoffset="{{ 2 * 3.14159 * 52 * (1 - $dailyScore / 100) }}" />
                    </svg>
                    <span class="absolute text-3xl font-bold">{{ $dailyScore }}%</span>
                </div>
                <h3 class="text-lg font-semibold mb-1">Daily historical score</h3>
                <p class="text-sm text-zinc-500">
                    {{ $dailyScore >= 90 ? 'Very reliable' : ($dailyScore >= 75 ? 'Moderately reliable' : 'Needs retraining') }}
                </p>
            @else
                <div class="py-8 text-zinc-400">
                    <flux:icon name="x-circle" class="w-12 h-12 mx-auto mb-2" />
                    <p>Daily model not trained yet</p>
                </div>
            @endif
        </flux:card>

        {{-- Weekly historical score --}}
        <flux:card class="p-6 text-center">
            @if($modelWeekly['exists'])
                @php $weeklyScore = $this->trustScore('weekly'); @endphp
                <div class="relative inline-flex items-center justify-center mb-4">
                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e5e7eb" stroke-width="10" />
                        <circle cx="60" cy="60" r="52" fill="none"
                            stroke="{{ $weeklyScore >= 90 ? '#22c55e' : ($weeklyScore >= 75 ? '#eab308' : '#ef4444') }}"
                            stroke-width="10" stroke-linecap="round"
                            stroke-dasharray="{{ 2 * 3.14159 * 52 }}"
                            stroke-dashoffset="{{ 2 * 3.14159 * 52 * (1 - $weeklyScore / 100) }}" />
                    </svg>
                    <span class="absolute text-3xl font-bold">{{ $weeklyScore }}%</span>
                </div>
                <h3 class="text-lg font-semibold mb-1">Weekly historical score</h3>
                <p class="text-sm text-zinc-500">
                    {{ $weeklyScore >= 90 ? 'Very reliable' : ($weeklyScore >= 75 ? 'Moderately reliable' : 'Needs retraining') }}
                </p>
            @else
                <div class="py-8 text-zinc-400">
                    <flux:icon name="x-circle" class="w-12 h-12 mx-auto mb-2" />
                    <p>Weekly model not trained yet</p>
                </div>
            @endif
        </flux:card>
    </div>

    {{-- What this means --}}
    <flux:card class="p-6 mb-8">
        <h3 class="text-lg font-semibold mb-2">What does this score mean?</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            This is a <strong>historical score</strong>: 100% minus the historical average percentage error (SMAPE).
            It is not classification accuracy or a guarantee for an individual prediction. The forecast interval and the underlying SMAPE remain visible in the training view.
        </p>
    </flux:card>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <flux:card class="p-6">
            <h3 class="text-lg font-semibold mb-2">Train Model</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                Upload historical occupancy data to train or retrain the AI model.
            </p>
            <a href="{{ route('admin.ai.train') }}">
                <flux:button icon="arrow-up-tray" variant="primary">Go to Training</flux:button>
            </a>
        </flux:card>

        <flux:card class="p-6">
            <h3 class="text-lg font-semibold mb-2">Generate Predictions</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                Use the trained model to predict future occupancy rates with confidence bounds.
            </p>
            <a href="{{ route('admin.ai.predict') }}">
                <flux:button icon="chart-bar-square" variant="primary">Go to Predictions</flux:button>
            </a>
        </flux:card>
    </div>
</div>
