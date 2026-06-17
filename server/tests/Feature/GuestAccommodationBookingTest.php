<?php

// US-CUS-03

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Mail\Booking\AccommodationConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function accomType(string $name = 'Bungalow', int $maxGuests = 4, array $amenities = []): int
{
    return DB::table('accommodation_types')->insertGetId([
        'name'        => $name,
        'description' => 'A comfortable ' . strtolower($name),
        'max_guests'  => $maxGuests,
        'amenities'   => $amenities ? json_encode($amenities) : null,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
}

function accomUnit(int $typeId, array $overrides = []): int
{
    return DB::table('accommodation_units')->insertGetId(array_merge([
        'accommodation_type_id' => $typeId,
        'name'                  => 'Unit A',
        'status'                => 'available',
        'is_active'             => true,
        'notes'                 => null,
        'created_at'            => now(),
        'updated_at'            => now(),
    ], $overrides));
}

function accomPricing(int $typeId, float $basePrice = 100.0): void
{
    DB::table('accommodation_pricing')->insert([
        'accommodation_type_id' => $typeId,
        'base_price'            => $basePrice,
        'created_at'            => now(),
        'updated_at'            => now(),
    ]);
}

function accomExtra(int $typeId, string $label = 'Breakfast', float $amount = 10.0, string $amountType = 'fixed'): int
{
    return DB::table('accommodation_extras')->insertGetId([
        'accommodation_type_id' => $typeId,
        'label'                 => $label,
        'amount_type'           => $amountType,
        'amount'                => $amount,
        'is_active'             => true,
        'created_at'            => now(),
        'updated_at'            => now(),
    ]);
}

function accomOccupyUnit(int $unitId, string $checkIn, string $checkOut): void
{
    DB::table('accommodation_bookings')->insert([
        'unit_id'        => $unitId,
        'guest_name'     => 'Existing Guest',
        'guest_email'    => 'existing@example.com',
        'guest_phone'    => null,
        'check_in'       => $checkIn,
        'check_out'      => $checkOut,
        'guests'         => 2,
        'special_requests' => null,
        'extras'         => null,
        'total_price'    => 200.00,
        'payment_status' => 'pending',
        'status'         => 'confirmed',
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
}

function submitGuestBooking(int $typeId, array $overrides = [])
{
    $data = array_merge([
        'check_in'        => now()->addDay()->toDateString(),
        'check_out'       => now()->addDays(4)->toDateString(),
        'guests'          => 2,
        'guest_name'      => 'Jane Doe',
        'guest_email'     => 'jane@example.com',
        'guest_phone'     => '',
        'special_requests' => '',
    ], $overrides);

    return Livewire::test('forms.book.accommodation')
        ->set('check_in', $data['check_in'])
        ->set('check_out', $data['check_out'])
        ->set('guests', $data['guests'])
        ->call('search')
        ->call('selectType', $typeId)
        ->set('guest_name', $data['guest_name'])
        ->set('guest_email', $data['guest_email'])
        ->set('guest_phone', $data['guest_phone'])
        ->set('special_requests', $data['special_requests'])
        ->call('book');
}

function makeAccomConfirmed(?string $specialRequests = null): AccommodationConfirmed
{
    return new AccommodationConfirmed(
        guestName:       'Jane Doe',
        unitName:        'Unit A',
        typeName:        'Bungalow',
        checkIn:         now()->addDay()->toDateString(),
        checkOut:        now()->addDays(4)->toDateString(),
        guests:          2,
        totalPrice:      300.00,
        paymentStatus:   'pending',
        specialRequests: $specialRequests,
    );
}

describe('Page', function () {
    it('is accessible at /book/accommodation', function () {
        $this->get(route('book.accommodation'))->assertOk();
    });
});

describe('Mount', function () {
    it('defaults check_in to today', function () {
        Livewire::test('forms.book.accommodation')
            ->assertSet('check_in', now()->toDateString());
    });

    it('defaults check_out to tomorrow', function () {
        Livewire::test('forms.book.accommodation')
            ->assertSet('check_out', now()->addDay()->toDateString());
    });

    it('defaults guests to 2', function () {
        Livewire::test('forms.book.accommodation')
            ->assertSet('guests', 2);
    });

    it('starts on the search step', function () {
        Livewire::test('forms.book.accommodation')
            ->assertSet('step', 'search');
    });
});

describe('Search', function () {
    it('moves to the results step on valid input', function () {
        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->assertSet('step', 'results');
    });

    it('requires check_in', function () {
        Livewire::test('forms.book.accommodation')
            ->set('check_in', '')
            ->set('check_out', now()->addDays(3)->toDateString())
            ->call('search')
            ->assertHasErrors(['check_in' => 'required']);
    });

    it('rejects a check_in date in the past', function () {
        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->subDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->call('search')
            ->assertHasErrors(['check_in']);
    });

    it('requires check_out to be after check_in', function () {
        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDays(3)->toDateString())
            ->set('check_out', now()->addDay()->toDateString())
            ->call('search')
            ->assertHasErrors(['check_out' => 'after']);
    });

    it('requires at least 1 guest', function () {
        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 0)
            ->call('search')
            ->assertHasErrors(['guests' => 'min']);
    });
});

describe('Results', function () {
    it('shows available accommodation types', function () {
        $typeId = accomType('Lakeside Cabin');
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->assertSee('Lakeside Cabin');
    });

    it('filters out types whose max_guests is below the guest count', function () {
        $typeId = accomType('Studio', maxGuests: 2);
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 4)
            ->call('search')
            ->assertDontSee('Studio');
    });

    it('filters out types with no units available for the date range', function () {
        $typeId = accomType('Occupied Lodge');
        $unitId = accomUnit($typeId);

        accomOccupyUnit($unitId, now()->addDay()->toDateString(), now()->addDays(5)->toDateString());

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDays(2)->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->assertDontSee('Occupied Lodge');
    });

    it('shows a type when only its overlapping booking is for adjacent dates', function () {
        $typeId = accomType('Adjacent Villa');
        $unitId = accomUnit($typeId);

        // Booking ends exactly when our search starts — no overlap
        accomOccupyUnit($unitId, now()->toDateString(), now()->addDay()->toDateString());

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->assertSee('Adjacent Villa');
    });

    it('shows multiple available types', function () {
        accomUnit(accomType('Bungalow A'));
        accomUnit(accomType('Bungalow B'));

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->assertSee('Bungalow A')
            ->assertSee('Bungalow B');
    });

    it('moves to the book step when a type is selected', function () {
        $typeId = accomType();
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->assertSet('step', 'book')
            ->assertSet('accommodation_type_id', $typeId);
    });

    it('shows the base price per night on the type card', function () {
        $typeId = accomType('Priced Lodge');
        accomUnit($typeId);
        accomPricing($typeId, 120.00);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->assertSee('120.00');
    });

    it('shows amenities on the type card', function () {
        $typeId = accomType('Amenity Lodge', amenities: ['WiFi', 'Pool']);
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->assertSee('WiFi')
            ->assertSee('Pool');
    });
});

describe('Navigation', function () {
    it('returns to search when backToSearch is called', function () {
        $typeId = accomType();
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->call('backToSearch')
            ->assertSet('step', 'search');
    });

    it('returns to results when backToResults is called', function () {
        $typeId = accomType();
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->call('backToResults')
            ->assertSet('step', 'results');
    });

    it('clears selected type when going back to results', function () {
        $typeId = accomType();
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->call('backToResults')
            ->assertSet('accommodation_type_id', 0);
    });
});

describe('Price breakdown', function () {
    it('calculates base price multiplied by nights', function () {
        $typeId = accomType();
        accomUnit($typeId);
        accomPricing($typeId, 100.0);

        $breakdown = Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString()) // 3 nights
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->get('priceBreakdown');

        expect($breakdown['total'])->toBe(300.0);
    });

    it('adds a fixed extra to the total', function () {
        $typeId  = accomType();
        $unitId  = accomUnit($typeId);
        accomPricing($typeId, 100.0);
        $extraId = accomExtra($typeId, 'Breakfast', 15.0);

        $breakdown = Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString()) // 2 nights = €200
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->set('selectedExtras', [$extraId])
            ->get('priceBreakdown');

        expect($breakdown['total'])->toBe(215.0); // 200 + 15
    });

    it('adds a percentage extra to the total', function () {
        $typeId  = accomType();
        accomUnit($typeId);
        accomPricing($typeId, 100.0);
        $extraId = accomExtra($typeId, 'Cleaning fee', 10.0, 'percentage');

        $breakdown = Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(3)->toDateString()) // 2 nights = €200
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->set('selectedExtras', [$extraId])
            ->get('priceBreakdown');

        expect($breakdown['total'])->toBe(220.0); // 200 + 10%
    });

    it('returns zero total when no dates are set', function () {
        $typeId = accomType();

        $breakdown = Livewire::test('forms.book.accommodation')
            ->set('accommodation_type_id', $typeId)
            ->set('check_in', '')
            ->set('check_out', '')
            ->get('priceBreakdown');

        expect($breakdown['total'])->toBe(0);
    });
});

