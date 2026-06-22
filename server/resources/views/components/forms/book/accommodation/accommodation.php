<?php

use App\Mail\Booking\AccommodationConfirmed;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

new #[Layout('layouts::master')] class extends Component
{
    public string $check_in  = '';
    public string $check_out = '';
    public int    $guests    = 2;

    public int   $accommodation_type_id = 0;
    public array $selectedExtras        = [];

    public string $guest_name        = '';
    public string $guest_email       = '';
    public string $guest_phone       = '';
    public string $special_requests  = '';

    // search | results | book | confirmed
    public string $step = 'search';

    public string $confirmedTypeName = '';
    public string $confirmedUnitName = '';
    public string $confirmedEmail    = '';
    public string $confirmedCheckIn  = '';
    public string $confirmedCheckOut = '';
    public int    $confirmedGuests   = 0;
    public float  $confirmedTotal    = 0.0;

    public function mount(): void
    {
        $this->check_in  = now()->toDateString();
        $this->check_out = now()->addDay()->toDateString();
    }

    #[Computed]
    public function availableTypes()
    {
        if (! $this->check_in || ! $this->check_out || ! $this->guests) {
            return collect();
        }

        return DB::table('accommodation_types')
            ->where('max_guests', '>=', $this->guests)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('accommodation_units')
                    ->whereColumn('accommodation_units.accommodation_type_id', 'accommodation_types.id')
                    ->where('is_active', true)
                    ->whereNotExists(function ($q2) {
                        $q2->select(DB::raw(1))
                            ->from('accommodation_bookings')
                            ->whereColumn('accommodation_bookings.unit_id', 'accommodation_units.id')
                            ->whereIn('accommodation_bookings.status', ['confirmed', 'checked_in'])
                            ->where('check_in',  '<', $this->check_out)
                            ->where('check_out', '>', $this->check_in);
                    });
            })
            ->leftJoin('accommodation_pricing', 'accommodation_pricing.accommodation_type_id', '=', 'accommodation_types.id')
            ->select('accommodation_types.*', 'accommodation_pricing.base_price')
            ->orderBy('accommodation_types.name')
            ->get()
            ->map(function ($type) {
                $type->amenities = json_decode($type->amenities ?? '[]', true);
                return $type;
            });
    }

    #[Computed]
    public function selectedType()
    {
        if (! $this->accommodation_type_id) return null;

        $type = DB::table('accommodation_types')
            ->leftJoin('accommodation_pricing', 'accommodation_pricing.accommodation_type_id', '=', 'accommodation_types.id')
            ->select('accommodation_types.*', 'accommodation_pricing.base_price')
            ->where('accommodation_types.id', $this->accommodation_type_id)
            ->first();

        if ($type) {
            $type->amenities = json_decode($type->amenities ?? '[]', true);
        }

        return $type;
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
        if (! $this->accommodation_type_id || $this->nights < 1) {
            return ['lines' => [], 'total' => 0];
        }

        $lines    = [];
        $total    = 0;
        $checkIn  = Carbon::parse($this->check_in);
        $checkOut = Carbon::parse($this->check_out);

        $bands = DB::table('accommodation_rate_bands')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('start_date', '<=', $this->check_out)
            ->where('end_date',   '>=', $this->check_in)
            ->orderBy('start_date')
            ->get();

        $pricing   = DB::table('accommodation_pricing')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->first();
        $basePrice = $pricing?->base_price ?? 0;

        if ($bands->isEmpty()) {
            $amt     = $basePrice * $this->nights;
            $lines[] = ['label' => "{$this->nights} night(s) × €" . number_format($basePrice, 2), 'amount' => $amt];
            $total  += $amt;
        } else {
            $dayCursor  = $checkIn->copy();
            $bandTotals = [];
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
                $amt     = $data['nights'] * $data['rate'];
                $lines[] = ['label' => "{$data['nights']} night(s) × €" . number_format($data['rate'], 2) . " ({$label})", 'amount' => $amt];
                $total  += $amt;
            }
        }

        $surcharges = DB::table('accommodation_surcharges')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('is_active', true)
            ->get();

        foreach ($surcharges as $s) {
            if ($s->days_threshold && $this->nights < $s->days_threshold) continue;
            $amt     = $s->amount_type === 'percentage' ? round($total * ($s->amount / 100), 2) : $s->amount;
            $lines[] = ['label' => $s->label, 'amount' => $amt];
            $total  += $amt;
        }

        if (! empty($this->selectedExtras)) {
            foreach (DB::table('accommodation_extras')->whereIn('id', $this->selectedExtras)->get() as $extra) {
                $amt     = $extra->amount_type === 'percentage' ? round($total * ($extra->amount / 100), 2) : $extra->amount;
                $lines[] = ['label' => $extra->label, 'amount' => $amt];
                $total  += $amt;
            }
        }

        return ['lines' => $lines, 'total' => round($total, 2)];
    }

    public function search(): void
    {
        $this->validate([
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests'    => 'required|integer|min:1',
        ]);

        $this->accommodation_type_id = 0;
        $this->selectedExtras        = [];
        $this->step                  = 'results';
    }

    public function selectType(int $id): void
    {
        $this->accommodation_type_id = $id;
        $this->selectedExtras        = [];
        $this->step                  = 'book';
    }

    public function backToResults(): void
    {
        $this->accommodation_type_id = 0;
        $this->selectedExtras        = [];
        $this->step                  = 'results';
    }

    public function backToSearch(): void
    {
        $this->accommodation_type_id = 0;
        $this->selectedExtras        = [];
        $this->step                  = 'search';
    }

    public function book(): void
    {
        $this->validate([
            'guest_name'            => 'required|string|max:255',
            'guest_email'           => 'required|email|max:255',
            'guest_phone'           => 'nullable|string|max:50',
            'special_requests'      => 'nullable|string',
            'accommodation_type_id' => 'required|exists:accommodation_types,id',
            'guests'                => 'required|integer|min:1',
        ]);

        if ($this->nights < 1) {
            $this->addError('check_out', 'Check-out must be after check-in.');
            return;
        }

        $unitId = $this->resolveAvailableUnit();

        if (! $unitId) {
            $this->addError('accommodation_type_id', 'No units are available for these dates. Please go back and choose another type.');
            return;
        }

        $breakdown = $this->priceBreakdown;

        DB::table('accommodation_bookings')->insert([
            'unit_id'          => $unitId,
            'guest_name'       => $this->guest_name,
            'guest_email'      => $this->guest_email,
            'guest_phone'      => $this->guest_phone ?: null,
            'check_in'         => $this->check_in,
            'check_out'        => $this->check_out,
            'guests'           => $this->guests,
            'special_requests' => $this->special_requests ?: null,
            'extras'           => ! empty($this->selectedExtras) ? json_encode($this->selectedExtras) : null,
            'total_price'      => $breakdown['total'],
            'payment_status'   => 'pending',
            'status'           => 'confirmed',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('accommodation_units')
            ->where('id', $unitId)
            ->update(['status' => 'occupied', 'updated_at' => now()]);

        $unit = DB::table('accommodation_units')
            ->join('accommodation_types', 'accommodation_units.accommodation_type_id', '=', 'accommodation_types.id')
            ->select('accommodation_units.name as unit_name', 'accommodation_types.name as type_name')
            ->where('accommodation_units.id', $unitId)
            ->first();

        Mail::to($this->guest_email)->send(new AccommodationConfirmed(
            guestName:       $this->guest_name,
            unitName:        $unit->unit_name,
            typeName:        $unit->type_name,
            checkIn:         $this->check_in,
            checkOut:        $this->check_out,
            guests:          $this->guests,
            totalPrice:      $breakdown['total'],
            paymentStatus:   'pending',
            specialRequests: $this->special_requests ?: null,
        ));

        $this->confirmedTypeName = $unit->type_name;
        $this->confirmedUnitName = $unit->unit_name;
        $this->confirmedEmail    = $this->guest_email;
        $this->confirmedCheckIn  = $this->check_in;
        $this->confirmedCheckOut = $this->check_out;
        $this->confirmedGuests   = $this->guests;
        $this->confirmedTotal    = $breakdown['total'];

        $this->step = 'confirmed';
    }

    private function resolveAvailableUnit(): ?int
    {
        return DB::table('accommodation_units')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('is_active', true)
            ->where('status', 'available')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('accommodation_bookings')
                    ->whereColumn('accommodation_bookings.unit_id', 'accommodation_units.id')
                    ->whereIn('accommodation_bookings.status', ['confirmed', 'checked_in'])
                    ->where('check_in',  '<', $this->check_out)
                    ->where('check_out', '>', $this->check_in);
            })
            ->orderBy('name')
            ->value('id');
    }
};
