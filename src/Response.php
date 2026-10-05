<?php

declare(strict_types=1);

namespace Quire;

use JsonSerializable;
use Stringable;
use UnexpectedValueException;

final class Response
{
    private const REASONS = [
        200 => 'OK', 201 => 'Created', 202 => 'Accepted', 204 => 'No Content',
        301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified',
        307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
        400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found',
        405 => 'Method Not Allowed', 409 => 'Conflict', 410 => 'Gone', 419 => 'Page Expired',
        422 => 'Unprocessable Content', 429 => 'Too Many Requests',
        500 => 'Server Error', 502 => 'Bad Gateway', 503 => 'Service Unavailable',
    ];

    /** @var array<string, array{0: string, 1: array<string, mixed>}> */
    public array $cookies = [];

    /** @param array<string, string> $headers */
    public function __construct(
        public string $body = '',
        public int $status = 200,
        public array $headers = [],
    ) {
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function text(string $text, int $status = 200): self
    {
        return new self($text, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new self($json, $status, ['Content-Type' => 'application/json']);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self('', $status, ['Location' => $to]);
    }

    /**
     * Normalise whatever a page or middleware returned into a Response.
     */
    public static function from(mixed $value): self
    {
        return match (true) {
            $value instanceof self => $value,
            is_string($value), $value instanceof Stringable => self::html((string) $value),
            is_array($value), $value instanceof JsonSerializable => self::json($value),
            default => throw new UnexpectedValueException('Cannot convert [' . get_debug_type($value) . '] to a response.'),
        };
    }

    public static function reason(int $status): string
    {
        return self::REASONS[$status] ?? 'Error';
    }

    public function header(string $name, string $value): self
    {
        foreach (array_keys($this->headers) as $existing) {
            if (strcasecmp($existing, $name) === 0) {
                unset($this->headers[$existing]);
            }
        }

        $this->headers[$name] = $value;

        return $this;
    }

    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Queue a cookie. Options are passed straight to setcookie(): expires, path, domain, secure, httponly, samesite.
     *
     * @param array<string, mixed> $options
     */
    public function cookie(string $name, string $value, array $options = []): self
    {
        $this->cookies[$name] = [$value, $options + ['path' => '/', 'httponly' => true, 'samesite' => 'Lax']];

        return $this;
    }

    public function forgetCookie(string $name): self
    {
        return $this->cookie($name, '', ['expires' => 1]);
    }

    public function send(bool $withoutBody = false): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}", true);
            }

            foreach ($this->cookies as $name => [$value, $options]) {
                setcookie($name, $value, $options);
            }
        }

        if (!$withoutBody) {
            echo $this->body;
        }
    }
}
