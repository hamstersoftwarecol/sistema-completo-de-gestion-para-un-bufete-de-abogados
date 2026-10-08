<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Claves que se guardan cifradas con APP_KEY. */
    public const ENCRYPTED = ['gemini_api_key', 'openai_api_key'];

    protected static ?array $loaded = null;

    public static function values(): array
    {
        if (static::$loaded === null) {
            try {
                static::$loaded = static::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                // La tabla aún no existe (instalación en curso).
                return [];
            }
        }

        return static::$loaded;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::values()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        if (in_array($key, self::ENCRYPTED, true)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $value;
    }

    public static function put(string $key, mixed $value): void
    {
        if (in_array($key, self::ENCRYPTED, true) && filled($value)) {
            $value = Crypt::encryptString((string) $value);
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        static::flush();
    }

    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::put($key, $value);
        }
    }

    public static function flush(): void
    {
        static::$loaded = null;
    }
}