describe('Book validation', function () {
    it('requires guest_name', function () {
        $typeId = accomType();
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->set('guest_name', '')
            ->set('guest_email', 'jane@example.com')
            ->call('book')
            ->assertHasErrors(['guest_name' => 'required']);
    });

    it('requires a valid email', function () {
        $typeId = accomType();
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'not-an-email')
            ->call('book')
            ->assertHasErrors(['guest_email' => 'email']);
    });

    it('requires guest_email', function () {
        $typeId = accomType();
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', '')
            ->call('book')
            ->assertHasErrors(['guest_email' => 'required']);
    });

    it('requires accommodation_type_id to be a valid type', function () {
        Livewire::test('forms.book.accommodation')
            ->set('accommodation_type_id', 9999)
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->call('book')
            ->assertHasErrors(['accommodation_type_id' => 'exists']);
    });

    it('requires at least 1 guest on the book step', function () {
        $typeId = accomType();
        accomUnit($typeId);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com')
            ->set('guests', 0)
            ->call('book')
            ->assertHasErrors(['guests' => 'min']);
    });
});

describe('Successful booking', function () {
    beforeEach(function () {
        Mail::fake();
        $this->typeId = accomType();
        $this->unitId = accomUnit($this->typeId);
        accomPricing($this->typeId, 100.0);
    });

    it('inserts a row into accommodation_bookings', function () {
        submitGuestBooking($this->typeId);

        $this->assertDatabaseHas('accommodation_bookings', [
            'guest_name'  => 'Jane Doe',
            'guest_email' => 'jane@example.com',
            'status'      => 'confirmed',
        ]);
    });

    it('sets the unit status to occupied', function () {
        submitGuestBooking($this->typeId);

        expect(
            DB::table('accommodation_units')->find($this->unitId)->status
        )->toBe('occupied');
    });

    it('stores the correct check-in and check-out dates', function () {
        $checkIn  = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(5)->toDateString();

        submitGuestBooking($this->typeId, [
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]);

        $this->assertDatabaseHas('accommodation_bookings', [
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]);
    });

    it('stores special requests when provided', function () {
        submitGuestBooking($this->typeId, ['special_requests' => 'Ground floor, no pets']);

        $this->assertDatabaseHas('accommodation_bookings', [
            'guest_name'       => 'Jane Doe',
            'special_requests' => 'Ground floor, no pets',
        ]);
    });

    it('stores selected extras as json on the booking', function () {
        $extraId = accomExtra($this->typeId, 'Breakfast', 10.0);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', now()->addDay()->toDateString())
            ->set('check_out', now()->addDays(4)->toDateString())
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $this->typeId)
            ->set('selectedExtras', [$extraId])
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com')
            ->call('book');

        $booking = DB::table('accommodation_bookings')->first();
        expect(json_decode($booking->extras, true))->toContain($extraId);
    });

    it('records payment_status as pending', function () {
        submitGuestBooking($this->typeId);

        $this->assertDatabaseHas('accommodation_bookings', [
            'guest_name'     => 'Jane Doe',
            'payment_status' => 'pending',
        ]);
    });

    it('sends a confirmation email to the guest', function () {
        submitGuestBooking($this->typeId);

        Mail::assertSent(
            AccommodationConfirmed::class,
            fn($mail) => $mail->hasTo('jane@example.com'),
        );
    });

    it('sends exactly one confirmation email', function () {
        submitGuestBooking($this->typeId);

        Mail::assertSentCount(1);
    });

    it('sets step to confirmed', function () {
        submitGuestBooking($this->typeId)->assertSet('step', 'confirmed');
    });

    it('snapshots booking details for the success screen', function () {
        $checkIn  = now()->addDay()->toDateString();
        $checkOut = now()->addDays(4)->toDateString();

        submitGuestBooking($this->typeId, [
            'check_in'   => $checkIn,
            'check_out'  => $checkOut,
            'guests'     => 3,
            'guest_email' => 'jane@example.com',
        ])
            ->assertSet('confirmedCheckIn', $checkIn)
            ->assertSet('confirmedCheckOut', $checkOut)
            ->assertSet('confirmedGuests', 3)
            ->assertSet('confirmedEmail', 'jane@example.com');
    });

    it('shows the success screen after booking', function () {
        submitGuestBooking($this->typeId)->assertSeeHtml("You're booked!");
    });
});

