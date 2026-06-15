<?php

use App\Livewire\FormComponent;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;

new class extends FormComponent {

    public int $unitId;

    #[Validate('required|exists:accommodation_types,id')]
    public int $accommodation_type_id = 0;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|in:available,occupied,maintenance')]
    public string $status = 'available';

    #[Validate('nullable|string')]
    public string $notes = '';

    public function mount($unit): void
    {
        $unitId = is_numeric($unit) ? $unit : ($unit->id ?? null);

        if (!$unitId) return;

        $this->unitId = $unitId;

        $data = DB::table('accommodation_units')->find($unitId);

        if ($data) {
            $this->accommodation_type_id = $data->accommodation_type_id;
            $this->name                  = $data->name;
            $this->status                = $data->status;
            $this->notes                 = $data->notes ?? '';
        }
    }

    public function update(): void
    {
        $this->validate();

        DB::table('accommodation_units')
            ->where('id', $this->unitId)
            ->update([
                'accommodation_type_id' => $this->accommodation_type_id,
                'name'                  => $this->name,
                'status'                => $this->status,
                'notes'                 => $this->notes ?: null,
                'updated_at'            => now(),
            ]);

        Flux::toast('Unit updated', variant: 'success');

        $this->redirect(route('staff.accommodation.units'), navigate: true);
    }

    public function types()
    {
        return DB::table('accommodation_types')->orderBy('name')->get();
    }
};
