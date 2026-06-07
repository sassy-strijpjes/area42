<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

abstract class FormComponent extends Component
{
    public function validateBookingRules(): string
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

        // If user selected a table explicitly, check if it's available
        if ($this->table_id) {
            $conflict = DB::table('table_bookings')
                ->where('table_id', $this->table_id)
                ->where('booking_date', $this->booking_date)
                ->where('status', 'confirmed')
                ->when($this->bookingId ?? null, fn ($q) =>
                $q->where('id', '!=', $this->bookingId)
                )
                ->exists();

            if ($conflict) {
                return 'waitlist';
            }

            return 'confirmed';
        }

        return $this->findAvailableTable() ? 'confirmed' : 'waitlist';
    }

    public function findAvailableTable(): ?object
    {
        $start = convert($this->booking_time);
        $end = $this->booking_end_time ? convert($this->booking_end_time) : null;

        return DB::table('restaurant_tables')
            ->where('capacity', '>=', $this->party_size)
            ->whereNotExists(function ($q) use ($start, $end) {
                $q->select(DB::raw(1))
                    ->from('table_bookings')
                    ->whereColumn('table_bookings.table_id', 'restaurant_tables.id')
                    ->where('booking_date', $this->booking_date)
                    ->where('status', 'confirmed')
                    ->where(function ($q) use ($start, $end) {
                        $q->where('booking_start', '<', $end ?? $start)
                            ->where('booking_end', '>', $start);
                    });
            })
            ->orderBy('capacity')
            ->first();
    }
}
