<?php

// US-PARK-01

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function createBookingType(string $name = 'Bungalow'): int
{
    return DB::table('accommodation_types')->insertGetId([
        'name'        => $name,
        'description' => null,
        'max_guests'  => 4,
        'amenities'   => null,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
}

function createBookingUnit(int $typeId, array $overrides = []): int
{
    return DB::table('accommodation_units')->insertGetId(array_merge([
        'accommodation_type_id' => $typeId,
        'name'                  => 'Bungalow B12',
        'status'                => 'available',
        'is_active'             => true,
        'notes'                 => null,
        'created_at'            => now(),
        'updated_at'            => now(),
    ], $overrides));
}

function createBooking(int $unitId, array $overrides = []): int
{
    return DB::table('accommodation_bookings')->insertGetId(array_merge([
        'unit_id'          => $unitId,
        'guest_name'       => 'John Doe',
        'guest_email'      => 'john@example.com',
        'guest_phone'      => '+31 6 12345678',
        'check_in'         => now()->addDay()->toDateString(),
        'check_out'        => now()->addDays(3)->toDateString(),
        'guests'           => 2,
        'special_requests' => null,
        'extras'           => null,
        'total_price'      => 200.00,
        'payment_status'   => 'pending',
        'status'           => 'confirmed',
        'created_at'       => now(),
        'updated_at'       => now(),
    ], $overrides));
}

function bookingStatus(int $bookingId): string
{
    return DB::table('accommodation_bookings')->find($bookingId)->status;
}

function unitStatus(int $unitId): string
{
    return DB::table('accommodation_units')->find($unitId)->status;
}

describe('Create', function () {
    it('inserts a new booking into the database', function () {
        $typeId = createBookingType();
        $unitId = createBookingUnit($typeId);

        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', $typeId)
            ->set('unit_id', $unitId)
            ->set('guest_name', 'Jane Smith')
            ->set('guest_email', 'jane@example.com')
            ->set('guest_phone', '+31 6 00000000')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'pending')
            ->call('create');

        $this->assertDatabaseHas('accommodation_bookings', [
            'guest_name'  => 'Jane Smith',
            'guest_email' => 'jane@example.com',
            'unit_id'     => $unitId,
            'status'      => 'confirmed',
        ]);
    });

    it('sets the unit status to occupied after booking', function () {
        $typeId = createBookingType();
        $unitId = createBookingUnit($typeId);

        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', $typeId)
            ->set('unit_id', $unitId)
            ->set('guest_name', 'Jane Smith')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'pending')
            ->call('create');

        expect(unitStatus($unitId))->toBe('occupied');
    });

    it('auto-assigns a unit when none is selected', function () {
        $typeId = createBookingType();
        createBookingUnit($typeId);

        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', $typeId)
            ->set('guest_name', 'Jane Smith')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'pending')
            ->call('create');

        $this->assertDatabaseHas('accommodation_bookings', [
            'guest_name' => 'Jane Smith',
            'status'     => 'confirmed',
        ]);
    });

    it('stores special requests when provided', function () {
        $typeId = createBookingType();
        $unitId = createBookingUnit($typeId);

        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', $typeId)
            ->set('unit_id', $unitId)
            ->set('guest_name', 'Jane Smith')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'pending')
            ->set('special_requests', 'Late check-in, ground floor preferred')
            ->call('create');

        $this->assertDatabaseHas('accommodation_bookings', [
            'guest_name'       => 'Jane Smith',
            'special_requests' => 'Late check-in, ground floor preferred',
        ]);
    });

    it('requires a guest name', function () {
        $typeId = createBookingType();
        $unitId = createBookingUnit($typeId);

        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', $typeId)
            ->set('unit_id', $unitId)
            ->set('guest_name', '')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'pending')
            ->call('create')
            ->assertHasErrors(['guest_name' => 'required']);
    });

    it('requires a valid accommodation type', function () {
        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', 9999)
            ->set('guest_name', 'Jane Smith')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'pending')
            ->call('create')
            ->assertHasErrors(['accommodation_type_id' => 'exists']);
    });

    it('requires check_out to be after check_in', function () {
        $typeId = createBookingType();
        $unitId = createBookingUnit($typeId);

        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', $typeId)
            ->set('unit_id', $unitId)
            ->set('guest_name', 'Jane Smith')
            ->set('check_in', now()->addDays(4)->toDateString())
            ->set('check_out', now()->addDay()->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'pending')
            ->call('create')
            ->assertHasErrors(['check_out' => 'after']);
    });

    it('requires a valid payment status', function () {
        $typeId = createBookingType();
        $unitId = createBookingUnit($typeId);

        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', $typeId)
            ->set('unit_id', $unitId)
            ->set('guest_name', 'Jane Smith')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'invalid')
            ->call('create')
            ->assertHasErrors(['payment_status' => 'in']);
    });

    it('rejects booking when no units are available', function () {
        $typeId = createBookingType();
        $unitId = createBookingUnit($typeId, ['status' => 'occupied']);

        createBooking($unitId, [
            'check_in'  => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'status'    => 'confirmed',
        ]);

        Livewire::test('forms.bookings.accommodation.create')
            ->set('accommodation_type_id', $typeId)
            ->set('guest_name', 'Jane Smith')
            ->set('check_in', now()->addDays(2)->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->set('payment_status', 'pending')
            ->call('create');

        // No new booking should have been created
        $this->assertDatabaseCount('accommodation_bookings', 1);
    });
});

