<?php

use App\Livewire\FormComponent;
use App\Mail\Booking\AccommodationUpdated;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;

new class extends FormComponent {

    public int $bookingId = 0;

    #[Validate('required|string|max:255')]
    public string $guest_name = '';

    #[Validate('nullable|email|max:255')]
    public string $guest_email = '';

    #[Validate('nullable|string|max:50')]
    public string $guest_phone = '';

    #[Validate('required|exists:accommodation_types,id')]
    public int $accommodation_type_id = 0;

    #[Validate('nullable|exists:accommodation_units,id')]
    public ?int $unit_id = null;

    #[Validate('required|date')]
    public string $check_in = '';

    #[Validate('required|date|after:check_in')]
    public string $check_out = '';

    #[Validate('required|integer|min:1')]
    public int $guests = 1;

    public array $selectedExtras = [];

    #[Validate('required|in:pending,paid,partial')]
    public string $payment_status = 'pending';

    #[Validate('nullable|string')]
    public string $special_requests = '';

    public function mount(int $booking): void
    {
        $this->bookingId = $booking;
        $b = DB::table('accommodation_bookings')
            ->join('accommodation_units', 'accommodation_bookings.unit_id', '=', 'accommodation_units.id')
            ->select('accommodation_bookings.*', 'accommodation_units.accommodation_type_id')
            ->where('accommodation_bookings.id', $booking)
            ->firstOrFail();

        $this->guest_name            = $b->guest_name;
        $this->guest_email           = $b->guest_email ?? '';
        $this->guest_phone           = $b->guest_phone ?? '';
        $this->accommodation_type_id = $b->accommodation_type_id;
        $this->unit_id               = $b->unit_id;
        $this->check_in              = $b->check_in;
        $this->check_out             = $b->check_out;
        $this->guests                = $b->guests;
        $this->payment_status        = $b->payment_status;
        $this->special_requests      = $b->special_requests ?? '';
        $this->selectedExtras        = json_decode($b->extras ?? '[]', true);
    }

    #[Computed]
    public function accommodationTypes()
    {
        return DB::table('accommodation_types')->orderBy('name')->get();
    }

    #[Computed]
    public function selectedType()
    {
        if (! $this->accommodation_type_id) return null;
        return DB::table('accommodation_types')->find($this->accommodation_type_id);
    }

    #[Computed]
    public function availableUnits()
    {
        if (! $this->accommodation_type_id || ! $this->check_in || ! $this->check_out) return collect();

        return DB::table('accommodation_units')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('is_active', true)
            ->where(fn($q) =>
            $q->where('status', 'available')
                ->orWhere('id', $this->unit_id)
            )
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('accommodation_bookings')
                    ->whereColumn('accommodation_bookings.unit_id', 'accommodation_units.id')
                    ->where('accommodation_bookings.id', '!=', $this->bookingId)
                    ->whereIn('accommodation_bookings.status', ['confirmed', 'checked_in'])
                    ->where('check_in',  '<', $this->check_out)
                    ->where('check_out', '>', $this->check_in);
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function extras()
    {
        if (! $this->accommodation_type_id) return collect();
        return DB::table('accommodation_extras')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('is_active', true)
            ->orderBy('label')
            ->get();
    }

    #[Computed]
    public function nights(): int
    {
        if (! $this->check_in || ! $this->check_out) return 0;
        return max(0, Carbon::parse($this->check_in)->diffInDays(Carbon::parse($this->check_out)));
    }

    #[Computed]
    public function priceBreakdown(): array
    {
        if (! $this->accommodation_type_id || $this->nights < 1) return ['lines' => [], 'total' => 0];

        $lines = []; $total = 0;
        $checkIn  = Carbon::parse($this->check_in);
        $checkOut = Carbon::parse($this->check_out);

        $bands = DB::table('accommodation_rate_bands')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('start_date', '<=', $this->check_out)
            ->where('end_date',   '>=', $this->check_in)
            ->orderBy('start_date')->get();

        $pricing   = DB::table('accommodation_pricing')->where('accommodation_type_id', $this->accommodation_type_id)->first();
        $basePrice = $pricing?->base_price ?? 0;

        if ($bands->isEmpty()) {
            $amt = $basePrice * $this->nights;
            $lines[] = ['label' => "{$this->nights} night(s) × €" . number_format($basePrice, 2), 'amount' => $amt];
            $total += $amt;
        } else {
            $dayCursor = $checkIn->copy(); $bandTotals = [];
            while ($dayCursor->lt($checkOut)) {
                $ds   = $dayCursor->toDateString();
                $band = $bands->first(fn($b) => $b->start_date <= $ds && $b->end_date >= $ds);
                $rate = $band ? $band->price_per_night : $basePrice;
                $key  = $band ? $band->label : 'Standard';
                $bandTotals[$key] = $bandTotals[$key] ?? ['nights' => 0, 'rate' => $rate];
                $bandTotals[$key]['nights']++;
                $dayCursor->addDay();
            }
            foreach ($bandTotals as $label => $data) {
                $amt = $data['nights'] * $data['rate'];
                $lines[] = ['label' => "{$data['nights']} night(s) × €" . number_format($data['rate'], 2) . " ({$label})", 'amount' => $amt];
                $total += $amt;
            }
        }

        $surcharges = DB::table('accommodation_surcharges')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('is_active', true)->get();
        foreach ($surcharges as $s) {
            if ($s->days_threshold && $this->nights < $s->days_threshold) continue;
            $amt = $s->amount_type === 'percentage' ? round($total * ($s->amount / 100), 2) : $s->amount;
            $lines[] = ['label' => $s->label, 'amount' => $amt]; $total += $amt;
        }

        if (! empty($this->selectedExtras)) {
            foreach (DB::table('accommodation_extras')->whereIn('id', $this->selectedExtras)->get() as $extra) {
                $amt = $extra->amount_type === 'percentage' ? round($total * ($extra->amount / 100), 2) : $extra->amount;
                $lines[] = ['label' => $extra->label, 'amount' => $amt]; $total += $amt;
            }
        }

        return ['lines' => $lines, 'total' => round($total, 2)];
    }

    public function updatedAccommodationTypeId(): void { $this->selectedExtras = []; }
    public function updatedCheckIn(): void
    {
        if ($this->check_out && $this->check_out <= $this->check_in) {
            $this->check_out = Carbon::parse($this->check_in)->addDay()->toDateString();
        }
    }

    public function update(): void
    {
        $this->validate();

        $breakdown = $this->priceBreakdown;

        DB::table('accommodation_bookings')
            ->where('id', $this->bookingId)
            ->update([
                'unit_id'          => $this->unit_id,
                'guest_name'       => $this->guest_name,
                'guest_email'      => $this->guest_email ?: null,
                'guest_phone'      => $this->guest_phone ?: null,
                'check_in'         => $this->check_in,
                'check_out'        => $this->check_out,
                'guests'           => $this->guests,
                'special_requests' => $this->special_requests ?: null,
                'extras'           => ! empty($this->selectedExtras) ? json_encode($this->selectedExtras) : null,
                'total_price'      => $breakdown['total'],
                'payment_status'   => $this->payment_status,
                'updated_at'       => now(),
            ]);

        if ($this->guest_email) {
            $unit = DB::table('accommodation_units')
                ->join('accommodation_types', 'accommodation_units.accommodation_type_id', '=', 'accommodation_types.id')
                ->select('accommodation_units.name as unit_name', 'accommodation_types.name as type_name')
                ->where('accommodation_units.id', $this->unit_id)
                ->first();

            Mail::to($this->guest_email)
                ->send(new AccommodationUpdated(
                    guestName:       $this->guest_name,
                    unitName:        $unit->unit_name,
                    typeName:        $unit->type_name,
                    checkIn:         $this->check_in,
                    checkOut:        $this->check_out,
                    guests:          $this->guests,
                    totalPrice:      $breakdown['total'],
                    paymentStatus:   $this->payment_status,
                    specialRequests: $this->special_requests ?: null,
                ));
        }

        Flux::toast('Booking updated.', variant: 'success');

        $this->redirect(route('staff.accommodation.bookings'), navigate: true);
    }
};
