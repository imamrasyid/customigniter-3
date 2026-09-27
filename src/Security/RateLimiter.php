<?php
declare(strict_types=1);

/**
 * Customigniter 3 - Rate Limiter
 *
 * Simple file-based rate limiter. Read-modify-write cycles run under an
 * exclusive file lock so concurrent processes cannot exceed the limit,
 * and reads use a shared lock. For high-traffic or multi-server
 * deployments, plug in a Redis-backed adapter instead.
 */
namespace Customigniter\Security;

class RateLimiter
{
    private string $cachePath;
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(
        string $cachePath = '',
        int $maxAttempts = 60,
        int $decayMinutes = 1,
    ) {
        $this->cachePath    = $cachePath ?: (sys_get_temp_dir() . '/ci3_ratelimit');
        $this->maxAttempts  = $maxAttempts;
        $this->decayMinutes = $decayMinutes;

        if (!is_dir($this->cachePath) && !@mkdir($this->cachePath, 0755, true) && !is_dir($this->cachePath)) {
            throw new \RuntimeException("Cannot create rate limit cache directory: {$this->cachePath}");
        }
    }

    /**
     * Count an attempt against the key if the limit allows it.
     *
     * The check and the increment happen atomically under an exclusive
     * lock, so parallel requests cannot overshoot the limit.
     */
    public function attempt(string $key, ?int $maxAttempts = null): bool
    {
        $max = $maxAttempts ?? $this->maxAttempts;

        return $this->writeLocked($key, function (array &$data) use ($max): bool {
            if ( ! $this->isExpired($data) && ($data['attempts'] ?? 0) >= $max) {
                return false;
            }

            if ($this->isExpired($data)) {
                $data = ['attempts' => 0, 'timestamp' => time()];
            }

            $data['attempts']++;

            return true;
        });
    }

    public function attempts(string $key): int
    {
        $data = $this->readLocked($key);

        if ($this->isExpired($data)) {
            return 0;
        }

        return $data['attempts'] ?? 0;
    }

    public function remaining(string $key, ?int $maxAttempts = null): int
    {
        $max = $maxAttempts ?? $this->maxAttempts;
        return max(0, $max - $this->attempts($key));
    }

    public function reset(string $key): void
    {
        $file = $this->getPath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function availableIn(string $key): int
    {
        $data = $this->readLocked($key);

        if ($this->isExpired($data)) {
            return 0;
        }

        $elapsed = time() - ($data['timestamp'] ?? 0);
        $decay   = $this->decayMinutes * 60;

        return max(0, $decay - $elapsed);
    }

    public function setMaxAttempts(int $max): static
    {
        $this->maxAttempts = $max;
        return $this;
    }

    public function setDecayMinutes(int $minutes): static
    {
        $this->decayMinutes = $minutes;
        return $this;
    }

    /**
     * Read the key's data under a shared lock. Never creates the file.
     *
     * @return array{attempts?: int, timestamp?: int}
     */
    private function readLocked(string $key): array
    {
        $file = $this->getPath($key);

        $fp = @fopen($file, 'rb');
        if ($fp === false) {
            return [];
        }

        try {
            if ( ! flock($fp, LOCK_SH)) {
                throw new \RuntimeException("Cannot lock rate limit file: {$file}");
            }

            $raw = stream_get_contents($fp);
            flock($fp, LOCK_UN);

            $data = $raw !== false && $raw !== '' ? (json_decode($raw, true) ?? []) : [];

            return is_array($data) ? $data : [];
        }
        finally {
            fclose($fp);
        }
    }

    /**
     * Run a read-modify-write cycle under an exclusive lock.
     *
     * The callback receives the data array by reference — empty when the
     * file is missing or the window expired. Its return value is passed
     * through; whatever it leaves in the array is persisted before
     * unlocking.
     */
    private function writeLocked(string $key, callable $callback): mixed
    {
        $file = $this->getPath($key);

        $fp = @fopen($file, 'c+b');
        if ($fp === false) {
            throw new \RuntimeException("Cannot open rate limit file: {$file}");
        }

        try {
            if ( ! flock($fp, LOCK_EX)) {
                throw new \RuntimeException("Cannot lock rate limit file: {$file}");
            }

            $raw = stream_get_contents($fp);
            $data = $raw !== false && $raw !== '' ? (json_decode($raw, true) ?? []) : [];

            if ( ! is_array($data) || $this->isExpired($data)) {
                $data = [];
            }

            $result = $callback($data);

            if ($data === []) {
                $data = ['attempts' => 0, 'timestamp' => time()];
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) json_encode($data));
            fflush($fp);
            flock($fp, LOCK_UN);

            return $result;
        }
        finally {
            fclose($fp);
        }
    }

    private function isExpired(array $data): bool
    {
        if (empty($data['timestamp'])) {
            return true;
        }

        return (time() - $data['timestamp']) >= ($this->decayMinutes * 60);
    }

    private function getPath(string $key): string
    {
        return $this->cachePath . '/' . md5($key) . '.json';
    }
}