describe('Edit', function () {
    it('loads existing booking data into the form', function () {
        $typeId    = createBookingType();
        $unitId    = createBookingUnit($typeId);
        $bookingId = createBooking($unitId, ['guest_name' => 'John Doe', 'payment_status' => 'partial']);

        Livewire::test('forms.bookings.accommodation.edit', ['booking' => $bookingId])
            ->assertSet('guest_name', 'John Doe')
            ->assertSet('unit_id', $unitId)
            ->assertSet('payment_status', 'partial');
    });

    it('updates the booking in the database', function () {
        $typeId    = createBookingType();
        $unitId    = createBookingUnit($typeId);
        $bookingId = createBooking($unitId);

        Livewire::test('forms.bookings.accommodation.edit', ['booking' => $bookingId])
            ->set('guest_name', 'Updated Name')
            ->set('guest_email', 'updated@example.com')
            ->set('payment_status', 'paid')
            ->set('check_in', now()->addDays(2)->toDateString())
            ->set('check_out', now()->addDays(5)->toDateString())
            ->call('update');

        $this->assertDatabaseHas('accommodation_bookings', [
            'id'             => $bookingId,
            'guest_name'     => 'Updated Name',
            'guest_email'    => 'updated@example.com',
            'payment_status' => 'paid',
        ]);
    });

    it('does not change booking status via the edit form', function () {
        $typeId    = createBookingType();
        $unitId    = createBookingUnit($typeId);
        $bookingId = createBooking($unitId, ['status' => 'confirmed']);

        Livewire::test('forms.bookings.accommodation.edit', ['booking' => $bookingId])
            ->set('guest_name', 'Updated Name')
            ->set('check_in', now()->addDays(2)->toDateString())
            ->set('check_out', now()->addDays(5)->toDateString())
            ->call('update');

        expect(bookingStatus($bookingId))->toBe('confirmed');
    });

    it('requires a guest name on update', function () {
        $typeId    = createBookingType();
        $unitId    = createBookingUnit($typeId);
        $bookingId = createBooking($unitId);

        Livewire::test('forms.bookings.accommodation.edit', ['booking' => $bookingId])
            ->set('guest_name', '')
            ->call('update')
            ->assertHasErrors(['guest_name' => 'required']);
    });

    it('excludes the current booking from its own conflict check', function () {
        $typeId    = createBookingType();
        $unitId    = createBookingUnit($typeId);
        $bookingId = createBooking($unitId, [
            'check_in'  => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]);

        Livewire::test('forms.bookings.accommodation.edit', ['booking' => $bookingId])
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(5)->toDateString())
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('accommodation_bookings', [
            'id'       => $bookingId,
            'check_out' => now()->addDays(5)->toDateString(),
        ]);
    });
});

