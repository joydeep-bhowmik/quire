<?php

declare(strict_types=1);

namespace Quire;

use Closure;
use InvalidArgumentException;
use LogicException;
use Throwable;

final class Quire
{
    private static ?self $instance = null;

    /** @var list<Mount> */
    private array $mounts = [];

    /** @var list<mixed> */
    private array $middleware = [];

    /** @var array<string, mixed> */
    private array $aliases = [];

    /** @var list<Route>|null */
    private ?array $routes = null;

    /** @var array<string, Route>|null */
    private ?array $named = null;

    private string $base = '';

    private bool $debug = false;

    private ?Request $request = null;

    private ?Closure $resolver = null;

    /** @var array<string, Closure|null> File extension => renderer, longest first. Null means plain PHP. */
    private array $renderers = ['.php' => null];

    /** @var list<string> Template extensions skipped until a renderer is registered, so their source is never served as PHP. */
    private array $ignored = ['.blade.php'];

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function instance(): self
    {
        return self::$instance ?? throw new LogicException('No Quire instance has been created.');
    }

    /**
     * Register a directory of pages. Returns the mount so you can chain ->uri() and ->middleware().
     */
    public function path(string $directory): Mount
    {
        $mount = new Mount($directory, $this->flush(...));
        $this->mounts[] = $mount;
        $this->flush();

        return $mount;
    }

    /**
     * Global middleware, run for every matched page.
     */
    public function middleware(mixed ...$middleware): self
    {
        array_push($this->middleware, ...self::flatten($middleware));

        return $this;
    }

    /**
     * Give a middleware a short name, e.g. alias('auth', AuthMiddleware::class).
     */
    public function alias(string $name, mixed $middleware): self
    {
        $this->aliases[$name] = $middleware;

        return $this;
    }

    /** @param array<string, mixed> $aliases */
    public function aliases(array $aliases): self
    {
        foreach ($aliases as $name => $middleware) {
            $this->alias($name, $middleware);
        }

        return $this;
    }

