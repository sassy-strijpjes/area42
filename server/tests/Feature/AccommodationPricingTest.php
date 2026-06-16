<?php

// US-PARK-05

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function createPricingType(string $name = 'Bungalow'): int
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

function createPricing(int $typeId, float $basePrice = 100.00): int
{
    return DB::table('accommodation_pricing')->insertGetId([
        'accommodation_type_id' => $typeId,
        'base_price'            => $basePrice,
        'created_at'            => now(),
        'updated_at'            => now(),
    ]);
}

function createRateBand(int $typeId, array $overrides = []): int
{
    return DB::table('accommodation_rate_bands')->insertGetId(array_merge([
        'accommodation_type_id' => $typeId,
        'label'                 => 'Peak',
        'season'                => 'summer',
        'start_date'            => '2026-07-01',
        'end_date'              => '2026-08-31',
        'price_per_night'       => 150.00,
        'created_at'            => now(),
        'updated_at'            => now(),
    ], $overrides));
}

function createSurcharge(int $typeId, array $overrides = []): int
{
    return DB::table('accommodation_surcharges')->insertGetId(array_merge([
        'accommodation_type_id' => $typeId,
        'type'                  => 'cleaning',
        'label'                 => 'Cleaning fee',
        'amount_type'           => 'fixed',
        'amount'                => 25.00,
        'days_threshold'        => null,
        'is_active'             => true,
        'created_at'            => now(),
        'updated_at'            => now(),
    ], $overrides));
}

function createExtra(int $typeId, array $overrides = []): int
{
    return DB::table('accommodation_extras')->insertGetId(array_merge([
        'accommodation_type_id' => $typeId,
        'label'                 => 'Breakfast',
        'amount_type'           => 'fixed',
        'amount'                => 15.00,
        'is_active'             => true,
        'created_at'            => now(),
        'updated_at'            => now(),
    ], $overrides));
}

describe('Table', function () {
    it('renders without errors', function () {
        Livewire::test('tables.accommodation.pricing')->assertOk();
    });

    it('shows all accommodation types even without pricing', function () {
        createPricingType();
        createPricingType('Chalet');

        Livewire::test('tables.accommodation.pricing')
            ->assertSee('Bungalow')
            ->assertSee('Chalet');
    });

    it('shows base price when pricing is set', function () {
        $typeId = createPricingType();
        createPricing($typeId, 99.50);

        Livewire::test('tables.accommodation.pricing')
            ->assertSee('99.50');
    });

    it('shows Not set when no pricing exists for a type', function () {
        createPricingType();

        Livewire::test('tables.accommodation.pricing')
            ->assertSee('Not set');
    });

    it('shows the correct rate band count', function () {
        $typeId = createPricingType();
        createPricing($typeId);
        createRateBand($typeId);
        createRateBand($typeId, ['label' => 'Low season']);

        $rows = Livewire::test('tables.accommodation.pricing')->instance()->rows;

        expect($rows->first()->band_count)->toBe(2);
    });

    it('shows the correct surcharge count for active surcharges only', function () {
        $typeId = createPricingType();
        createPricing($typeId);
        createSurcharge($typeId);
        createSurcharge($typeId, ['label' => 'City tax', 'is_active' => false]);

        $rows = Livewire::test('tables.accommodation.pricing')->instance()->rows;

        expect($rows->first()->surcharge_count)->toBe(1);
    });

    it('shows the correct extras count for active extras only', function () {
        $typeId = createPricingType();
        createPricing($typeId);
        createExtra($typeId);
        createExtra($typeId, ['label' => 'Bike rental', 'is_active' => false]);

        $rows = Livewire::test('tables.accommodation.pricing')->instance()->rows;

        expect($rows->first()->extras_count)->toBe(1);
    });

    it('filters results by type name search', function () {
        createPricingType();
        createPricingType('Chalet');

        Livewire::test('tables.accommodation.pricing')
            ->dispatch('accommodation-pricing-search', value: 'Bungalow')
            ->assertSee('Bungalow')
            ->assertDontSee('Chalet');
    });

    it('shows empty state when no types exist', function () {
        Livewire::test('tables.accommodation.pricing')
            ->assertSee('No accommodation types found');
    });
});

describe('Delete', function () {
    it('removes pricing when deleted', function () {
        $typeId = createPricingType();
        createPricing($typeId);

        Livewire::test('tables.accommodation.pricing')
            ->call('delete', $typeId);

        $this->assertDatabaseMissing('accommodation_pricing', [
            'accommodation_type_id' => $typeId,
        ]);
    });

    it('removes all rate bands for the type', function () {
        $typeId = createPricingType();
        createPricing($typeId);
        createRateBand($typeId);
        createRateBand($typeId, ['label' => 'Low season']);

        Livewire::test('tables.accommodation.pricing')
            ->call('delete', $typeId);

        $this->assertDatabaseMissing('accommodation_rate_bands', [
            'accommodation_type_id' => $typeId,
        ]);
    });

    it('removes all surcharges for the type', function () {
        $typeId = createPricingType();
        createPricing($typeId);
        createSurcharge($typeId);

        Livewire::test('tables.accommodation.pricing')
            ->call('delete', $typeId);

        $this->assertDatabaseMissing('accommodation_surcharges', [
            'accommodation_type_id' => $typeId,
        ]);
    });

    it('removes all extras for the type', function () {
        $typeId = createPricingType();
        createPricing($typeId);
        createExtra($typeId);

        Livewire::test('tables.accommodation.pricing')
            ->call('delete', $typeId);

        $this->assertDatabaseMissing('accommodation_extras', [
            'accommodation_type_id' => $typeId,
        ]);
    });

    it('does not affect pricing for other types', function () {
        $typeA = createPricingType();
        $typeB = createPricingType('Chalet');
        createPricing($typeA);
        createPricing($typeB, 200.00);

        Livewire::test('tables.accommodation.pricing')
            ->call('delete', $typeA);

        $this->assertDatabaseHas('accommodation_pricing', [
            'accommodation_type_id' => $typeB,
        ]);
    });
});
