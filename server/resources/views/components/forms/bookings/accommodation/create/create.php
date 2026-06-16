<?php

use App\Livewire\FormComponent;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;

new class extends FormComponent {

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

    public ?string $priceBreakdownJson = null;

    public function mount(): void
    {
        $this->check_in = now()->toDateString();
        $this->check_out = now()->addDay()->toDateString();
    }

    #[Computed]
    public function accommodationTypes()
    {
        return DB::table('accommodation_types')->orderBy('name')->get();
    }

    #[Computed]
    public function selectedType()
    {
        if (!$this->accommodation_type_id) return null;
        return DB::table('accommodation_types')->find($this->accommodation_type_id);
    }

    #[Computed]
    public function availableUnits()
    {
        if (!$this->accommodation_type_id || !$this->check_in || !$this->check_out) {
            return collect();
        }

        return DB::table('accommodation_units')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('is_active', true)
            ->where('status', 'available')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('accommodation_bookings')
                    ->whereColumn('accommodation_bookings.unit_id', 'accommodation_units.id')
                    ->whereIn('accommodation_bookings.status', ['confirmed', 'checked_in'])
                    ->where('check_in', '<', $this->check_out)
                    ->where('check_out', '>', $this->check_in);
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function extras()
    {
        if (!$this->accommodation_type_id) return collect();

        return DB::table('accommodation_extras')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('is_active', true)
            ->orderBy('label')
            ->get();
    }

    #[Computed]
    public function nights(): int
    {
        if (!$this->check_in || !$this->check_out) return 0;
        return max(0, Carbon::parse($this->check_in)->diffInDays(Carbon::parse($this->check_out)));
    }

    #[Computed]
    public function priceBreakdown(): array
    {
        if (!$this->accommodation_type_id || $this->nights < 1) {
            return ['lines' => [], 'total' => 0];
        }

        $lines = [];
        $total = 0;

        $checkIn = Carbon::parse($this->check_in);
        $checkOut = Carbon::parse($this->check_out);

        $bands = DB::table('accommodation_rate_bands')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->where('start_date', '<=', $this->check_out)
            ->where('end_date', '>=', $this->check_in)
            ->orderBy('start_date')
            ->get();

        $pricing = DB::table('accommodation_pricing')
            ->where('accommodation_type_id', $this->accommodation_type_id)
            ->first();

        $basePrice = $pricing?->base_price ?? 0;

        if ($bands->isEmpty()) {
            $nightlyTotal = $basePrice * $this->nights;
            $lines[] = ['label' => "{$this->nights} night(s) × €" . number_format($basePrice, 2), 'amount' => $nightlyTotal];
            $total += $nightlyTotal;
        } else {
            $dayCursor = $checkIn->copy();
            $bandTotals = [];

            while ($dayCursor->lt($checkOut)) {
                $dateStr = $dayCursor->toDateString();
                $band = $bands->first(
                    fn($b) => $b->start_date <= $dateStr && $b->end_date >= $dateStr
                );
                $rate = $band ? $band->price_per_night : $basePrice;
                $key = $band ? $band->label : 'Standard';
                $bandTotals[$key] = ($bandTotals[$key] ?? ['nights' => 0, 'rate' => $rate]);
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
            ->where('is_active', true)
            ->get();

        foreach ($surcharges as $s) {
            if ($s->days_threshold && $this->nights < $s->days_threshold) continue;

            $amt = $s->amount_type === 'percentage'
                ? round($total * ($s->amount / 100), 2)
                : $s->amount;

            $lines[] = ['label' => $s->label, 'amount' => $amt];
            $total += $amt;
        }

        if (!empty($this->selectedExtras)) {
            $selectedExtraRows = DB::table('accommodation_extras')
                ->whereIn('id', $this->selectedExtras)
                ->get();

            foreach ($selectedExtraRows as $extra) {
                $amt = $extra->amount_type === 'percentage'
                    ? round($total * ($extra->amount / 100), 2)
                    : $extra->amount;

                $lines[] = ['label' => $extra->label, 'amount' => $amt];
                $total += $amt;
            }
        }

        return ['lines' => $lines, 'total' => round($total, 2)];
    }

    public function updatedAccommodationTypeId(): void
    {
        $this->unit_id = null;
        $this->selectedExtras = [];
        unset($this->guests);
    }

    public function updatedCheckIn(): void
    {
        if ($this->check_out && $this->check_out <= $this->check_in) {
            $this->check_out = Carbon::parse($this->check_in)->addDay()->toDateString();
        }
        $this->unit_id = null;
    }

    public function updatedCheckOut(): void
    {
        $this->unit_id = null;
    }

    public function create(): void
    {
        $this->validate();

        if ($this->nights < 1) {
            Flux::toast('Check-out must be after check-in.', variant: 'danger');
            return;
        }

        $unitId = $this->unit_id ?: $this->resolveAvailableUnit();

        if (! $unitId) {
            Flux::toast('No available units for the selected dates.', variant: 'danger');
            return;
        }

        $breakdown = $this->priceBreakdown;

        DB::table('accommodation_bookings')->insert([
            'unit_id'          => $unitId,
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
            'status'           => 'confirmed',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('accommodation_units')
            ->where('id', $unitId)
            ->update(['status' => 'occupied', 'updated_at' => now()]);

        Flux::toast('Accommodation booking created.', variant: 'success');

        $this->redirect(route('staff.accommodation.bookings'), navigate: true);
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
