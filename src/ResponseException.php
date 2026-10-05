<?php

declare(strict_types=1);

namespace Quire;

use RuntimeException;

/**
 * Thrown by respond() to stop a page and send a response from anywhere,
 * including templates that can't `return` one (e.g. Blade).
 */
final class ResponseException extends RuntimeException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct('Response sent early.');
    }
}
