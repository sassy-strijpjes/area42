<?php

// US-CUS-02

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Mail\Booking\RestaurantConfirmed;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * Insert a restaurant table and return id
 */
function createTable(int $capacity = 4, string $name = 'Table 1'): int
{
    return DB::table('restaurant_tables')->insertGetId([
        'name'       => $name,
        'capacity'   => $capacity,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * Block a slot on a given table so it appears unavailable
 */
function blockSlot(int $tableId, string $date, string $start, string $end): void
{
    DB::table('table_bookings')->insert([
        'table_id'      => $tableId,
        'guest_name'    => 'Existing Guest',
        'guest_phone'   => null,
        'booking_date'  => $date,
        'booking_start' => $start,
        'booking_end'   => $end,
        'party_size'    => 2,
        'notes'         => null,
        'status'        => 'confirmed',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);
}

/**
 * Return a future date string for the given weekday
 */
function nextWeekday(string $day, int $minDaysAhead = 1): string
{
    $date = Carbon::parse("next {$day}");

    if ($date->diffInDays(now(), false) >= -$minDaysAhead + 1) {
        $date->addWeek();
    }

    return $date->toDateString();
}

/**
 * Build a RestaurantConfirmed mailable for Jane Doe on the next Monday
 */
function makeRestaurantConfirmed(?string $notes = null): RestaurantConfirmed
{
    return new RestaurantConfirmed(
        'Jane Doe',
        Carbon::parse('next monday')->toDateString(),
        Carbon::parse('next monday 14:00')->format('H:i'),
        Carbon::parse('next monday 15:30')->format('H:i'),
        2,
        $notes,
    );
}

/**
 * Compute the expected 90-min slots for a given date
 */
function expectedSlots(string $date): array
{
    $openingHours = [
        0 => ['open' => '11:00', 'close' => '21:00'], // Sun
        1 => ['open' => '11:00', 'close' => '21:00'], // Mon
        2 => ['open' => '11:00', 'close' => '21:00'], // Tue
        3 => ['open' => '11:00', 'close' => '21:00'], // Wed
        4 => ['open' => '11:00', 'close' => '22:00'], // Thu
        5 => ['open' => '11:00', 'close' => '22:00'], // Fri
        6 => ['open' => '11:00', 'close' => '22:00'], // Sat
    ];

    $dow   = (int) Carbon::parse($date)->dayOfWeek;
    $hours = $openingHours[$dow];
    $open  = Carbon::parse("{$date} {$hours['open']}");
    $close = Carbon::parse("{$date} {$hours['close']}");

    $slots   = [];
    $current = $open->copy();

    while ($current->copy()->addMinutes(90)->lte($close)) {
        $slots[] = $current->format('H:i');
        $current->addMinutes(90);
    }

    return $slots;
}

/**
 * Submit a complete valid booking through the Livewire component
 */
function submitBooking(string $monday, array $overrides = [])
{
    $data = array_merge([
        'party_size'  => 2,
        'slot'        => '14:00',
        'guest_name'  => 'Jane Doe',
        'guest_email' => 'jane@example.com',
        'notes'       => '',
    ], $overrides);

    return Livewire::test('forms.book.restaurant')
        ->set('booking_date', $monday)
        ->set('party_size', $data['party_size'])
        ->call('selectSlot', $data['slot'])
        ->set('guest_name', $data['guest_name'])
        ->set('guest_email', $data['guest_email'])
        ->set('notes', $data['notes'])
        ->call('book');
}

describe('Page', function () {
    it('is accessible at /book/restaurant', function () {
        $this->get(route('book.restaurant'))->assertOk();
    });

    it('renders the correct view', function () {
        $this->get(route('book.restaurant'))->assertViewIs('book.restaurant');
    });

    it('mounts the Livewire component', function () {
        $this->get(route('book.restaurant'))
            ->assertOk()
            ->assertSee('forms.book.restaurant');
    });
});

describe('Mount', function () {
    it('defaults booking_date to today', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->assertSet('booking_date', now()->toDateString());
    });

    it('defaults party_size to 2', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->assertSet('party_size', 2);
    });

    it('picks up max party size from restaurant_tables', function () {
        createTable(capacity: 8, name: 'Large Table');

        Livewire::test('forms.book.restaurant')
            ->assertSet('maxPartySize', 8);
    });

    it('starts with confirmed = false', function () {
        Livewire::test('forms.book.restaurant')
            ->assertSet('confirmed', false);
    });
});

describe('Slot generation', function () {
    it('returns slots between opening and closing time', function () {
        createTable();

        $monday = nextWeekday('monday');
        $expected = expectedSlots($monday);

        $slots = Livewire::test('forms.book.restaurant')
            ->set('booking_date', $monday)
            ->get('availableSlots');

        // First and last slot must match the schedule exactly
        expect($slots)
            ->toContain($expected[0]) // first slot (11:00)
            ->toContain(end($expected)) // last valid slot
            ->not->toContain(
                Carbon::parse("{$monday} " . end($expected))
                    ->addMinutes(90)
                    ->format('H:i') // one step past closing
            );
    });

    it('clears the selected time when the date changes', function () {
        createTable();

        $monday  = nextWeekday('monday');
        $tuesday = nextWeekday('tuesday');

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', $monday)
            ->call('selectSlot', '12:30')
            ->set('booking_date', $tuesday)
            ->assertSet('booking_time', '');
    });

    it('clears the selected time when party size changes', function () {
        createTable(capacity: 4);

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->call('selectSlot', '12:30')
            ->set('party_size', 3)
            ->assertSet('booking_time', '');
    });

    it('excludes slots where an existing booking overlaps', function () {
        $tableId = createTable(capacity: 2);
        $monday  = nextWeekday('monday');

        blockSlot($tableId, $monday, '11:00:00', '12:30:00');

        $slots = Livewire::test('forms.book.restaurant')
            ->set('booking_date', $monday)
            ->set('party_size', 2)
            ->get('availableSlots');

        expect($slots)->not->toContain('11:00');
    });

    it('excludes slots where party size exceeds all table capacities', function () {
        createTable(capacity: 2);

        $slots = Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->set('party_size', 10)
            ->get('availableSlots');

        expect($slots)->toBeEmpty();
    });
});

describe('Slot selection', function () {
    it('sets booking_time when a slot is selected', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->call('selectSlot', '14:00')
            ->assertSet('booking_time', '14:00');
    });

    it('calculates booking_end_time 90 minutes after the selected slot', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->call('selectSlot', '14:00')
            ->assertSet('booking_end_time', '15:30');
    });
});

