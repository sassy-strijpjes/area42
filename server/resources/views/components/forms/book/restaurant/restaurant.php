<?php

use App\Livewire\FormComponent;
use App\Mail\Booking\RestaurantConfirmed;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

new class extends FormComponent
{
    #[Validate('required|string|max:255')]
    public string $guest_name = '';

    #[Validate('required|email|max:255')]
    public string $guest_email = '';

    #[Validate('required|date|after_or_equal:today')]
    public string $booking_date = '';

    #[Validate('required|string')]
    public string $booking_time = '';

    public string $booking_end_time = '';

    #[Validate('required|integer|min:1')]
    public int $party_size = 2;

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    public bool $confirmed = false;

    public array $availableSlots = [];

    public int $maxPartySize = 20;

    // Snapshot of confirmed booking for the success screen
    public string $confirmedDate = '';
    public string $confirmedTime = '';
    public int    $confirmedPartySize = 0;
    public string $confirmedEmail = '';
    public string $confirmedNotes = '';

    protected int $slotDuration = 90;

    protected array $openingHours = [
        0 => ['open' => '11:00', 'close' => '21:00'], // Sun
        1 => ['open' => '11:00', 'close' => '21:00'], // Mon
        2 => ['open' => '11:00', 'close' => '21:00'], // Tue
        3 => ['open' => '11:00', 'close' => '21:00'], // Wed
        4 => ['open' => '11:00', 'close' => '22:00'], // Thu
        5 => ['open' => '11:00', 'close' => '22:00'], // Fri
        6 => ['open' => '11:00', 'close' => '22:00'], // Sat
    ];

    public function mount(): void
    {
        $this->maxPartySize   = (int) DB::table('restaurant_tables')->max('capacity') ?: 20;
        $this->booking_date   = now()->toDateString();
        $this->availableSlots = $this->computeSlots();
    }

    public function updatedBookingDate(): void
    {
        $this->booking_time   = '';
        $this->availableSlots = $this->computeSlots();
    }

    public function updatedPartySize(): void
    {
        $this->booking_time   = '';
        $this->availableSlots = $this->computeSlots();
    }

    public function selectSlot(string $slot): void
    {
        $this->booking_time     = $slot;
        $this->booking_end_time = Carbon::parse($this->booking_date . ' ' . $slot)
            ->addMinutes($this->slotDuration)
            ->format('H:i');
    }

    protected function computeSlots(): array
    {
        if (! $this->booking_date || ! $this->party_size) {
            return [];
        }

        $dow = (int) Carbon::parse($this->booking_date)->dayOfWeek;

        if (! isset($this->openingHours[$dow])) {
            return [];
        }

        $hours   = $this->openingHours[$dow];
        $open    = Carbon::parse($this->booking_date . ' ' . $hours['open']);
        $close   = Carbon::parse($this->booking_date . ' ' . $hours['close']);
        $slots   = [];
        $current = $open->copy();

        while ($current->copy()->addMinutes($this->slotDuration)->lte($close)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($this->slotDuration);
        }

        // Pass each slot explicitly so we never mutate $this->booking_time
        return array_values(array_filter(
            $slots,
            fn (string $slot) => $this->resolveTable($slot) !== null
        ));
    }

    protected function resolveTable(?string $slot = null): ?object
    {
        $time  = $slot ?? $this->booking_time;
        $start = Carbon::parse($this->booking_date . ' ' . $time)->format('H:i:s');
        $end   = Carbon::parse($this->booking_date . ' ' . $time)
            ->addMinutes($this->slotDuration)
            ->format('H:i:s');

        return DB::table('restaurant_tables')
            ->where('capacity', '>=', $this->party_size)
            ->whereNotExists(function ($q) use ($start, $end) {
                $q->from('table_bookings')
                    ->whereColumn('table_bookings.table_id', 'restaurant_tables.id')
                    ->where('booking_date', $this->booking_date)
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->where('booking_start', '<', $end)
                    ->where('booking_end', '>', $start);
            })
            ->orderBy('capacity')
            ->first();
    }

    public function book(): void
    {
        $this->validate();

        $table = $this->resolveTable();

        if (! $table) {
            $this->addError('booking_time', 'This slot is no longer available. Please pick another time.');
            $this->booking_time   = '';
            $this->availableSlots = $this->computeSlots(); // Refresh without touching booking_time
            return;
        }

        $slotStart = Carbon::parse($this->booking_date . ' ' . $this->booking_time);
        $slotEnd   = $slotStart->copy()->addMinutes($this->slotDuration);

        DB::table('table_bookings')->insert([
            'table_id'      => $table->id,
            'guest_name'    => $this->guest_name,
            'guest_phone'   => null,
            'booking_date'  => $this->booking_date,
            'booking_start' => $slotStart->format('H:i:s'),
            'booking_end'   => $slotEnd->format('H:i:s'),
            'party_size'    => $this->party_size,
            'notes'         => $this->notes ?: null,
            'status'        => 'confirmed',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        Mail::to($this->guest_email, $this->guest_name)->send(new RestaurantConfirmed(
            $this->guest_name,
            $this->booking_date,
            $slotStart,
            $slotEnd,
            $this->party_size,
            $this->notes ?: null,
        ));

        // Snapshot what we need for the success screen before resetting
        $this->confirmedDate      = $this->booking_date;
        $this->confirmedTime      = $this->booking_time;
        $this->confirmedPartySize = $this->party_size;
        $this->confirmedEmail     = $this->guest_email;
        $this->confirmedNotes     = $this->notes;

        // Reset the form
        $this->reset(['guest_name', 'guest_email', 'booking_time', 'booking_end_time', 'notes']);
        $this->booking_date   = now()->toDateString();
        $this->party_size     = 2;
        $this->availableSlots = $this->computeSlots();
        $this->resetValidation();

        $this->confirmed = true;
    }
};