    /**
     * Customise how middleware class names are instantiated (e.g. hand off to a DI container).
     */
    public function resolveUsing(Closure $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Serve pages with another file extension through a renderer:
     *
     *   $quire->extension('.blade.php', new BladeRenderer($viewPaths, $cachePath));
     *
     * The renderer receives ($file, $vars) and returns a string, array or Response.
     */
    public function extension(string $extension, callable $renderer): self
    {
        $extension = '.' . ltrim($extension, '.');
        $this->renderers[$extension] = Closure::fromCallable($renderer);
        $this->ignored = array_values(array_diff($this->ignored, [$extension]));

        // Longest first, so "about.blade.php" is claimed by ".blade.php" before ".php".
        uksort($this->renderers, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $this->flush();

        return $this;
    }

    /** @return list<string> */
    public function extensions(): array
    {
        return array_keys($this->renderers);
    }

    /**
     * Set the base path when the app lives in a subdirectory, e.g. base('/my-app').
     */
    public function base(string $base): self
    {
        $base = '/' . trim($base, '/');
        $this->base = $base === '/' ? '' : $base;

        return $this;
    }

    public function debug(bool $debug = true): self
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * The request currently being handled.
     */
    public function request(): ?Request
    {
        return $this->request;
    }

    public function run(?Request $request = null): void
    {
        $request ??= Request::fromGlobals();

        $this->handle($request)->send($request->method === 'HEAD');
    }

    public function handle(Request $request): Response
    {
        $previous = $this->request;
        $previousInstance = self::$instance;
        $this->request = $request;
        self::$instance = $this; // route(), url() and request() inside pages refer to this router

        try {
            return $this->dispatch($request);
        } catch (ResponseException $e) {
            return $e->response;
        } catch (HttpException $e) {
            return $this->renderError($e, $request);
        } catch (Throwable $e) {
            return $this->renderError(
                new HttpException(500, $this->debug ? $e->getMessage() : '', previous: $e),
                $request,
            );
        } finally {
            $this->request = $previous;
            self::$instance = $previousInstance;
        }
    }

    /**
     * Build the URL for a named page.
     *
     * @param array<string, mixed> $params
     */
    public function url(string $name, array $params = []): string
    {
        $route = $this->named()[$name] ?? throw new InvalidArgumentException("Route [{$name}] is not defined.");

        return $this->base . $route->url($params);
    }

    public function has(string $name): bool
    {
        return isset($this->named()[$name]);
    }

    /**
     * Prefix a path with the base path.
     */
    public function to(string $path): string
    {
        return $this->base . '/' . ltrim($path, '/');
    }

    /**
     * All discovered routes, most specific first.
     *
     * @return list<Route>
     */
    public function routes(): array
    {
        if ($this->routes !== null) {
            return $this->routes;
        }

        $routes = [];
        foreach ($this->mounts as $mount) {
            array_push($routes, ...$mount->discover($this->extensions(), $this->ignored));
        }

        $seen = [];
        foreach ($routes as $route) {
            $signature = $route->signature();

            if (isset($seen[$signature])) {
                throw new LogicException(sprintf(
                    'Pages [%s] and [%s] resolve to the same route.',
                    $seen[$signature]->file,
                    $route->file,
                ));
            }

            $seen[$signature] = $route;
        }

        usort($routes, [Route::class, 'compare']);

        return $this->routes = $routes;
    }

    /**
     * Render a PHP file with the given variables in scope.
     *
     * @param array<string, mixed> $vars
     */
    public function render(string $file, array $vars = []): Response
    {
        $level = ob_get_level();
        ob_start();

        try {
            $result = (static function (string $__file, array $__vars): mixed {
                extract($__vars, EXTR_SKIP);

                return require $__file;
            })($file, $vars);

            $output = (string) ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || $result instanceof \JsonSerializable) {
            return Response::json($result);
        }

        return Response::html($output);
    }

    /**
     * Turn a middleware definition into a callable and its arguments.
     *
     * @internal
     * @return array{0: Closure, 1: list<string>}
     */
    public function resolveMiddleware(mixed $middleware): array
    {
        $original = $middleware;
        $args = [];

        if (is_string($middleware) && !str_contains($middleware, '::')) {
            [$key, $argString] = array_pad(explode(':', $middleware, 2), 2, null);
            $args = $argString === null ? [] : explode(',', $argString);
            $middleware = $this->aliases[$key] ?? $key;

            if (is_string($middleware) && !class_exists($middleware)) {
                throw new InvalidArgumentException("Unknown middleware [{$original}]. Did you forget to register an alias?");
            }
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $middleware = $this->resolver ? ($this->resolver)($middleware) : new $middleware();
        }

        if (is_object($middleware) && !$middleware instanceof Closure && method_exists($middleware, 'handle')) {
            $middleware = $middleware->handle(...);
        }

        if (!is_callable($middleware)) {
            throw new InvalidArgumentException(sprintf('Unable to resolve middleware [%s].', self::describe($original)));
        }

        return [Closure::fromCallable($middleware), $args];
    }

    /** @internal */
    public static function describe(mixed $value): string
    {
        return is_string($value) ? $value : get_debug_type($value);
    }

    /**
     * @param array<mixed> $items
     * @return list<mixed>
     */
    public static function flatten(array $items): array
    {
        $flat = [];

        foreach ($items as $item) {
            if (is_array($item) && !is_callable($item)) {
                array_push($flat, ...array_values($item));
            } else {
                $flat[] = $item;
            }
        }

        return $flat;
    }

    private function dispatch(Request $request): Response
    {
        $path = $this->stripBase($request->path);
        $segments = Route::splitUri($path);
        $allowed = [];

        foreach ($this->routes() as $route) {
            $params = $route->match($segments);

            if ($params === null) {
                continue;
            }

            $methods = $route->methods();

            if (in_array($request->method, $methods, true)
                || ($request->method === 'HEAD' && in_array('GET', $methods, true))) {
                return $this->runRoute($route, $request, $params, $path);
            }

            array_push($allowed, ...$methods);
        }

        if ($allowed) {
            throw new HttpException(405, headers: ['Allow' => implode(', ', array_unique($allowed))]);
        }

        throw new HttpException(404);
    }

    /** @param array<string, string|list<string>> $params */
    private function runRoute(Route $route, Request $request, array $params, string $path): Response
    {
        $request->params = $params;
        $request->route = $route;

        $stack = [
            ...$this->middleware,
            ...$route->mount->middlewareFor($path),
            ...$route->directoryMiddleware(),
            ...$route->metadata()->middleware,
        ];

        $page = function (Request $request) use ($route, $params): Response {
            try {
                return $this->renderFile($route->file, $route->extension, [...$params, 'request' => $request]);
            } catch (ResponseException $e) {
                return $e->response;
            }
        };

        return (new Pipeline($this))->run($stack, $request, $page);
    }

    private function renderError(HttpException $e, Request $request): Response
    {
        $mount = $request->route?->mount ?? $this->mountFor($this->stripBase($request->path));
        $file = null;
        $extension = '.php';

        // _errors/404.php (or .blade.php) first, then the catch-all _errors/error.php.
        $directory = $mount ? $mount->path . DIRECTORY_SEPARATOR . '_errors' . DIRECTORY_SEPARATOR : null;

        foreach ($directory ? [(string) $e->status, 'error'] : [] as $candidate) {
            foreach ($this->extensions() as $ext) {
                if (is_file($path = $directory . $candidate . $ext)) {
                    [$file, $extension] = [$path, $ext];
                    break 2;
                }
            }
        }

        try {
            $response = $file
                ? $this->renderFile($file, $extension, [
                    'request' => $request,
                    'status' => $e->status,
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ])
                : Response::text($this->defaultErrorBody($e));
            $response->status = $e->status;
        } catch (Throwable $inner) {
            $response = Response::text($this->debug ? "500 Server Error\n\n{$inner}" : '500 Server Error', 500);
        }

        foreach ($e->headers as $name => $value) {
            $response->header($name, $value);
        }

        return $response;
    }

    /** @param array<string, mixed> $vars */
    private function renderFile(string $file, string $extension, array $vars): Response
    {
        $renderer = $this->renderers[$extension] ?? null;

        if ($renderer === null) {
            return $this->render($file, $vars);
        }

        try {
            return Response::from($renderer($file, $vars));
        } catch (Throwable $e) {
            // Template engines wrap exceptions (Blade throws ViewException); dig out abort()/respond().
            for ($inner = $e; $inner !== null; $inner = $inner->getPrevious()) {
                if ($inner instanceof HttpException || $inner instanceof ResponseException) {
                    throw $inner;
                }
            }

            throw $e;
        }
    }

    private function defaultErrorBody(HttpException $e): string
    {
        $body = "{$e->status} {$e->getMessage()}";

        if ($this->debug && $e->getPrevious()) {
            $body .= "\n\n" . $e->getPrevious();
        }

        return $body;
    }

    private function mountFor(string $path): ?Mount
    {
        $best = null;

        foreach ($this->mounts as $mount) {
            $uri = $mount->getUri();
            $matches = $uri === '/' || $path === $uri || str_starts_with($path, $uri . '/');

            if ($matches && ($best === null || strlen($uri) > strlen($best->getUri()))) {
                $best = $mount;
            }
        }

        return $best;
    }

    private function stripBase(string $path): string
    {
        if ($this->base !== '' && ($path === $this->base || str_starts_with($path, $this->base . '/'))) {
            return substr($path, strlen($this->base)) ?: '/';
        }

        return $path;
    }

    /** @return array<string, Route> */
    private function named(): array
    {
        if ($this->named !== null) {
            return $this->named;
        }

        $named = [];
        foreach ($this->routes() as $route) {
            $name = $route->metadata()->name;

            if ($name === null) {
                continue;
            }

            if (isset($named[$name])) {
                throw new LogicException("Route name [{$name}] is used by both [{$named[$name]->file}] and [{$route->file}].");
            }

            $named[$name] = $route;
        }

        return $this->named = $named;
    }

    private function flush(): void
    {
        $this->routes = null;
        $this->named = null;
    }
}
