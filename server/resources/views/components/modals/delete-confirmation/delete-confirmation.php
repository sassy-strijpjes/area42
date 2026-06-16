<?php

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $modalName = '';

    public string $itemName = '';

    public string $table = '';

    public $itemId = '';

    public function delete(): void
    {
        if (! $this->table || ! $this->itemId) {
            Flux::toast('Invalid delete request', variant: 'warning');

            return;
        }

        DB::beginTransaction();

        try {
            if ($this->table === 'roles') {
                DB::table('role_permissions')
                    ->where('role_id', $this->itemId)
                    ->delete();

                DB::table('staff_roles')
                    ->where('role_id', $this->itemId)
                    ->delete();
            }

            DB::table($this->table)
                ->where('id', $this->itemId)
                ->delete();

            DB::commit();

            Flux::toast(ucfirst(str_replace('_', ' ', $this->itemName)).' deleted successfully', variant: 'success');

            $this->dispatch('item-deleted', table: $this->table);
        } catch (Exception) {
            DB::rollBack();

            Flux::toast('Error deleting '.strtolower($this->itemName), variant: 'danger');
        }
    }
};
