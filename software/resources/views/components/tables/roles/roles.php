<?php

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $prefix;

    public bool $isAdmin = false;

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public string $search = '';

    public function mount()
    {
        $this->prefix = request()->routeIs('admin.*') ? 'admin' : 'staff';
        $this->isAdmin = $this->prefix === 'admin';
    }

    #[On('roles-search')]
    public function updateSearch($value)
    {
        $this->search = $value;
        $this->resetPage();
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[On('item-deleted')]
    public function refreshRoles()
    {
        $this->resetPage();
    }

    #[Computed]
    public function roles()
    {
        return DB::table('roles')
            ->select('roles.*')
            ->when(
                $this->search,
                fn($q) => $q->where('roles.name', 'like', "%{$this->search}%")
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(5);
    }
};
