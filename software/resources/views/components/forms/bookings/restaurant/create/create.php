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
        $status = $this->validateBookingRules();
        $table = $status === 'confirmed'
            ? $this->findAvailableTable()
            : null;

        DB::table('table_bookings')->insert([
            'table_id' => $table?->id,
            'guest_name' => $this->guest_name,
            'guest_phone' => $this->guest_phone,
            'booking_date' => $this->booking_date,
            'booking_start' => convert($this->booking_time),
            'booking_end' => $this->booking_end_time ? convert($this->booking_end_time) : null,
            'party_size' => $this->party_size,
            'notes' => $this->notes,
            'status' => $table ? 'confirmed' : 'waitlist',
            'waitlisted_at' => $table ? null : now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Flux::toast('Booking created', variant: 'success');

        $this->redirect(route('staff.restaurant.bookings'), navigate: true);
    }

    public function tables()
    {
        return DB::table('restaurant_tables')
            ->orderBy('name')
            ->get();
    }
};
