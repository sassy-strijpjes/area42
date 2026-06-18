<?php

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

    #[On('accommodation-type-search')]
    public function updateSearch(string $value): void
    {
        $this->search = $value;
        $this->resetPage();
        unset($this->types);
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

    #[Computed]
    public function types()
    {
        return DB::table('accommodation_types')
            ->when($this->search, function ($query) {
                $term = '%' . strtolower($this->search) . '%';
                $query->where(fn($q) =>
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                );
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }
};
