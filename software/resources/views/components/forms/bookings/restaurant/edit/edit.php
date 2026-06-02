<?php

use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    public $booking;

    public int $bookingId;

    #[Validate('required|exists:restaurant_tables,id')]
    public int $table_id;

    #[Validate('required|string|max:255')]
    public string $guest_name;

    #[Validate('nullable|string|max:255')]
    public string $guest_phone;

    #[Validate('required|integer|min:1')]
    public int $party_size = 1;

    #[Validate('required|date')]
    public string $booking_date;

    #[Validate('required|date_format:H:i')]
    public string $booking_time = '';

    #[Validate('nullable|date_format:H:i')]
    public string $booking_end_time = '';

    #[Validate('nullable|string')]
    public string $notes;

    public function mount($booking)
    {
        $bookingId = is_numeric($booking) ? $booking : ($booking->id ?? null);

        if (!$bookingId) {
            return;
        }

        $this->bookingId = $bookingId;

        $bookingData = DB::table('table_bookings')->find($bookingId);

        if ($bookingData) {
            $this->booking = $bookingData;
            $this->table_id = $bookingData->table_id;
            $this->guest_name = $bookingData->guest_name;
            $this->guest_phone = $bookingData->guest_phone;
            $this->party_size = $bookingData->party_size;
            $this->booking_date = $bookingData->booking_date;
            $this->booking_time = Carbon::parse($bookingData->booking_start)->format('H:i');
            $this->booking_end_time = $bookingData->booking_end ? Carbon::parse($bookingData->booking_end)->format('H:i') : '';
            $this->notes = $bookingData->notes;
        }
    }

    public function update(): void
    {
        $this->validate();
        $this->validateCapacity();

        DB::table('table_bookings')
            ->where('id', $this->bookingId)
            ->update([
                'table_id' => $this->table_id,
                'guest_name' => $this->guest_name,
                'guest_phone' => $this->guest_phone,
                'party_size' => $this->party_size,
                'booking_date' => $this->booking_date,
                'booking_start' => convert($this->booking_time),
                'booking_end' => $this->booking_end_time ? convert($this->booking_end_time) : null,
                'notes' => $this->notes,
                'updated_at' => now(),
            ]);

        Flux::toast('Booking updated', variant: 'success');

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