describe('Cancellation', function () {
    it('cancels a confirmed booking', function () {
        $typeId    = createBookingType();
        $unitId    = createBookingUnit($typeId);
        $bookingId = createBooking($unitId, ['status' => 'confirmed']);

        Livewire::test('tables.bookings.accommodation')
            ->call('cancel', $bookingId);

        expect(bookingStatus($bookingId))->toBe('cancelled');
    });

    it('frees the unit when a booking is cancelled', function () {
        $typeId    = createBookingType();
        $unitId    = createBookingUnit($typeId, ['status' => 'occupied']);
        $bookingId = createBooking($unitId, ['status' => 'confirmed']);

        Livewire::test('tables.bookings.accommodation')
            ->call('cancel', $bookingId);

        expect(unitStatus($unitId))->toBe('available');
    });

    it('does nothing when booking id does not exist', function () {
        Livewire::test('tables.bookings.accommodation')
            ->call('cancel', 9999);

        // No exception, no rows changed
        $this->assertDatabaseCount('accommodation_bookings', 0);
    });
});


describe('Table', function () {
    it('renders without errors', function () {
        Livewire::test('tables.bookings.accommodation')->assertOk();
    });

    it('shows bookings with guest name, unit and type', function () {
        $typeId = createBookingType('Bungalow');
        $unitId = createBookingUnit($typeId, ['name' => 'B12']);
        createBooking($unitId, ['guest_name' => 'Alice Johnson']);

        Livewire::test('tables.bookings.accommodation')
            ->assertSee('Alice Johnson')
            ->assertSee('B12')
            ->assertSee('Bungalow');
    });

    it('filters results by guest name search', function () {
        $typeId = createBookingType();
        $unitA  = createBookingUnit($typeId, ['name' => 'Unit A']);
        $unitB  = createBookingUnit($typeId, ['name' => 'Unit B']);

        createBooking($unitA, ['guest_name' => 'Alice Johnson']);
        createBooking($unitB, ['guest_name' => 'Bob Williams']);

        Livewire::test('tables.bookings.accommodation')
            ->dispatch('accommodation-booking-search', value: 'Alice')
            ->assertSee('Alice Johnson')
            ->assertDontSee('Bob Williams');
    });

    it('filters results by unit name search', function () {
        $typeId = createBookingType();
        $unitA  = createBookingUnit($typeId, ['name' => 'Lakeside Cabin']);
        $unitB  = createBookingUnit($typeId, ['name' => 'Forest Lodge']);

        createBooking($unitA, ['guest_name' => 'Guest One']);
        createBooking($unitB, ['guest_name' => 'Guest Two']);

        Livewire::test('tables.bookings.accommodation')
            ->dispatch('accommodation-booking-search', value: 'Lakeside')
            ->assertSee('Guest One')
            ->assertDontSee('Guest Two');
    });

    it('search is case-insensitive', function () {
        $typeId = createBookingType();
        $unitId = createBookingUnit($typeId);
        createBooking($unitId, ['guest_name' => 'Alice Johnson']);

        Livewire::test('tables.bookings.accommodation')
            ->dispatch('accommodation-booking-search', value: 'alice johnson')
            ->assertSee('Alice Johnson');
    });

    it('shows empty state when no bookings exist', function () {
        Livewire::test('tables.bookings.accommodation')
            ->assertSee('No bookings found');
    });

    it('shows cancelled bookings with the correct status badge', function () {
        $typeId    = createBookingType();
        $unitId    = createBookingUnit($typeId);
        createBooking($unitId, ['guest_name' => 'Cancelled Guest', 'status' => 'cancelled']);

        Livewire::test('tables.bookings.accommodation')
            ->assertSee('Cancelled Guest')
            ->assertSee('Cancelled');
    });
});
