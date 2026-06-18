<?php

use App\Livewire\FormComponent;
use Flux\Flux;
use Illuminate\Support\Facades\DB;

new class extends FormComponent {

    public int    $typeId   = 0;
    public string $typeName = '';

    public string $base_price = '';
    public array  $rate_bands = [];
    public array  $surcharges = [];
    public array  $extras     = [];

    public function mount(): void
    {
        $typeId = request()->integer('type');

        if ($typeId && DB::table('accommodation_types')->where('id', $typeId)->exists()) {
            $this->typeId   = $typeId;
            $this->typeName = DB::table('accommodation_types')->find($typeId)->name;
        }
    }

    public function updatedTypeId(): void
    {
        $type           = DB::table('accommodation_types')->find($this->typeId);
        $this->typeName = $type->name ?? '';
    }

    public function addRateBand(): void
    {
        $this->rate_bands[] = [
            'label' => '', 'season' => 'mid',
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
            'type' => 'weekend', 'label' => '',
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
            'label' => '', 'amount_type' => 'fixed',
            'amount' => '', 'is_active' => true,
        ];
    }

    public function removeExtra(int $index): void
    {
        array_splice($this->extras, $index, 1);
    }

    public function save(): void
    {
        $this->validate([
            'typeId'                       => 'required|exists:accommodation_types,id',
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

        DB::table('accommodation_pricing')->insert([
            'accommodation_type_id' => $this->typeId,
            'base_price'            => $this->base_price,
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        foreach ($this->rate_bands as $row) {
            DB::table('accommodation_rate_bands')->insert([
                'accommodation_type_id' => $this->typeId,
                'label'                 => $row['label'],
                'season'                => $row['season'],
                'start_date'            => $row['start_date'],
                'end_date'              => $row['end_date'],
                'price_per_night'       => $row['price_per_night'],
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }

        foreach ($this->surcharges as $row) {
            DB::table('accommodation_surcharges')->insert([
                'accommodation_type_id' => $this->typeId,
                'type'                  => $row['type'],
                'label'                 => $row['label'],
                'amount_type'           => $row['amount_type'],
                'amount'                => $row['amount'],
                'days_threshold'        => $row['days_threshold'] ?: null,
                'is_active'             => $row['is_active'],
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }

        foreach ($this->extras as $row) {
            DB::table('accommodation_extras')->insert([
                'accommodation_type_id' => $this->typeId,
                'label'                 => $row['label'],
                'amount_type'           => $row['amount_type'],
                'amount'                => $row['amount'],
                'is_active'             => $row['is_active'],
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }

        Flux::toast('Pricing created', variant: 'success');
        $this->redirect(route('staff.accommodation.pricing'), navigate: true);
    }

    public function types()
    {
        return DB::table('accommodation_types')
            ->whereNotIn('id', function ($q) {
                $q->select('accommodation_type_id')->from('accommodation_pricing');
            })
            ->orderBy('name')
            ->get();
    }
};
