<?php

use App\Livewire\FormComponent;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;

new class extends FormComponent {

    #[Validate('required|exists:accommodation_types,id')]
    public int $accommodation_type_id = 0;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|in:available,occupied,maintenance')]
    public string $status = 'available';

    #[Validate('nullable|string')]
    public string $notes = '';

    public function create(): void
    {
        $this->validate();

        DB::table('accommodation_units')->insert([
            'accommodation_type_id' => $this->accommodation_type_id,
            'name'                  => $this->name,
            'status'                => $this->status,
            'is_active'             => true,
            'notes'                 => $this->notes ?: null,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        Flux::toast('Unit created', variant: 'success');

        $this->redirect(route('staff.accommodation.units'), navigate: true);
    }

    public function types()
    {
        return DB::table('accommodation_types')->orderBy('name')->get();
    }
};
