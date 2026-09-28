<?php
declare(strict_types=1);

/**
 * Customigniter 3 - Modern HTTP Response
 *
 * Fluent builder for HTTP responses.
 * Complements CI_Output, does not replace it.
 */
namespace Customigniter\Http;

class Response
{
    protected int $statusCode = 200;
    /** @var array<string, string> */
    protected array $headers = [];
    protected string $body = '';
    protected string $protocol = '1.1';

    public function status(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function header(string $name, string $value): static
    {
        if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $value)) {
            throw new \InvalidArgumentException('Header names and values must not contain CR or LF characters.');
        }

        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @param array<array-key, mixed> $headers
     */
    public function headers(array $headers): static
    {
        foreach ($headers as $name => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $this->header((string) $name, (string) $value);
        }
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function body(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function json(mixed $data, int $options = JSON_UNESCAPED_UNICODE): static
    {
        $encoded = json_encode($data, $options);

        if ($encoded === false) {
            throw new \RuntimeException('Failed to encode response as JSON: ' . json_last_error_msg());
        }

        $this->body = $encoded;
        $this->headers['Content-Type'] = 'application/json; charset=UTF-8';
        return $this;
    }

    public function html(string $content): static
    {
        $this->body = $content;
        $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
        return $this;
    }

    public function text(string $content): static
    {
        $this->body = $content;
        $this->headers['Content-Type'] = 'text/plain; charset=UTF-8';
        return $this;
    }

    public function redirect(string $url, int $code = 302): static
    {
        $this->statusCode = $code;
        $this->header('Location', $url);
        return $this;
    }

    /**
     * @param array<mixed> $options
     */
    public function withCookie(string $name, string $value, array $options = []): static
    {
        $expire  = $options['expire'] ?? 0;
        $path    = $options['path'] ?? '/';
        $domain  = $options['domain'] ?? '';
        $secure  = $options['secure'] ?? false;
        $httponly = $options['httponly'] ?? true;
        $samesite = $options['samesite'] ?? 'Lax';

        if (!in_array($samesite, ['Lax', 'lax', 'None', 'none', 'Strict', 'strict'], true)) {
            $samesite = 'Lax';
        }

        setcookie($name, $value, [
            'expires'  => is_int($expire) ? $expire : 0,
            'path'     => is_string($path) ? $path : '/',
            'domain'   => is_string($domain) ? $domain : '',
            'secure'   => (bool) $secure,
            'httponly' => (bool) $httponly,
            'samesite' => $samesite,
        ]);
        return $this;
    }

    public function cache(int $seconds, bool $public = true): static
    {
        $visibility = $public ? 'public' : 'private';
        $this->headers['Cache-Control'] = sprintf('%s, max-age=%d', $visibility, $seconds);
        return $this;
    }

    public function noCache(): static
    {
        $this->headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
        $this->headers['Pragma'] = 'no-cache';
        return $this;
    }

    public function noContent(): static
    {
        $this->statusCode = 204;
        $this->body = '';
        return $this;
    }

    public function download(string $filename, string $contents): static
    {
        $safe = str_replace(["\r", "\n", '"'], '', $filename);

        $this->header('Content-Disposition', 'attachment; filename="' . $safe . '"');
        $this->header('Content-Type', 'application/octet-stream');
        $this->body = $contents;
        return $this;
    }

    public function jsonp(string $callback, mixed $data, int $options = JSON_UNESCAPED_UNICODE): static
    {
        if (!preg_match('/^[A-Za-z_$][A-Za-z0-9_$.]*$/', $callback)) {
            throw new \InvalidArgumentException('Invalid JSONP callback name.');
        }

        $encoded = json_encode($data, $options);

        if ($encoded === false) {
            throw new \RuntimeException('Failed to encode response as JSON: ' . json_last_error_msg());
        }

        $this->body = $callback . '(' . $encoded . ');';
        $this->headers['Content-Type'] = 'application/javascript; charset=UTF-8';
        return $this;
    }

    public function xml(string $content): static
    {
        $this->body = $content;
        $this->headers['Content-Type'] = 'application/xml; charset=UTF-8';
        return $this;
    }

    public function send(): void
    {
        if ( ! headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', $name, $value));
            }
        }

        echo $this->body;
    }
}