describe('Validation', function () {
    it('requires guest_name', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->call('selectSlot', '14:00')
            ->set('guest_name', '')
            ->set('guest_email', 'guest@example.com')
            ->call('book')
            ->assertHasErrors(['guest_name' => 'required']);
    });

    it('requires a valid email', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->call('selectSlot', '14:00')
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'not-an-email')
            ->call('book')
            ->assertHasErrors(['guest_email' => 'email']);
    });

    it('rejects a booking_date in the past', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', now()->subDay()->toDateString())
            ->set('booking_time', '14:00')
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com')
            ->call('book')
            ->assertHasErrors(['booking_date']);
    });

    it('requires booking_time to be selected', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com')
            ->call('book')
            ->assertHasErrors(['booking_time' => 'required']);
    });

    it('requires party_size to be at least 1', function () {
        createTable();

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->set('party_size', 2)
            ->call('selectSlot', '14:00')
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com')
            ->set('party_size', 0)
            ->call('book')
            ->assertHasErrors(['party_size' => 'min']);
    });

    it('allows notes to be empty', function () {
        Mail::fake();
        createTable(capacity: 4);

        Livewire::test('forms.book.restaurant')
            ->set('booking_date', nextWeekday('monday'))
            ->set('party_size', 2)
            ->call('selectSlot', '14:00')
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com')
            ->set('notes', '')
            ->call('book')
            ->assertHasNoErrors(['notes']);
    });
});

