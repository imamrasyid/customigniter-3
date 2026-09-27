<?php
declare(strict_types=1);

/**
 * Customigniter 3 - Input Sanitizer
 *
 * Convenience wrapper for common input sanitization.
 * Complements CI_Security::xss_clean() with typed helpers.
 *
 * Note: string() deliberately does NOT HTML-escape — escaping belongs
 * at the output layer (e.g. html_escape()/htmlspecialchars() when
 * rendering), otherwise already-escaped data gets double-encoded.
 * Use html() when an escaped copy is explicitly wanted.
 */
namespace Customigniter\Security;

class InputSanitizer
{
    public static function string(string $input, int $maxLength = 0): string
    {
        $input = trim(strip_tags($input));
        $input = self::stripControlCharacters($input);

        if ($maxLength > 0 && mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength);
        }

        return $input;
    }

    /**
     * HTML-escape a value for safe output (explicit opt-in).
     */
    public static function html(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function email(string $input): string
    {
        $input = trim($input);
        if (filter_var($input, FILTER_VALIDATE_EMAIL) === false) {
            return '';
        }
        return $input;
    }

    public static function int(string|int|float $input): int
    {
        return (int) filter_var((string) $input, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function float(string|int|float $input): float
    {
        return (float) filter_var((string) $input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public static function bool(mixed $input): bool
    {
        if (is_bool($input)) {
            return $input;
        }

        $map = ['1', 'true', 'yes', 'on'];
        return in_array(strtolower(trim((string) $input)), $map, true);
    }

    public static function url(string $input): string
    {
        $input = trim($input);
        $input = filter_var($input, FILTER_SANITIZE_URL);
        if ($input && filter_var($input, FILTER_VALIDATE_URL) === false) {
            return '';
        }
        return $input;
    }

    public static function alphanum(string $input): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $input);
    }

    public static function filename(string $input): string
    {
        $input = basename($input);
        $input = preg_replace('/[^\w\.\-]/', '', $input);
        return $input;
    }

    public static function array(array $input, callable $sanitizer): array
    {
        $clean = [];
        foreach ($input as $key => $value) {
            $cleanKey = self::key($key);
            if (is_array($value)) {
                $clean[$cleanKey] = self::array($value, $sanitizer);
            }
            else {
                $clean[$cleanKey] = $sanitizer($value);
            }
        }
        return $clean;
    }

    /**
     * Sanitize an array key without altering its meaning.
     *
     * Control characters are removed, but the key is neither HTML-escaped
     * nor rewritten (which would corrupt keys like "a&b").
     */
    private static function key(string|int $key): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/', '', (string) $key) ?? '';
    }

    /**
     * Remove C0/C1 control characters (tabs, newlines and carriage
     * returns are preserved).
     */
    private static function stripControlCharacters(string $input): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input) ?? $input;
    }
}
