<?php

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';

    public $sortDirection = 'desc';

    public string $search = '';

    #[On('staff-search')]
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

    #[Computed]
    public function staff()
    {
        return DB::table('staff')
            ->leftJoin('staff_roles', 'staff.id', '=', 'staff_roles.staff_id')
            ->leftJoin('roles', 'staff_roles.role_id', '=', 'roles.id')
            ->select('staff.*', 'roles.name as role_name')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('staff.name', 'like', "%{$this->search}%")
                        ->orWhere('staff.email', 'like', "%{$this->search}%")
                        ->orWhere('staff.type', 'like', "%{$this->search}%")
                        ->orWhere('roles.name', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(5);
    }
};
