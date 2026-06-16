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

    public string $search        = '';
    public string $sortBy        = 'check_in';
    public string $sortDirection = 'asc';
    public string $filterStatus  = '';
    public string $filterType    = '';

    #[On('accommodation-booking-search')]
    public function updateSearch(string $value): void
    {
        $this->search = $value;
        $this->resetPage();
        unset($this->bookings);
    }

    #[On('item-deleted')]
    public function refresh(): void { $this->resetPage(); }

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
    public function bookings()
    {
        return DB::table('accommodation_bookings')
            ->join('accommodation_units', 'accommodation_bookings.unit_id', '=', 'accommodation_units.id')
            ->join('accommodation_types', 'accommodation_units.accommodation_type_id', '=', 'accommodation_types.id')
            ->select(
                'accommodation_bookings.*',
                'accommodation_units.name as unit_name',
                'accommodation_types.name as type_name',
            )
            ->when($this->search, function ($q) {
                $term = '%' . strtolower($this->search) . '%';
                $q->where(fn($q) =>
                $q->whereRaw('LOWER(accommodation_bookings.guest_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(accommodation_units.name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(accommodation_types.name) LIKE ?', [$term])
                );
            })
            ->when($this->filterStatus, fn($q) => $q->where('accommodation_bookings.status', $this->filterStatus))
            ->when($this->filterType,   fn($q) => $q->where('accommodation_units.accommodation_type_id', $this->filterType))
            ->orderBy(match($this->sortBy) {
                'unit_name'  => 'accommodation_units.name',
                'type_name'  => 'accommodation_types.name',
                default      => "accommodation_bookings.{$this->sortBy}",
            }, $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function accommodationTypes()
    {
        return DB::table('accommodation_types')->orderBy('name')->get();
    }

    public function cancel(int $id): void
    {
        $booking = DB::table('accommodation_bookings')->find($id);
        if (! $booking) return;

        DB::table('accommodation_bookings')
            ->where('id', $id)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        DB::table('accommodation_units')
            ->where('id', $booking->unit_id)
            ->update(['status' => 'available', 'updated_at' => now()]);

        Flux::toast('Booking cancelled.', variant: 'warning');
        Flux::modal('cancel-booking-' . $id)->close();

        $this->resetPage();
    }
};
