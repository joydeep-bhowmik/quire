<?php

declare(strict_types=1);

namespace Quire;

final class Request
{
    /** @var array<string, string|list<string>> Route parameters, filled in once a page matches. */
    public array $params = [];

    public ?Route $route = null;

    /** @var array<string, mixed> Free space for middleware to pass data to pages (e.g. the current user). */
    public array $attributes = [];

    /** @var array<string, string> */
    public array $headers = [];

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        public string $method = 'GET',
        public string $path = '/',
        public array $query = [],
        public array $body = [],
        array $headers = [],
        public array $cookies = [],
        public array $files = [],
        public array $server = [],
    ) {
        $this->method = strtoupper($method);
        $this->path = '/' . ltrim($path, '/');
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // HTML forms can only GET/POST; allow <input type="hidden" name="_method" value="DELETE">.
        if ($method === 'POST' && in_array(strtoupper((string) ($_POST['_method'] ?? '')), ['PUT', 'PATCH', 'DELETE'], true)) {
            $method = strtoupper($_POST['_method']);
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[str_replace('_', '-', $key)] = (string) $value;
            }
        }

        $body = $_POST;
        if ($body === [] && str_contains(strtolower($headers['CONTENT-TYPE'] ?? ''), 'json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $body = is_array($decoded) ? $decoded : [];
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return new self($method, is_string($path) ? $path : '/', $_GET, $body, $headers, $_COOKIE, $_FILES, $_SERVER);
    }

    /**
     * Build a request by hand — handy for tests.
     *
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     */
    public static function create(string $method, string $uri, array $body = [], array $headers = [], array $cookies = []): self
    {
        $path = parse_url($uri, PHP_URL_PATH);
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        return new self($method, is_string($path) ? $path : '/', $query, $body, $headers, $cookies);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function cookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('accept'), 'json');
    }
}
