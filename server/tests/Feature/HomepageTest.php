<?php

// US-CUS-01

use App\Models\User;

describe('US-CUS-01', function () {
    it('resolves the home route to the / URI', function () {
        expect(route('home'))->toBe(url('/'));
    });

    it('returns a 200 response for guests', function () {
        $this->get(route('home'))
            ->assertOk();
    });

    it('renders the home view', function () {
        $this->get(route('home'))
            ->assertViewIs('home');
    });

    it('shows the page heading', function () {
        $this->get(route('home'))
            ->assertSeeText('What would you like to book?');
    });

    it('displays the Restaurant booking option', function () {
        $this->get(route('home'))
            ->assertSeeText('Restaurant')
            ->assertSeeText('Book a table');
    });

    it('displays the Accommodation booking option', function () {
        $this->get(route('home'))
            ->assertSeeText('Accommodation')
            ->assertSeeText('Book a room');
    });

    it('displays the Bike Rental booking option', function () {
        $this->get(route('home'))
            ->assertSeeText('Bike Rental')
            ->assertSeeText('Rent a bike');
    });

    it('displays the no-account-required reassurance', function () {
        $this->get(route('home'))
            ->assertSeeText('No account required');
    });

    it('displays the instant-confirmation reassurance', function () {
        $this->get(route('home'))
            ->assertSeeText('Instant confirmation');
    });

    it('displays the secure-and-private reassurance', function () {
        $this->get(route('home'))
            ->assertSeeText('Secure & private');
    });
});
