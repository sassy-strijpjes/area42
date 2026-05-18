<?php

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;

new class extends Component {
    #[Validate('required|string|max:255|unique:roles,name')]
    public string $name = '';

    public function create(): void
    {
        $this->validate();

        DB::table('roles')->insert([
            'name' => $this->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session()->flash('toast', [
            'text' => 'Role created successfully',
            'variant' => 'success',
        ]);

        $this->redirect(route('admin.roles'), navigate: true);
    }
};
