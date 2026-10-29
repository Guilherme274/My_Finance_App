<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configurações locais de cada instalação (não sincronizadas).
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Retorna o PIN ativo (prioriza o configurado no app/banco, depois o .env).
     */
    public static function getPin(): ?string
    {
        $dbPin = static::get('settings_app_pin');
        if (filled($dbPin)) {
            return trim((string) $dbPin);
        }

        $envPin = config('finance.pin');
        if (filled($envPin)) {
            return trim((string) $envPin);
        }

        return null;
    }

    /**
     * Verifica se existe um PIN configurado no app ou no .env.
     */
    public static function hasPin(): bool
    {
        return filled(static::getPin());
    }

    /**
     * Valida se o PIN informado confere com o PIN configurado.
     */
    public static function verifyPin(?string $pin): bool
    {
        $expected = static::getPin();
        if (blank($expected)) {
            return true;
        }

        if (blank($pin)) {
            return false;
        }

        return hash_equals($expected, trim((string) $pin));
    }

    /**
     * Salva ou remove o PIN configurado pelo aplicativo.
     */
    public static function setPin(?string $pin): void
    {
        if (blank($pin)) {
            static::query()->where('key', 'settings_app_pin')->delete();
        } else {
            static::put('settings_app_pin', trim((string) $pin));
        }
    }
}
