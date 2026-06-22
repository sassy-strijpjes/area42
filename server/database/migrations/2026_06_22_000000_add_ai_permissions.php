<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert AI permissions if they don't already exist
        $permissions = ['view_ai_predictions', 'train_ai_model'];
        $now = now();

        foreach ($permissions as $perm) {
            if (!DB::table('permissions')->where('name', $perm)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $perm,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'view_ai_predictions',
            'train_ai_model',
        ])->delete();
    }
};
