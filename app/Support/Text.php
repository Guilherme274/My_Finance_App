<?php

namespace App\Support;

use Illuminate\Support\Str;

final class Text
{
    /**
     * Normaliza descrições para comparação: sem acentos, minúsculas, espaços únicos.
     */
    public static function normalize(string $value): string
    {
        return (string) Str::of(Str::ascii($value))->lower()->squish();
    }
}
