<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    #[On('accommodation-unit-search')]
    public function updateSearch(string $value): void
    {
        $this->search = $value;
        $this->resetPage();
    }

    #[On('item-deleted')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleActive(int $id): void
    {
        $unit = DB::table('accommodation_units')->find($id);

        if ($unit->is_active && $unit->status !== 'available') {
            Flux::toast('Unit must be available before it can be deactivated.', variant: 'danger');
            return;
        }

        DB::table('accommodation_units')
            ->where('id', $id)
            ->update(['is_active' => ! $unit->is_active, 'updated_at' => now()]);

        Flux::toast(
            $unit->is_active ? 'Unit deactivated' : 'Unit activated',
            variant: $unit->is_active ? 'warning' : 'success',
        );
    }

    #[Computed]
    public function units()
    {
        return DB::table('accommodation_units')
            ->join('accommodation_types', 'accommodation_units.accommodation_type_id', '=', 'accommodation_types.id')
            ->select('accommodation_units.*', 'accommodation_types.name as type_name')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('accommodation_units.name', 'like', "%{$this->search}%")
                        ->orWhere('accommodation_types.name', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortBy === 'type_name' ? 'accommodation_types.name' : "accommodation_units.{$this->sortBy}", $this->sortDirection)
            ->paginate(10);
    }
};
