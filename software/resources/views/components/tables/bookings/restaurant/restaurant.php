<?php

use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $view = 'day';

    public ?object $selectedBooking = null;

    public bool $showNotesModal = false;

    public bool $showCancelled = false;

    public string $currentDate;

    public function mount()
    {
        $this->currentDate = now()->toDateString();
    }

    public function previous()
    {
        $this->currentDate = $this->view === 'day'
            ? Carbon::parse($this->currentDate)->subDay()->toDateString()
            : Carbon::parse($this->currentDate)->subWeek()->toDateString();
    }

    public function next()
    {
        $this->currentDate = $this->view === 'day'
            ? Carbon::parse($this->currentDate)->addDay()->toDateString()
            : Carbon::parse($this->currentDate)->addWeek()->toDateString();
    }

    #[Computed]
    public function tables()
    {
        return DB::table('restaurant_tables')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function bookings()
    {
        return DB::table('table_bookings')
            ->join(
                'restaurant_tables',
                'table_bookings.table_id',
                '=',
                'restaurant_tables.id'
            )
            ->select(
                'table_bookings.*',
                'restaurant_tables.name as table_name'
            )
            ->when(
                ! $this->showCancelled,
                fn ($q) => $q->where('table_bookings.status', '!=', 'cancelled')
            )
            ->when(
                $this->view === 'day',
                fn ($q) => $q->where('booking_date', $this->currentDate),
                fn ($q) => $q->whereBetween('booking_date', [
                    Carbon::parse($this->currentDate)->startOfWeek(),
                    Carbon::parse($this->currentDate)->endOfWeek(),
                ])
            )
            ->orderBy('booking_start')
            ->get();
    }

    public function weekDays(): Collection
    {
        $start = Carbon::parse($this->currentDate)->startOfWeek();

        return collect(range(0, 6))
            ->map(fn ($i) => $start->copy()->addDays($i));
    }

    public function openNotes(int $id): void
    {
        $this->selectedBooking = DB::table('table_bookings')->find($id);
        $this->showNotesModal = true;
    }

    public function cancel(int $id): void
    {
        DB::table('table_bookings')
            ->where('id', $id)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

        Flux::toast('Booking cancelled successfully.', variant: 'success');
    }
};
