<?php

declare(strict_types=1);

namespace Modules\Design\Api;

use Closure;

/**
 * The resolvers EDGE asks for.
 *
 * `TailwindParser` hands a resolver a bare token name, whatever a template
 * wrote after `bg-theme-`, and reads null as "this token means nothing here".
 * Null lives inside these closures and nowhere else: the parser's contract
 * wants it, and nothing else a module publishes answers with it.
 *
 * The composition root registers them; building them reads no file and reaches
 * no network, so a frame arrives without waiting on any of it.
 */
final readonly class Theme
{
    /**
     * The light resolver: a role's paper-theme hex.
     *
     * @return Closure(string): (?string)
     */
    public static function resolver(): Closure
    {
        return static fn(string $token): ?string => ThemeToken::tryFrom($token)?->light();
    }

    /**
     * The dark resolver: a role's ink-theme hex, which the parser attaches to
     * every theme class as its dark companion.
     *
     * @return Closure(string): (?string)
     */
    public static function darkResolver(): Closure
    {
        return static fn(string $token): ?string => ThemeToken::tryFrom($token)?->dark();
    }
}
