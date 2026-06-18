<?php

// US-PARK-04

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function createType(string $name = 'Bungalow'): int
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

function createUnit(int $typeId, array $overrides = []): int
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

function isActive(int $unitId): bool
{
    return (bool) DB::table('accommodation_units')->find($unitId)->is_active;
}

describe('Create', function () {
    it('inserts a new unit into the database', function () {
        $typeId = createType();

        Livewire::test('forms.accommodation.units.create')
            ->set('accommodation_type_id', $typeId)
            ->set('name', 'Bungalow B12')
            ->set('status', 'available')
            ->call('create');

        $this->assertDatabaseHas('accommodation_units', [
            'name'                  => 'Bungalow B12',
            'accommodation_type_id' => $typeId,
            'status'                => 'available',
        ]);
    });

    it('defaults is_active to true on creation', function () {
        $typeId = createType();

        Livewire::test('forms.accommodation.units.create')
            ->set('accommodation_type_id', $typeId)
            ->set('name', 'Bungalow B12')
            ->set('status', 'available')
            ->call('create');

        $unit = DB::table('accommodation_units')->where('name', 'Bungalow B12')->first();

        expect((bool) $unit->is_active)->toBeTrue();
    });

    it('stores notes when provided', function () {
        $typeId = createType();

        Livewire::test('forms.accommodation.units.create')
            ->set('accommodation_type_id', $typeId)
            ->set('name', 'Chalet C3')
            ->set('status', 'available')
            ->set('notes', 'Near the lake')
            ->call('create');

        $this->assertDatabaseHas('accommodation_units', [
            'name'  => 'Chalet C3',
            'notes' => 'Near the lake',
        ]);
    });

    it('requires a name', function () {
        $typeId = createType();

        Livewire::test('forms.accommodation.units.create')
            ->set('accommodation_type_id', $typeId)
            ->set('name', '')
            ->call('create')
            ->assertHasErrors(['name' => 'required']);
    });

    it('requires a valid accommodation type', function () {
        Livewire::test('forms.accommodation.units.create')
            ->set('accommodation_type_id', 9999)
            ->set('name', 'Bungalow B12')
            ->call('create')
            ->assertHasErrors(['accommodation_type_id' => 'exists']);
    });

    it('requires a valid status', function () {
        $typeId = createType();

        Livewire::test('forms.accommodation.units.create')
            ->set('accommodation_type_id', $typeId)
            ->set('name', 'Bungalow B12')
            ->set('status', 'invalid-status')
            ->call('create')
            ->assertHasErrors(['status' => 'in']);
    });

    it('allows notes to be empty', function () {
        $typeId = createType();

        Livewire::test('forms.accommodation.units.create')
            ->set('accommodation_type_id', $typeId)
            ->set('name', 'Bungalow B12')
            ->set('status', 'available')
            ->set('notes', '')
            ->call('create')
            ->assertHasNoErrors(['notes']);
    });
});

describe('Edit', function () {
    it('loads existing unit data into the form', function () {
        $typeId = createType();
        $unitId = createUnit($typeId, ['name' => 'Chalet C3', 'status' => 'maintenance']);

        Livewire::test('forms.accommodation.units.edit', ['unit' => $unitId])
            ->assertSet('name', 'Chalet C3')
            ->assertSet('status', 'maintenance')
            ->assertSet('accommodation_type_id', $typeId);
    });

    it('updates the unit in the database', function () {
        $typeId    = createType();
        $newTypeId = createType('Chalet');
        $unitId    = createUnit($typeId);

        Livewire::test('forms.accommodation.units.edit', ['unit' => $unitId])
            ->set('name', 'Renamed B12')
            ->set('accommodation_type_id', $newTypeId)
            ->set('status', 'maintenance')
            ->set('notes', 'Needs new roof')
            ->call('update');

        $this->assertDatabaseHas('accommodation_units', [
            'id'                    => $unitId,
            'name'                  => 'Renamed B12',
            'accommodation_type_id' => $newTypeId,
            'status'                => 'maintenance',
            'notes'                 => 'Needs new roof',
        ]);
    });

    it('does not change is_active via the edit form', function () {
        $typeId = createType();
        $unitId = createUnit($typeId, ['is_active' => false]);

        Livewire::test('forms.accommodation.units.edit', ['unit' => $unitId])
            ->set('name', 'Bungalow B12')
            ->set('status', 'available')
            ->call('update');

        expect(isActive($unitId))->toBeFalse();
    });

    it('requires a name on update', function () {
        $typeId = createType();
        $unitId = createUnit($typeId);

        Livewire::test('forms.accommodation.units.edit', ['unit' => $unitId])
            ->set('name', '')
            ->call('update')
            ->assertHasErrors(['name' => 'required']);
    });
});

describe('Deactivation', function () {
    it('deactivates a unit that is available', function () {
        $typeId = createType();
        $unitId = createUnit($typeId, ['status' => 'available', 'is_active' => true]);

        Livewire::test('tables.accommodation.units')
            ->call('toggleActive', $unitId);

        expect(isActive($unitId))->toBeFalse();
    });

    it('reactivates an inactive unit regardless of status', function () {
        $typeId = createType();
        $unitId = createUnit($typeId, ['status' => 'occupied', 'is_active' => false]);

        Livewire::test('tables.accommodation.units')
            ->call('toggleActive', $unitId);

        expect(isActive($unitId))->toBeTrue();
    });

    it('cannot deactivate a unit that is occupied', function () {
        $typeId = createType();
        $unitId = createUnit($typeId, ['status' => 'occupied', 'is_active' => true]);

        Livewire::test('tables.accommodation.units')
            ->call('toggleActive', $unitId);

        expect(isActive($unitId))->toBeTrue();
    });

    it('cannot deactivate a unit under maintenance', function () {
        $typeId = createType();
        $unitId = createUnit($typeId, ['status' => 'maintenance', 'is_active' => true]);

        Livewire::test('tables.accommodation.units')
            ->call('toggleActive', $unitId);

        expect(isActive($unitId))->toBeTrue();
    });
});

describe('Table', function () {
    it('renders without errors', function () {
        Livewire::test('tables.accommodation.units')->assertOk();
    });

    it('shows units with their type name', function () {
        $typeId = createType('Bungalow');
        createUnit($typeId, ['name' => 'Bungalow B12']);

        Livewire::test('tables.accommodation.units')
            ->assertSee('Bungalow B12')
            ->assertSee('Bungalow');
    });

    it('filters results by unit name search', function () {
        $bungalowTypeId = createType('TypeA');
        $chaletTypeId   = createType('TypeB');

        createUnit($bungalowTypeId, ['name' => 'Bungalow B12']);
        createUnit($chaletTypeId,   ['name' => 'Chalet C3']);

        Livewire::test('tables.accommodation.units')
            ->dispatch('accommodation-unit-search', value: 'Bungalow B12')
            ->assertSee('Bungalow B12')
            ->assertDontSee('Chalet C3');
    });

    it('shows empty state when no units exist', function () {
        Livewire::test('tables.accommodation.units')
            ->assertSee('No units found');
    });
});
