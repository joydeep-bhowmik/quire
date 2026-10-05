<?php

declare(strict_types=1);

namespace Quire;

use Closure;
use LogicException;

/**
 * Runs a request through middleware, onion style. Each middleware receives
 * ($request, $next, ...$args) and must return a response (usually $next($request)).
 */
final class Pipeline
{
    public function __construct(private readonly Quire $quire)
    {
    }

    /** @param list<mixed> $stack */
    public function run(array $stack, Request $request, Closure $destination): Response
    {
        $next = $destination;

        foreach (array_reverse($stack) as $entry) {
            [$handler, $args] = $this->quire->resolveMiddleware($entry);
            $inner = $next;

            $next = static function (Request $request) use ($handler, $args, $inner, $entry): Response {
                $result = $handler($request, $inner, ...$args);

                if ($result === null) {
                    throw new LogicException(sprintf('Middleware [%s] did not return a response.', Quire::describe($entry)));
                }

                return Response::from($result);
            };
        }

        return $next($request);
    }
}
