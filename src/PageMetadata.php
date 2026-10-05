<?php

declare(strict_types=1);

namespace Quire;

/**
 * Metadata a page declares about itself at the top of the file:
 *
 *   <?php
 *   use function Quire\{name, middleware, methods};
 *
 *   name('users.show');
 *   middleware(['auth']);
 *   methods('GET', 'POST');
 *   ?>
 *
 * Blade pages can use the same `<?php ... ?>` block or an `@php ... @endphp` block.
 *
 * Only the leading run of `use`, `name()`, `middleware()` and `methods()` statements is read,
 * so metadata is collected without executing the rest of the page.
 */
final class PageMetadata
{
    private const FUNCTIONS = ['name', 'middleware', 'methods'];

    public ?string $name = null;

    /** @var list<mixed> */
    public array $middleware = [];

    /** @var list<string> */
    public array $methods = [];

    private static ?self $collecting = null;

    /** @var array<string, array{0: int|false, 1: self}> */
    private static array $cache = [];

    /**
     * The metadata currently being collected, or null while a page renders normally.
     */
    public static function collecting(): ?self
    {
        return self::$collecting;
    }

    public static function for(string $file): self
    {
        $mtime = filemtime($file);

        if (isset(self::$cache[$file]) && self::$cache[$file][0] === $mtime) {
            return self::$cache[$file][1];
        }

        $metadata = new self();
        $code = self::extract((string) file_get_contents($file), $file);

        if ($code !== null) {
            self::$collecting = $metadata;

            try {
                (static function (string $__code): void {
                    eval($__code);
                })($code);
            } finally {
                self::$collecting = null;
            }
        }

        self::$cache[$file] = [$mtime, $metadata];

        return $metadata;
    }

    /**
     * Pull the leading metadata statements out of a page's source.
     */
    public static function extract(string $source, string $file): ?string
    {
        $tokens = token_get_all(self::normalize($source));
        $count = count($tokens);

        if ($count === 0 || !is_array($tokens[0]) || $tokens[0][0] !== T_OPEN_TAG) {
            return null;
        }

        $uses = [];
        $calls = [];
        $i = 1;

        while ($i < $count) {
            $token = $tokens[$i];
            $id = is_array($token) ? $token[0] : null;

            if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;
            } elseif ($id === T_DECLARE) {
                [, $i] = self::statement($tokens, $i, $file);
            } elseif ($id === T_USE) {
                [$uses[], $i] = self::statement($tokens, $i, $file);
            } elseif (self::isMetadataCall($tokens, $i)) {
                [$calls[], $i] = self::statement($tokens, $i, $file);
            } else {
                break;
            }
        }

        return $calls ? implode("\n", [...$uses, ...$calls]) : null;
    }

    /**
     * Template pages (Blade) may lead with {{-- comments --}} and declare metadata in an @php ... @endphp block.
     */
    private static function normalize(string $source): string
    {
        $trimmed = (string) preg_replace('/^(?:\s*\{\{--.*?--\}\})*\s*/s', '', $source);

        if (preg_match('/^@php\s(.*?)@endphp/s', $trimmed, $m)) {
            return "<?php\n{$m[1]}\n?>";
        }

        return str_starts_with($trimmed, '<?php') ? $trimmed : $source;
    }

    /**
     * @param array<int, mixed> $tokens
     * @return array{0: string, 1: int} The statement source and the index after it.
     */
    private static function statement(array $tokens, int $i, string $file): array
    {
        $code = '';
        $depth = 0;
        $count = count($tokens);

        for (; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                $code .= $token;

                if (in_array($token, ['(', '[', '{'], true)) {
                    $depth++;
                } elseif (in_array($token, [')', ']', '}'], true)) {
                    $depth--;
                } elseif ($token === ';' && $depth === 0) {
                    return [$code, $i + 1];
                }

                continue;
            }

            [$id, $text] = $token;

            if ($id === T_CLOSE_TAG && $depth === 0) {
                return [$code . ';', $i];
            }

            $code .= match ($id) {
                // Magic constants would otherwise point at "eval()'d code".
                T_DIR => var_export(dirname($file), true),
                T_FILE => var_export($file, true),
                default => $text,
            };

            if ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
            }
        }

        return [$code . ';', $i];
    }

    /** @param array<int, mixed> $tokens */
    private static function isMetadataCall(array $tokens, int $i): bool
    {
        $token = $tokens[$i];

        if (!is_array($token) || !in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
            return false;
        }

        $name = strtolower(substr((string) strrchr('\\' . $token[1], '\\'), 1));

        if (!in_array($name, self::FUNCTIONS, true)) {
            return false;
        }

        for ($j = $i + 1; isset($tokens[$j]); $j++) {
            if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_WHITESPACE) {
                return $tokens[$j] === '(';
            }
        }

        return false;
    }
}
