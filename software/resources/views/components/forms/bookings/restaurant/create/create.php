<?php

use App\Livewire\FormComponent;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;

new class extends FormComponent {

    #[Validate('required|exists:restaurant_tables,id')]
    public int $table_id = 0;

    #[Validate('required|string|max:255')]
    public string $guest_name = '';

    #[Validate('nullable|string|max:255')]
    public string $guest_phone = '';

    #[Validate('required|integer|min:1')]
    public int $party_size = 1;

    #[Validate('required|date')]
    public string $booking_date = '';

    #[Validate('required|string')]
    public string $booking_time = '';

    #[Validate('nullable|string')]
    public string $booking_end_time = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    public function mount()
    {
        $this->booking_date = now()->toDateString();
    }

    public function create(): void
    {
        $this->validate();

        $start = convert($this->booking_time);
        $end = $this->booking_end_time ? convert($this->booking_end_time) : null;

        $hasConflict = DB::table('table_bookings')
            ->where('table_id', $this->table_id)
            ->where('booking_date', $this->booking_date)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q) use ($start, $end) {
                    $q->where('booking_start', '<', $end ?? $start)
                        ->where('booking_end', '>', $start);
                });
            })
            ->exists();

        if ($hasConflict) {
            Flux::modal('confirm')->show();
            return;
        }

        $this->saveBooking('confirmed');
    }

    public function confirmWaitlist(): void
    {
        Flux::modal('confirm')->close();
        $this->saveBooking('waitlist');
    }

    private function saveBooking(string $status): void
    {
        Flux::modal('confirm')->close();

        $tableId = $status === 'confirmed'
            ? $this->findAvailableTable()?->id
            : null;

        DB::table('table_bookings')->insert([
            'table_id' => $tableId,
            'guest_name' => $this->guest_name,
            'guest_phone' => $this->guest_phone,
            'booking_date' => $this->booking_date,
            'booking_start' => convert($this->booking_time),
            'booking_end' => $this->booking_end_time ? convert($this->booking_end_time) : null,
            'party_size' => $this->party_size,
            'notes' => $this->notes,
            'status' => $status,
            'waitlisted_at' => $status === 'waitlist' ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Flux::toast(
            $status === 'confirmed'
                ? 'Booking confirmed'
                : 'Added to waitlist',
            variant: $status === 'confirmed' ? 'success' : 'info'
        );

        $this->redirect(route('staff.restaurant.bookings'), navigate: true);
    }

    public function tables()
    {
        return DB::table('restaurant_tables')
            ->orderBy('name')
            ->get();
    }
};
