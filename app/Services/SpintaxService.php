<?php

namespace App\Services;

class SpintaxService
{
    /**
     * Parse nested spintax text format like "{Halo|Selamat pagi|Hai} {Bapak/Ibu|Wajib Pajak}"
     */
    public static function spin(?string $text): string
    {
        if (blank($text)) {
            return '';
        }

        while (preg_match('/\{([^{}]+)\}/', $text)) {
            $text = preg_replace_callback('/\{([^{}]+)\}/', function ($matches) {
                $parts = explode('|', $matches[1]);
                return $parts[array_rand($parts)];
            }, $text);
        }

        return $text;
    }

    /**
     * Replace template placeholders with array data
     */
    public static function interpolate(string $template, array $data): string
    {
        foreach ($data as $key => $val) {
            $valueStr = is_scalar($val) ? (string) $val : '';
            $template = str_replace('{' . $key . '}', $valueStr, $template);
            $template = str_replace(':' . $key, $valueStr, $template);
        }

        return $template;
    }
}