describe('Successful booking', function () {
    beforeEach(function () {
        Mail::fake();
        createTable(capacity: 4);
        $this->monday = nextWeekday('monday');
    });

    it('inserts a row into table_bookings', function () {
        submitBooking($this->monday);

        $this->assertDatabaseHas('table_bookings', [
            'guest_name'    => 'Jane Doe',
            'booking_date'  => $this->monday,
            'booking_start' => '14:00:00',
            'booking_end'   => '15:30:00',
            'party_size'    => 2,
            'status'        => 'confirmed',
        ]);
    });

    it('stores notes when provided', function () {
        submitBooking($this->monday, ['notes' => 'Window seat please']);

        $this->assertDatabaseHas('table_bookings', [
            'guest_name' => 'Jane Doe',
            'notes'      => 'Window seat please',
        ]);
    });

    it('sends a confirmation email to the guest', function () {
        submitBooking($this->monday);

        Mail::assertSent(
            RestaurantConfirmed::class,
            fn ($mail) => $mail->hasTo('jane@example.com'),
        );
    });

    it('sends exactly one confirmation email', function () {
        submitBooking($this->monday);

        Mail::assertSentCount(1);
    });

    it('sets confirmed = true after a successful booking', function () {
        submitBooking($this->monday)->assertSet('confirmed', true);
    });

    it('snapshots the booking details for the success screen', function () {
        submitBooking($this->monday, ['party_size' => 3, 'notes' => 'Birthday dinner'])
            ->assertSet('confirmedDate', $this->monday)
            ->assertSet('confirmedTime', '14:00')
            ->assertSet('confirmedPartySize', 3)
            ->assertSet('confirmedEmail', 'jane@example.com')
            ->assertSet('confirmedNotes', 'Birthday dinner');
    });

    it('resets the form fields after booking', function () {
        submitBooking($this->monday, ['notes' => 'Some note'])
            ->assertSet('guest_name', '')
            ->assertSet('guest_email', '')
            ->assertSet('booking_time', '')
            ->assertSet('notes', '');
    });

    it('shows the success screen after booking', function () {
        submitBooking($this->monday)->assertSeeHtml("You're all set!");
    });
});

describe('Race condition', function () {
    it('adds a booking_time error if the slot is taken between load and submit', function () {
        Mail::fake();
        $tableId = createTable(capacity: 2);
        $monday  = nextWeekday('monday');

        $component = Livewire::test('forms.book.restaurant')
            ->set('booking_date', $monday)
            ->set('party_size', 2)
            ->call('selectSlot', '14:00')
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com');

        // Another booking sneaks in for the only available table.
        blockSlot($tableId, $monday, '14:00:00', '15:30:00');

        $component->call('book')
            ->assertHasErrors('booking_time')
            ->assertSet('confirmed', false);

        Mail::assertNothingSent();
    });

    it('refreshes available slots after a race-condition failure', function () {
        Mail::fake();
        $tableId = createTable(capacity: 2);
        $monday  = nextWeekday('monday');

        $component = Livewire::test('forms.book.restaurant')
            ->set('booking_date', $monday)
            ->set('party_size', 2)
            ->call('selectSlot', '14:00')
            ->set('guest_name', 'Jane Doe')
            ->set('guest_email', 'jane@example.com');

        blockSlot($tableId, $monday, '14:00:00', '15:30:00');

        $component->call('book');

        expect($component->get('availableSlots'))->not->toContain('14:00');
    });
});

describe('RestaurantConfirmed mailable', function () {
    it('uses the correct subject line', function () {
        $appName = config('app.name');

        expect(makeRestaurantConfirmed()->envelope()->subject)
            ->toBe("Your table reservation is confirmed - {$appName}");
    });

    it('renders the guest name in the email body', function () {
        makeRestaurantConfirmed()->assertSeeInHtml('Jane Doe');
    });

    it('renders the notes row when notes are present', function () {
        makeRestaurantConfirmed('Window seat please')->assertSeeInHtml('Window seat please');
    });

    it('omits the notes row when notes are null', function () {
        makeRestaurantConfirmed()->assertDontSeeInHtml('Notes');
    });
});
