<?php

declare(strict_types=1);

namespace Quire;

use Closure;
use FilesystemIterator;
use InvalidArgumentException;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * A directory of pages served under a URI prefix.
 */
final class Mount
{
    public readonly string $path;

    private string $uri = '/';

    /** @var list<array{0: string, 1: list<mixed>}> */
    private array $middleware = [];

    public function __construct(string $path, private readonly Closure $onChange)
    {
        $real = realpath($path);

        if ($real === false || !is_dir($real)) {
            throw new InvalidArgumentException("Pages directory [{$path}] does not exist.");
        }

        $this->path = $real;
    }

    /**
     * Serve this directory under a URI prefix, e.g. uri('/admin').
     */
    public function uri(string $uri): self
    {
        $this->uri = '/' . trim($uri, '/');
        ($this->onChange)();

        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Middleware by path pattern, relative to this mount's URI:
     *
     *   ->middleware(['*' => ['log'], 'admin/*' => ['auth']])
     *
     * Plain list entries apply to every page.
     *
     * @param array<int|string, mixed> $middleware
     */
    public function middleware(array $middleware): self
    {
        foreach ($middleware as $pattern => $entries) {
            if (is_int($pattern)) {
                $this->middleware[] = ['*', [$entries]];
            } else {
                $this->middleware[] = [$pattern, is_array($entries) && !is_callable($entries) ? array_values($entries) : [$entries]];
            }
        }

        return $this;
    }

    /** @return list<mixed> */
    public function middlewareFor(string $path): array
    {
        $relative = trim($this->uri === '/' ? $path : substr($path, strlen($this->uri)), '/');
        $matched = [];

        foreach ($this->middleware as [$pattern, $entries]) {
            $regex = '#^' . str_replace('\*', '.*', preg_quote(trim($pattern, '/'), '#')) . '$#';

            if (preg_match($regex, $relative)) {
                array_push($matched, ...$entries);
            }
        }

        return $matched;
    }

    /**
     * @param list<string> $extensions Longest first, e.g. ['.blade.php', '.php'].
     * @param list<string> $ignored Extensions to skip entirely (templates with no renderer).
     * @return list<Route>
     */
    public function discover(array $extensions = ['.php'], array $ignored = []): array
    {
        $files = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS),
            // Anything starting with "_" or "." (files or folders) is private: partials, layouts, _middleware.php...
            static fn (SplFileInfo $file): bool => !in_array($file->getFilename()[0], ['_', '.'], true),
        ));

        $routes = [];
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            foreach ([...$ignored, ...$extensions] as $extension) {
                if (str_ends_with($file->getFilename(), $extension)) {
                    if (in_array($extension, $ignored, true)) {
                        break;
                    }

                    $routes[] = Route::fromFile($this, $file->getPathname(), $extension);
                    break;
                }
            }
        }

        return $routes;
    }
}
