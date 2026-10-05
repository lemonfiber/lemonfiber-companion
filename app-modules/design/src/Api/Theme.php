<?php

declare(strict_types=1);

namespace Modules\Design\Api;

use Closure;

/**
 * The resolver EDGE asks for, for one theme.
 *
 * `TailwindParser` hands a resolver a bare token name, whatever a template
 * wrote after `bg-theme-`, and reads null as "this token means nothing here".
 * Null lives inside this closure and nowhere else: the parser's contract
 * wants it, and nothing else a module publishes answers with it.
 *
 * The parser holds a light and a dark resolver; this one is handed to both,
 * because a theme paints the same whatever the phone is set to. The
 * composition root hands it over for the screen on view. Building it reads no
 * file and reaches no network, so a frame arrives without waiting on any of it.
 */
final readonly class Theme
{
    /**
     * A role's hex in this theme.
     *
     * @return Closure(string): (?string)
     */
    public static function resolver(WhoseTheme $theme): Closure
    {
        return static fn(string $token): ?string => ThemeToken::tryFrom($token)?->in($theme);
    }
}
