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
        $booking = DB::table('table_bookings')->find($id);

        if (! $booking) {
            return;
        }

        DB::table('table_bookings')
            ->where('id', $id)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

        $this->promoteWaitlist($booking->booking_date);

        Flux::toast('Booking cancelled successfully.', variant: 'success');
    }

    public function promoteWaitlist(string $date): void
    {
        $next = DB::table('table_bookings')
            ->where('status', 'waitlist')
            ->where('booking_date', $date)
            ->orderBy('waitlisted_at')
            ->first();

        if (! $next) return;

        $table = DB::table('restaurant_tables')
            ->where('capacity', '>=', $next->party_size)
            ->whereNotExists(function ($q) use ($next) {
                $q->select(DB::raw(1))
                    ->from('table_bookings')
                    ->whereColumn('table_bookings.table_id', 'restaurant_tables.id')
                    ->where('booking_date', $next->booking_date)
                    ->where('status', 'confirmed');
            })
            ->orderBy('capacity')
            ->first();

        if (! $table) return;

        DB::table('table_bookings')
            ->where('id', $next->id)
            ->update([
                'table_id' => $table->id,
                'status' => 'confirmed',
                'waitlisted_at' => null,
            ]);
    }
};
