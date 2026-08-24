<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SqlDate
{
    public static function date(string $column): string
    {
        return match (self::driver()) {
            'pgsql' => "DATE($column)",
            default => "DATE($column)",
        };
    }

    public static function year(string $column): string
    {
        return match (self::driver()) {
            'sqlite' => "CAST(strftime('%Y', $column) AS INTEGER)",
            'pgsql' => "EXTRACT(YEAR FROM $column)",
            default => "YEAR($column)",
        };
    }

    public static function month(string $column): string
    {
        return match (self::driver()) {
            'sqlite' => "CAST(strftime('%m', $column) AS INTEGER)",
            'pgsql' => "EXTRACT(MONTH FROM $column)",
            default => "MONTH($column)",
        };
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }
}