describe('Race condition', function () {
    it('adds an error if no unit is available at submit time', function () {
        Mail::fake();
        $typeId = accomType();
        $unitId = accomUnit($typeId);
        accomPricing($typeId, 100.0);

        $checkIn  = now()->addDay()->toDateString();
        $checkOut = now()->addDays(4)->toDateString();

        $component = Livewire::test('forms.book.accommodation')
            ->set('check_in', $checkIn)
            ->set('check_out', $checkOut)
            ->set('guests', 2)
            ->call('search')
            ->call('selectType', $typeId)
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com');

        // Another booking takes the unit between page load and submit
        accomOccupyUnit($unitId, $checkIn, $checkOut);
        DB::table('accommodation_units')->where('id', $unitId)->update(['status' => 'occupied']);

        $component->call('book')
            ->assertHasErrors('accommodation_type_id')
            ->assertSet('step', 'book');

        Mail::assertNothingSent();
    });

    it('does not insert a booking when no units are available', function () {
        Mail::fake();
        $typeId = accomType();
        $unitId = accomUnit($typeId, ['status' => 'occupied']);
        accomPricing($typeId, 100.0);

        $checkIn  = now()->addDay()->toDateString();
        $checkOut = now()->addDays(4)->toDateString();

        accomOccupyUnit($unitId, $checkIn, $checkOut);

        Livewire::test('forms.book.accommodation')
            ->set('check_in', $checkIn)
            ->set('check_out', $checkOut)
            ->set('guests', 2)
            ->set('accommodation_type_id', $typeId)
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com')
            ->call('book');

        $this->assertDatabaseCount('accommodation_bookings', 1); // only the seed booking
    });
});

describe('AccommodationConfirmed mailable', function () {
    it('uses the correct subject line', function () {
        $appName = config('app.name');

        expect(makeAccomConfirmed()->envelope()->subject)
            ->toBe("Your accommodation booking is confirmed – {$appName}");
    });

    it('renders the guest name in the email body', function () {
        makeAccomConfirmed()->assertSeeInHtml('Jane Doe');
    });

    it('renders the unit and type name', function () {
        makeAccomConfirmed()
            ->assertSeeInHtml('Unit A')
            ->assertSeeInHtml('Bungalow');
    });

    it('renders special requests when present', function () {
        makeAccomConfirmed('Late check-in')->assertSeeInHtml('Late check-in');
    });

    it('omits the special requests row when null', function () {
        makeAccomConfirmed()->assertDontSeeInHtml('Special requests');
    });
});
