<?php

// US-PARK-04

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function createAccommodationType(array $overrides = []): int
{
    return DB::table('accommodation_types')->insertGetId(array_merge([
        'name'        => 'Bungalow',
        'description' => 'A cozy bungalow',
        'max_guests'  => 4,
        'amenities'   => json_encode(['WiFi', 'Kitchen']),
        'created_at'  => now(),
        'updated_at'  => now(),
    ], $overrides));
}

describe('Create', function () {
    it('inserts a new accommodation type into the database', function () {
        Livewire::test('forms.accommodation.types.create')
            ->set('name', 'Chalet')
            ->set('description', 'A mountain chalet')
            ->set('max_guests', 6)
            ->set('amenities', 'WiFi, Parking')
            ->call('create');

        $this->assertDatabaseHas('accommodation_types', [
            'name'        => 'Chalet',
            'description' => 'A mountain chalet',
            'max_guests'  => 6,
        ]);
    });

    it('stores amenities as json', function () {
        Livewire::test('forms.accommodation.types.create')
            ->set('name', 'Chalet')
            ->set('max_guests', 4)
            ->set('amenities', 'WiFi, Kitchen, Parking')
            ->call('create');

        $type = DB::table('accommodation_types')->where('name', 'Chalet')->first();

        expect(json_decode($type->amenities, true))
            ->toBe(['WiFi', 'Kitchen', 'Parking']);
    });

    it('stores null when amenities are empty', function () {
        Livewire::test('forms.accommodation.types.create')
            ->set('name', 'Camping pitch')
            ->set('max_guests', 2)
            ->set('amenities', '')
            ->call('create');

        $type = DB::table('accommodation_types')->where('name', 'Camping pitch')->first();

        expect($type->amenities)->toBeNull();
    });

    it('trims whitespace from individual amenities', function () {
        Livewire::test('forms.accommodation.types.create')
            ->set('name', 'Bungalow')
            ->set('max_guests', 4)
            ->set('amenities', '  WiFi  ,  Kitchen  ')
            ->call('create');

        $type = DB::table('accommodation_types')->where('name', 'Bungalow')->first();

        expect(json_decode($type->amenities, true))
            ->toBe(['WiFi', 'Kitchen']);
    });

    it('requires a name', function () {
        Livewire::test('forms.accommodation.types.create')
            ->set('name', '')
            ->set('max_guests', 4)
            ->call('create')
            ->assertHasErrors(['name' => 'required']);
    });

    it('requires max_guests', function () {
        Livewire::test('forms.accommodation.types.create')
            ->set('name', 'Bungalow')
            ->set('max_guests', 0)
            ->call('create')
            ->assertHasErrors(['max_guests' => 'min']);
    });

    it('requires max_guests to be at least 1', function () {
        Livewire::test('forms.accommodation.types.create')
            ->set('name', 'Bungalow')
            ->set('max_guests', 0)
            ->call('create')
            ->assertHasErrors(['max_guests']);
    });

    it('allows description to be empty', function () {
        Livewire::test('forms.accommodation.types.create')
            ->set('name', 'Bungalow')
            ->set('max_guests', 4)
            ->set('description', '')
            ->call('create')
            ->assertHasNoErrors(['description']);
    });
});

describe('Edit', function () {
    it('loads existing type data into the form', function () {
        $id = createAccommodationType(['name' => 'Chalet', 'max_guests' => 6]);

        Livewire::test('forms.accommodation.types.edit', ['type' => $id])
            ->assertSet('name', 'Chalet')
            ->assertSet('max_guests', 6);
    });

    it('populates amenities as a comma-separated string', function () {
        $id = createAccommodationType(['amenities' => json_encode(['WiFi', 'Kitchen'])]);

        $component = Livewire::test('forms.accommodation.types.edit', ['type' => $id]);

        expect($component->get('amenities'))->toContain('WiFi')->toContain('Kitchen');
    });

    it('updates the accommodation type in the database', function () {
        $id = createAccommodationType();

        Livewire::test('forms.accommodation.types.edit', ['type' => $id])
            ->set('name', 'Updated Bungalow')
            ->set('max_guests', 8)
            ->set('description', 'Now bigger')
            ->set('amenities', 'WiFi, Pool')
            ->call('update');

        $this->assertDatabaseHas('accommodation_types', [
            'id'          => $id,
            'name'        => 'Updated Bungalow',
            'max_guests'  => 8,
            'description' => 'Now bigger',
        ]);
    });

    it('stores updated amenities as json', function () {
        $id = createAccommodationType();

        Livewire::test('forms.accommodation.types.edit', ['type' => $id])
            ->set('name', 'Bungalow')
            ->set('max_guests', 4)
            ->set('amenities', 'WiFi, Pool, Sauna')
            ->call('update');

        $type = DB::table('accommodation_types')->find($id);

        expect(json_decode($type->amenities, true))
            ->toBe(['WiFi', 'Pool', 'Sauna']);
    });

    it('sets amenities to null when cleared', function () {
        $id = createAccommodationType();

        Livewire::test('forms.accommodation.types.edit', ['type' => $id])
            ->set('name', 'Bungalow')
            ->set('max_guests', 4)
            ->set('amenities', '')
            ->call('update');

        $type = DB::table('accommodation_types')->find($id);

        expect($type->amenities)->toBeNull();
    });

    it('requires a name on update', function () {
        $id = createAccommodationType();

        Livewire::test('forms.accommodation.types.edit', ['type' => $id])
            ->set('name', '')
            ->call('update')
            ->assertHasErrors(['name' => 'required']);
    });
});

describe('Table', function () {
    it('renders without errors', function () {
        Livewire::test('tables.accommodation.types')->assertOk();
    });

    it('shows accommodation types', function () {
        createAccommodationType(['name' => 'Bungalow']);
        createAccommodationType(['name' => 'Chalet']);

        Livewire::test('tables.accommodation.types')
            ->assertSee('Bungalow')
            ->assertSee('Chalet');
    });

    it('filters results by search', function () {
        createAccommodationType(['name' => 'Bungalow']);
        createAccommodationType(['name' => 'Chalet']);

        Livewire::test('tables.accommodation.types')
            ->dispatch('accommodation-type-search', value: 'Bungalow')
            ->assertSee('Bungalow')
            ->assertDontSee('Chalet');
    });

    it('shows empty state when no types exist', function () {
        Livewire::test('tables.accommodation.types')
            ->assertSee('No accommodation types found');
    });
});
