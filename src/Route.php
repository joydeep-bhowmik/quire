<?php

declare(strict_types=1);

namespace Quire;

use InvalidArgumentException;
use LogicException;

final class Route
{
    public const STATIC = 0;
    public const PARAM = 1;
    public const CATCH_ALL = 2;
    public const OPTIONAL_CATCH_ALL = 3;

    private ?PageMetadata $metadata = null;

    /** @var array<string, list<mixed>> */
    private static array $directoryMiddleware = [];

    /**
     * @param list<array{0: int, 1: string}> $segments [type, value]
     */
    private function __construct(
        public readonly Mount $mount,
        public readonly string $file,
        public readonly array $segments,
        public readonly string $extension,
    ) {
    }

    public static function fromFile(Mount $mount, string $file, string $extension = '.php'): self
    {
        $relative = str_replace('\\', '/', substr($file, strlen($mount->path) + 1, -strlen($extension)));
        $parts = explode('/', $relative);

        if (end($parts) === 'index') {
            array_pop($parts);
        }

        $segments = [];
        foreach (self::splitUri($mount->getUri()) as $static) {
            $segments[] = [self::STATIC, $static];
        }

        $last = count($parts) - 1;
        foreach ($parts as $i => $part) {
            $segment = self::parseSegment($part, $file);

            if ($segment[0] >= self::CATCH_ALL && $i !== $last) {
                throw new LogicException("Catch-all segment [{$part}] must be last in [{$file}].");
            }

            $segments[] = $segment;
        }

        return new self($mount, $file, $segments, $extension);
    }

    /** @return list<string> */
    public static function splitUri(string $uri): array
    {
        $uri = trim($uri, '/');

        return $uri === '' ? [] : array_map('rawurldecode', explode('/', $uri));
    }

    /**
     * Sort comparator: static beats [param] beats [...catchall] beats [[...optional]].
     */
    public static function compare(self $a, self $b): int
    {
        $count = max(count($a->segments), count($b->segments));

        for ($i = 0; $i < $count; $i++) {
            $x = $a->segments[$i] ?? null;
            $y = $b->segments[$i] ?? null;

            if ($x === null || $y === null) {
                return $x === null ? -1 : 1;
            }

            if ($x[0] !== $y[0]) {
                return $x[0] <=> $y[0];
            }
        }

        return 0;
    }

    /**
     * @param list<string> $uri
     * @return array<string, string|list<string>>|null
     */
    public function match(array $uri): ?array
    {
        $params = [];

        foreach ($this->segments as $i => [$type, $value]) {
            switch ($type) {
                case self::STATIC:
                    if (($uri[$i] ?? null) !== $value) {
                        return null;
                    }
                    break;

                case self::PARAM:
                    if (($uri[$i] ?? '') === '') {
                        return null;
                    }
                    $params[$value] = $uri[$i];
                    break;

                case self::CATCH_ALL:
                    $rest = array_slice($uri, $i);
                    if ($rest === []) {
                        return null;
                    }
                    $params[$value] = $rest;
                    return $params;

                case self::OPTIONAL_CATCH_ALL:
                    $params[$value] = array_slice($uri, $i);
                    return $params;
            }
        }

        return count($this->segments) === count($uri) ? $params : null;
    }

    /**
     * Build a path for this route. Leftover params become the query string.
     *
     * @param array<string, mixed> $params
     */
    public function url(array $params = []): string
    {
        $parts = [];

        foreach ($this->segments as [$type, $value]) {
            if ($type === self::STATIC) {
                $parts[] = rawurlencode($value);
                continue;
            }

            if (!array_key_exists($value, $params)) {
                if ($type === self::OPTIONAL_CATCH_ALL) {
                    continue;
                }

                throw new InvalidArgumentException("Missing parameter [{$value}] for page [{$this->pattern()}].");
            }

            $param = $params[$value];
            unset($params[$value]);

            if ($type === self::PARAM) {
                $parts[] = rawurlencode((string) $param);
            } else {
                $values = is_array($param) ? $param : explode('/', trim((string) $param, '/'));
                foreach ($values as $piece) {
                    if ((string) $piece !== '') {
                        $parts[] = rawurlencode((string) $piece);
                    }
                }
            }
        }

        $url = '/' . implode('/', $parts);

        return $params ? $url . '?' . http_build_query($params) : $url;
    }

    /**
     * Human-readable pattern, e.g. /users/[id]/posts/[...slug].
     */
    public function pattern(): string
    {
        $parts = array_map(static fn (array $segment): string => match ($segment[0]) {
            self::STATIC => $segment[1],
            self::PARAM => "[{$segment[1]}]",
            self::CATCH_ALL => "[...{$segment[1]}]",
            self::OPTIONAL_CATCH_ALL => "[[...{$segment[1]}]]",
        }, $this->segments);

        return '/' . implode('/', $parts);
    }

    /**
     * Shape of the route, ignoring param names. Two pages with the same signature conflict.
     */
    public function signature(): string
    {
        return implode('/', array_map(
            static fn (array $segment): string => $segment[0] === self::STATIC ? 's:' . $segment[1] : (string) $segment[0],
            $this->segments,
        ));
    }

    public function metadata(): PageMetadata
    {
        return $this->metadata ??= PageMetadata::for($this->file);
    }

    public function name(): ?string
    {
        return $this->metadata()->name;
    }

    /** @return list<string> */
    public function methods(): array
    {
        return $this->metadata()->methods ?: ['GET'];
    }

    /**
     * Middleware from every _middleware.php between the mount root and this page, outermost first.
     *
     * @return list<mixed>
     */
    public function directoryMiddleware(): array
    {
        $dirs = [];
        $dir = dirname($this->file);

        while (true) {
            $dirs[] = $dir;
            $parent = dirname($dir);

            if ($dir === $this->mount->path || $parent === $dir) {
                break;
            }

            $dir = $parent;
        }

        $middleware = [];
        foreach (array_reverse($dirs) as $dir) {
            $file = $dir . DIRECTORY_SEPARATOR . '_middleware.php';

            if (is_file($file)) {
                array_push($middleware, ...(self::$directoryMiddleware[$file] ??= self::loadDirectoryMiddleware($file)));
            }
        }

        return $middleware;
    }

    /** @return list<mixed> */
    private static function loadDirectoryMiddleware(string $file): array
    {
        $middleware = (static fn () => require $file)();

        if (!is_array($middleware)) {
            throw new LogicException("[{$file}] must return an array of middleware.");
        }

        return Quire::flatten([$middleware]);
    }

    /** @return array{0: int, 1: string} */
    private static function parseSegment(string $part, string $file): array
    {
        $name = '([A-Za-z_][A-Za-z0-9_]*)';

        return match (true) {
            (bool) preg_match("/^\\[\\[\\.\\.\\.{$name}\\]\\]$/", $part, $m) => [self::OPTIONAL_CATCH_ALL, $m[1]],
            (bool) preg_match("/^\\[\\.\\.\\.{$name}\\]$/", $part, $m) => [self::CATCH_ALL, $m[1]],
            (bool) preg_match("/^\\[{$name}\\]$/", $part, $m) => [self::PARAM, $m[1]],
            str_contains($part, '[') || str_contains($part, ']') => throw new LogicException("Invalid segment [{$part}] in [{$file}]."),
            default => [self::STATIC, $part],
        };
    }
}
