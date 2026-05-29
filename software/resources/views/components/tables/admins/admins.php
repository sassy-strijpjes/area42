<?php

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public int $adminId = 0;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public string $search = '';

    public function mount()
    {
        $this->adminId = (int) (user()?->id ?? 0);
    }

    #[On('admins-search')]
    public function updateSearch($value)
    {
        $this->search = $value;
        $this->resetPage();
    }

    #[On('item-deleted')]
    public function refreshAdmins()
    {
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
    public function admins()
    {
        return DB::table('admins')
            ->where('admins.id', '!=', $this->adminId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('admins.name', 'like', "%{$this->search}%")
                        ->orWhere('admins.email', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(5);
    }
};
