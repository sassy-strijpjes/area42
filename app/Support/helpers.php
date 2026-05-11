<?php

if (!function_exists('user')) {
    function user()
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '';
        $type = preg_match('#^/?admin(/|$)#', $uri) ? 'admin' : 'staff';

        $id = session()->get("{$type}_id");

        if (!$id) {
            return null;
        }

        $table = $type === 'admin' ? 'users' : 'staff';

        return DB::table($table)->where('id', $id)->first();
    }
}
