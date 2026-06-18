<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public string $search        = '';
    public string $sortBy        = 'name';
    public string $sortDirection = 'asc';

    #[On('accommodation-pricing-search')]
    public function updateSearch(string $value): void
    {
        $this->search = $value;
    }

    #[On('item-deleted')]
    public function refresh(): void {}

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $typeId): void
    {
        DB::table('accommodation_rate_bands')->where('accommodation_type_id', $typeId)->delete();
        DB::table('accommodation_surcharges')->where('accommodation_type_id', $typeId)->delete();
        DB::table('accommodation_extras')->where('accommodation_type_id', $typeId)->delete();
        DB::table('accommodation_pricing')->where('accommodation_type_id', $typeId)->delete();

        Flux::toast('Pricing rules removed', variant: 'success');
    }

    #[Computed]
    public function rows()
    {
        return DB::table('accommodation_types')
            ->leftJoin('accommodation_pricing', 'accommodation_types.id', '=', 'accommodation_pricing.accommodation_type_id')
            ->select('accommodation_types.id', 'accommodation_types.name', 'accommodation_pricing.base_price')
            ->when($this->search, fn($q) =>
            $q->where('accommodation_types.name', 'like', "%{$this->search}%")
            )
            ->orderBy('accommodation_types.' . $this->sortBy, $this->sortDirection)
            ->get()
            ->map(function ($row) {
                $row->band_count      = DB::table('accommodation_rate_bands')
                    ->where('accommodation_type_id', $row->id)->count();
                $row->surcharge_count = DB::table('accommodation_surcharges')
                    ->where('accommodation_type_id', $row->id)->where('is_active', true)->count();
                $row->extras_count    = DB::table('accommodation_extras')
                    ->where('accommodation_type_id', $row->id)->where('is_active', true)->count();
                $row->has_pricing     = $row->base_price !== null;
                return $row;
            });
    }
};
