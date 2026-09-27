<?php
declare(strict_types=1);

/**
 * Customigniter 3 - OPcache Configuration
 *
 * Generates and validates recommended opcache.ini settings for PHP 8.4.
 */
namespace Customigniter\Performance;

class OpcacheConfig
{
    /**
     * @return array<string, int|string>
     */
    public static function getRecommendedSettings(): array
    {
        return [
            'opcache.enable'                 => 1,
            'opcache.enable_cli'             => 0,
            'opcache.memory_consumption'     => 128,
            'opcache.interned_strings_buffer' => 16,
            'opcache.max_accelerated_files'  => 10000,
            'opcache.revalidate_freq'        => 0,
            'opcache.save_comments'          => 1,
            'opcache.validate_timestamps'    => 1,
            'opcache.max_wasted_percentage'  => 5,
            'opcache.jit'                    => 'tracing',
            'opcache.jit_buffer_size'        => 64 * 1024 * 1024,
        ];
    }

    public static function generateIni(): string
    {
        $lines = ['; Customigniter 3 - Recommended OPcache settings'];
        $lines[] = '; Paste into your php.ini or a conf.d file';

        foreach (self::getRecommendedSettings() as $key => $value) {
            $lines[] = sprintf('%s = %s', $key, (string) $value);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * JIT modes as documented by PHP (see opcache.jit in the PHP manual).
     *
     * @return array<int, string>
     */
    public static function getJitModes(): array
    {
        return [
            0    => 'Disabled',
            1205 => 'Function — compile functions on script load (CRTO 1205)',
            1254 => 'Tracing — recommended, traces hot code segments (CRTO 1254)',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function getCurrentStatus(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['available' => false];
        }

        $status = opcache_get_status(false);

        return [
            'available' => $status !== false,
            'enabled'   => $status['opcache_enabled'] ?? false,
            'memory'    => $status['memory_usage'] ?? [],
            'interned'  => $status['interned_strings_usage'] ?? [],
            'opcache'   => $status['opcache_statistics'] ?? [],
            'jit'       => $status['jit'] ?? null,
        ];
    }

    /**
     * @return list<string>
     */
    public static function validate(): array
    {
        $issues = [];

        if (!function_exists('opcache_get_status')) {
            return ['OPcache extension not available'];
        }

        $status = opcache_get_status(false);

        if (!$status || !($status['opcache_enabled'] ?? false)) {
            return ['OPcache is not enabled'];
        }

        $mem = $status['memory_usage'] ?? [];
        if (!is_array($mem)) {
            $mem = [];
        }

        $used  = $mem['used_memory'] ?? null;
        $free  = $mem['free_memory'] ?? null;
        $used  = is_numeric($used) ? (int) $used : 0;
        $free  = is_numeric($free) ? (int) $free : 0;
        $total = $used + $free;

        if ($total > 0) {
            $usagePct = ($used / $total) * 100;
            if ($usagePct > 80) {
                $issues[] = sprintf('OPcache memory usage high: %.1f%%', $usagePct);
            }
        }

        // opcache_statistics does not contain max_accelerated_files;
        // read the configured limit from opcache_get_configuration().
        $maxFiles = 10000;
        if (function_exists('opcache_get_configuration')) {
            $config = opcache_get_configuration();
            if (is_array($config)) {
                $maxFiles = (int) $config['directives']['opcache.max_accelerated_files'];
            }
        }

        $stats = $status['opcache_statistics'] ?? [];
        if (!is_array($stats)) {
            $stats = [];
        }

        $cachedKeys = $stats['num_cached_keys'] ?? null;
        $cachedKeys = is_numeric($cachedKeys) ? (int) $cachedKeys : 0;

        if ($maxFiles > 0 && $cachedKeys >= $maxFiles * 0.9) {
            $issues[] = sprintf(
                'OPcache near max_accelerated_files limit (%d/%d keys used)',
                $cachedKeys,
                $maxFiles
            );
        }

        return $issues;
    }
}
