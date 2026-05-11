<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('user')) {
    function user()
    {
        $request = request();

        $type = $request->is('admin*') ? 'admin' : 'staff';

        $id = session()->get("{$type}_id");

        if (!$id) {
            return null;
        }

        $table = $type === 'admin' ? 'admins' : 'staff';

        return DB::table($table)->where('id', $id)->first();
    }
}
