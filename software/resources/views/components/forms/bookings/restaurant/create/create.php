<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
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
        $this->validateCapacity();

        DB::table('table_bookings')->insert([
            'table_id' => $this->table_id,
            'guest_name' => $this->guest_name,
            'guest_phone' => $this->guest_phone,
            'booking_date' => $this->booking_date,
            'booking_start' => convert($this->booking_time),
            'booking_end' => $this->booking_end_time ? convert($this->booking_end_time) : null,
            'party_size' => $this->party_size,
            'notes' => $this->notes,
            'status' => 'confirmed',
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

    private function validateCapacity(): void
    {
        $table = DB::table('restaurant_tables')
            ->where('id', $this->table_id)
            ->first();

        if (! $table) {
            throw ValidationException::withMessages([
                'table_id' => 'Selected table does not exist.',
            ]);
        }

        if ($this->party_size > $table->capacity) {
            throw ValidationException::withMessages([
                'party_size' => "This table only seats {$table->capacity} guests.",
            ]);
        }
    }
};
