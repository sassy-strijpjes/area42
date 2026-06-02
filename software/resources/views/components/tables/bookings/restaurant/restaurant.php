<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $view = 'day';

    public bool $showCancelled = false;
    public ?int $bookingToCancel = null;

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
                fn ($q) => $q->whereDate('booking_start', $this->currentDate),
                fn ($q) => $q->whereBetween(
                    'booking_start',
                    [
                        Carbon::parse($this->currentDate)->startOfWeek(),
                        Carbon::parse($this->currentDate)->endOfWeek(),
                    ]
                )
            )
            ->orderBy('booking_start')
            ->get();
    }

    public function getWeekDaysProperty()
    {
        $start = Carbon::parse($this->currentDate)->startOfWeek();

        return collect(range(0, 6))
            ->map(fn ($i) => $start->copy()->addDays($i));
    }

    public function cancelBooking(): void
    {
        if (! $this->bookingToCancel) {
            return;
        }

        DB::table('table_bookings')
            ->where('id', $this->bookingToCancel)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

        $this->bookingToCancel = null;

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Booking cancelled successfully.',
        ]);
    }

    public function confirmCancel(int $bookingId): void
    {
        $this->bookingToCancel = $bookingId;
    }
};
