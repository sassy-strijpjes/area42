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

    public string $sortBy = 'level';

    public string $sortDirection = 'asc';

    public string $search = '';
    public bool $editingHierarchy = false;

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
    public function canEditHierarchy(): bool
    {
        return $this->isAdmin;
    }

    public function updateHierarchy(array $orderedIds): void
    {
        abort_unless($this->isAdmin, 403);

        foreach ($orderedIds as $level => $id) {
            DB::table('roles')
                ->where('id', $id)
                ->update(['level' => $level + 1]);
        }
    }

    #[Computed]
    public function roles()
    {
        return DB::table('roles')
            ->select('roles.*')
            ->when(
                !$this->isAdmin,
                fn($q) => $q->where('roles.level', '>', roleLevel())
            )
            ->when(
                $this->search,
                fn($q) => $q->where('roles.name', 'like', "%{$this->search}%")
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(5);
    }

    #[Computed]
    public function allRoles()
    {
        return DB::table('roles')
            ->when(
                !$this->isAdmin,
                fn($q) => $q->where('level', '>', roleLevel())
            )
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }
};
