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

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    public string $search = '';

    public function mount()
    {
        $this->prefix = request()->routeIs('admin.*') ? 'admin' : 'staff';
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
            ->when($this->search, function ($query) {
                $query->where('roles.name', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(5);
    }
};
