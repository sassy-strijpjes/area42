<?php

use App\Livewire\FormComponent;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;

new class extends FormComponent {

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|integer|min:1')]
    public int $max_guests = 1;

    #[Validate('nullable|string')]
    public string $amenities = '';

    public function create(): void
    {
        $this->validate();

        $amenities = collect(explode(',', $this->amenities))
            ->map(fn($a) => trim($a))
            ->filter()
            ->values()
            ->all();

        DB::table('accommodation_types')->insert([
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'max_guests'  => $this->max_guests,
            'amenities'   => count($amenities) ? json_encode($amenities) : null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        Flux::toast('Accommodation type created', variant: 'success');

        $this->redirect(route('staff.accommodation.types'), navigate: true);
    }
};
