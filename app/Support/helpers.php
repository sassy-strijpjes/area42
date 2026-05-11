<?php

if (!function_exists('auth'))
{
    function auth(string $type = 'staff')
    {
        $id = session()->get("{$type}_id");

        if (! $id) {
            return null;
        }

        $table = $type == 'admin' ? 'users' : 'staffs';

        return DB::table($table)->where('id', $id)->first();
    }
}
