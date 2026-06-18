<?php

use App\Livewire\FormComponent;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

new class extends FormComponent {

    public int    $ruleId   = 0;
    public string $ruleName = '';

    public string $base_price = '';
    public array  $rate_bands = [];
    public array  $surcharges = [];
    public array  $extras     = [];

    public function mount($rule): void
    {
        $this->ruleId = (int) $rule;

        $ruleRow        = DB::table('accommodation_types')->find($this->ruleId);
        $this->ruleName = $ruleRow->name ?? '';

        $pricing = DB::table('accommodation_pricing')
            ->where('accommodation_type_id', $this->ruleId)
            ->first();

        $this->base_price = $pricing
            ? number_format((float) $pricing->base_price, 2, '.', '')
            : '';

        $this->rate_bands = DB::table('accommodation_rate_bands')
            ->where('accommodation_type_id', $this->ruleId)
            ->orderBy('start_date')
            ->get()
            ->map(fn($b) => [
                'id'              => $b->id,
                'label'           => $b->label,
                'season'          => $b->season,
                'start_date'      => $b->start_date,
                'end_date'        => $b->end_date,
                'price_per_night' => number_format((float) $b->price_per_night, 2, '.', ''),
            ])
            ->toArray();

        $this->surcharges = DB::table('accommodation_surcharges')
            ->where('accommodation_type_id', $this->ruleId)
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'type'           => $s->type,
                'label'          => $s->label,
                'amount_type'    => $s->amount_type,
                'amount'         => number_format((float) $s->amount, 2, '.', ''),
                'days_threshold' => $s->days_threshold,
                'is_active'      => (bool) $s->is_active,
            ])
            ->toArray();

        $this->extras = DB::table('accommodation_extras')
            ->where('accommodation_type_id', $this->ruleId)
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'label'       => $e->label,
                'amount_type' => $e->amount_type,
                'amount'      => number_format((float) $e->amount, 2, '.', ''),
                'is_active'   => (bool) $e->is_active,
            ])
            ->toArray();
    }

    public function addRateBand(): void
    {
        $this->rate_bands[] = [
            'id' => null, 'label' => '', 'season' => 'mid',
            'start_date' => '', 'end_date' => '', 'price_per_night' => '',
        ];
    }

    public function removeRateBand(int $index): void
    {
        array_splice($this->rate_bands, $index, 1);
    }

    public function addSurcharge(): void
    {
        $this->surcharges[] = [
            'id' => null, 'type' => 'weekend', 'label' => '',
            'amount_type' => 'fixed', 'amount' => '',
            'days_threshold' => null, 'is_active' => true,
        ];
    }

    public function removeSurcharge(int $index): void
    {
        array_splice($this->surcharges, $index, 1);
    }

    public function addExtra(): void
    {
        $this->extras[] = [
            'id' => null, 'label' => '',
            'amount_type' => 'fixed', 'amount' => '', 'is_active' => true,
        ];
    }

    public function removeExtra(int $index): void
    {
        array_splice($this->extras, $index, 1);
    }

    public function save(): void
    {
        $this->validate([
            'base_price'                   => 'required|numeric|min:0',
            'rate_bands.*.label'           => 'required|string|max:255',
            'rate_bands.*.season'          => 'required|in:high,mid,low',
            'rate_bands.*.start_date'      => 'required|date',
            'rate_bands.*.end_date'        => 'required|date|after:rate_bands.*.start_date',
            'rate_bands.*.price_per_night' => 'required|numeric|min:0',
            'surcharges.*.label'           => 'required|string|max:255',
            'surcharges.*.type'            => 'required|in:weekend,last_minute,early_bird',
            'surcharges.*.amount_type'     => 'required|in:fixed,percentage',
            'surcharges.*.amount'          => 'required|numeric|min:0',
            'surcharges.*.days_threshold'  => 'nullable|integer|min:1',
            'extras.*.label'               => 'required|string|max:255',
            'extras.*.amount_type'         => 'required|in:fixed,percentage',
            'extras.*.amount'              => 'required|numeric|min:0',
        ]);

        $now = now();

        $oldPricing = DB::table('accommodation_pricing')
            ->where('accommodation_type_id', $this->ruleId)
            ->first();

        if ($oldPricing) {
            DB::table('accommodation_pricing')
                ->where('accommodation_type_id', $this->ruleId)
                ->update(['base_price' => $this->base_price, 'updated_at' => $now]);
        } else {
            DB::table('accommodation_pricing')->insert([
                'accommodation_type_id' => $this->ruleId,
                'base_price'            => $this->base_price,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }

        $this->syncRows(
            'accommodation_rate_bands', $this->rate_bands,
            fn($row) => [
                'accommodation_type_id' => $this->ruleId,
                'label'           => $row['label'],
                'season'          => $row['season'],
                'start_date'      => $row['start_date'],
                'end_date'        => $row['end_date'],
                'price_per_night' => $row['price_per_night'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            fn($row) => [
                'label'           => $row['label'],
                'season'          => $row['season'],
                'start_date'      => $row['start_date'],
                'end_date'        => $row['end_date'],
                'price_per_night' => $row['price_per_night'],
                'updated_at'      => $now,
            ],
        );

        $this->syncRows(
            'accommodation_surcharges', $this->surcharges,
            fn($row) => [
                'accommodation_type_id' => $this->ruleId,
                'type'           => $row['type'],
                'label'          => $row['label'],
                'amount_type'    => $row['amount_type'],
                'amount'         => $row['amount'],
                'days_threshold' => $row['days_threshold'] ?: null,
                'is_active'      => $row['is_active'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            fn($row) => [
                'type'           => $row['type'],
                'label'          => $row['label'],
                'amount_type'    => $row['amount_type'],
                'amount'         => $row['amount'],
                'days_threshold' => $row['days_threshold'] ?: null,
                'is_active'      => $row['is_active'],
                'updated_at'     => $now,
            ],
        );

        $this->syncRows(
            'accommodation_extras', $this->extras,
            fn($row) => [
                'accommodation_type_id' => $this->ruleId,
                'label'       => $row['label'],
                'amount_type' => $row['amount_type'],
                'amount'      => $row['amount'],
                'is_active'   => $row['is_active'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            fn($row) => [
                'label'       => $row['label'],
                'amount_type' => $row['amount_type'],
                'amount'      => $row['amount'],
                'is_active'   => $row['is_active'],
                'updated_at'  => $now,
            ],
        );

        Flux::toast('Pricing updated', variant: 'success');
        $this->redirect(route('staff.accommodation.pricing'), navigate: true);
    }

    private function syncRows(
        string   $table,
        array    $rows,
        callable $insertData,
        callable $updateData,
    ): void {
        $existingIds  = DB::table($table)
            ->where('accommodation_type_id', $this->ruleId)
            ->pluck('id')
            ->toArray();

        $submittedIds = array_filter(array_column($rows, 'id'));

        foreach (array_diff($existingIds, $submittedIds) as $id) {
            DB::table($table)->where('id', $id)->delete();
        }

        foreach ($rows as $row) {
            if (!empty($row['id'])) {
                DB::table($table)->where('id', $row['id'])->update($updateData($row));
            } else {
                DB::table($table)->insertGetId($insertData($row));
            }
        }
    }
};
