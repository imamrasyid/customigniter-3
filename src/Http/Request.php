<?php
declare(strict_types=1);

/**
 * Customigniter 3 - Modern HTTP Request
 *
 * Clean wrapper around PHP superglobals.
 * Complements CI_Input, does not replace it.
 */
namespace Customigniter\Http;

#[\AllowDynamicProperties]
class Request
{
    /** @var array<mixed> */
    protected array $query = [];
    /** @var array<mixed> */
    protected array $post = [];
    /** @var array<mixed> */
    protected array $server = [];
    /** @var array<mixed> */
    protected array $cookies = [];
    /** @var array<mixed> */
    protected array $files = [];
    protected ?string $body = null;
    protected bool $trustProxy = false;

    /**
     * @param array<mixed>|null $query
     * @param array<mixed>|null $post
     * @param array<mixed>|null $server
     * @param array<mixed>|null $cookies
     * @param array<mixed>|null $files
     */
    public function __construct(
        ?array $query = null,
        ?array $post = null,
        ?array $server = null,
        ?array $cookies = null,
        ?array $files = null,
        bool $trustProxy = false,
    ) {
        $this->query   = $query   ?? $_GET;
        $this->post     = $post     ?? $_POST;
        $this->server   = $server   ?? $_SERVER;
        $this->cookies  = $cookies  ?? $_COOKIE;
        $this->files    = $files    ?? $_FILES;
        $this->trustProxy = $trustProxy;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * @return array<mixed>|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        return strtoupper(is_string($method) ? $method : 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    public function isAjax(): bool
    {
        return $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ($this->server('SERVER_PORT') ?? 0) === 443
            || $this->server('HTTP_X_FORWARDED_PROTO') === 'https';
    }

    /**
     * Client IP address.
     *
     * Forwarded headers (X-Forwarded-For / Client-IP) are only honoured
     * when the request is known to come from a trusted reverse proxy
     * ($trustProxy = true), because those headers are client-controlled
     * and can be spoofed otherwise.
     */
    public function ip(): string
    {
        if ($this->trustProxy) {
            $forwarded = $this->server('HTTP_X_FORWARDED_FOR');

            if (is_string($forwarded) && $forwarded !== '') {
                $candidate = trim(explode(',', $forwarded)[0]);
                if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                    return $candidate;
                }
            }

            $clientIp = $this->server('HTTP_CLIENT_IP');
            if (is_string($clientIp) && filter_var($clientIp, FILTER_VALIDATE_IP) !== false) {
                return $clientIp;
            }
        }

        $remote = $this->server('REMOTE_ADDR');

        return is_string($remote) && $remote !== '' ? $remote : '0.0.0.0';
    }

    public function userAgent(): string
    {
        $agent = $this->server('HTTP_USER_AGENT', '');
        return is_string($agent) ? $agent : '';
    }

    public function uri(): string
    {
        $uri = $this->server('REQUEST_URI', '/');
        return is_string($uri) ? $uri : '/';
    }

    public function contentType(): ?string
    {
        $type = $this->server('CONTENT_TYPE');
        return is_string($type) ? $type : null;
    }

    public function accepts(string $type): bool
    {
        $accept = $this->server('HTTP_ACCEPT', '');
        if (!is_string($accept)) {
            return false;
        }
        return str_contains($accept, $type) || str_contains($accept, '*/*');
    }

    /**
     * @return array<mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * @param array<int|string> $keys
     * @return array<mixed>
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * @param array<int|string> $keys
     * @return array<mixed>
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function filled(string $key): bool
    {
        return $this->has($key) && $this->input($key) !== '' && $this->input($key) !== null;
    }

    public function getBody(): string
    {
        if ($this->body !== null) {
            return $this->body;
        }
        $body = file_get_contents('php://input');
        $this->body = $body === false ? '' : $body;
        return $this->body;
    }

    public function json(bool $associative = true): mixed
    {
        return json_decode($this->getBody(), $associative);
    }
}
