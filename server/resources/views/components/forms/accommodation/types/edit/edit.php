<?php

use App\Livewire\FormComponent;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;

new class extends FormComponent {

    public int $typeId;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|integer|min:1')]
    public int $max_guests = 1;

    #[Validate('nullable|string')]
    public string $amenities = '';

    public function mount($type): void
    {
        $typeId = is_numeric($type) ? $type : ($type->id ?? null);

        if (!$typeId) return;

        $this->typeId = $typeId;

        $data = DB::table('accommodation_types')->find($typeId);

        if ($data) {
            $this->name        = $data->name;
            $this->description = $data->description ?? '';
            $this->max_guests  = $data->max_guests;
            $this->amenities   = implode(', ', json_decode($data->amenities ?? '[]', true));
        }
    }

    public function update(): void
    {
        $this->validate();

        $amenities = collect(explode(',', $this->amenities))
            ->map(fn($a) => trim($a))
            ->filter()
            ->values()
            ->all();

        DB::table('accommodation_types')
            ->where('id', $this->typeId)
            ->update([
                'name'        => $this->name,
                'description' => $this->description ?: null,
                'max_guests'  => $this->max_guests,
                'amenities'   => count($amenities) ? json_encode($amenities) : null,
                'updated_at'  => now(),
            ]);

        Flux::toast('Accommodation type updated', variant: 'success');

        $this->redirect(route('staff.accommodation.types'), navigate: true);
    }
};
