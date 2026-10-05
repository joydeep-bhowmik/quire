<?php

declare(strict_types=1);

namespace Quire;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        string $message = '',
        public readonly array $headers = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : Response::reason($status), $status, $previous);
    }
}
