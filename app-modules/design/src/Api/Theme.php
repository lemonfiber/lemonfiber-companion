<?php

declare(strict_types=1);

namespace Modules\Design\Api;

use Closure;

/**
 * The theme resolver EDGE asks for, which this module is the owner of.
 *
 * `TailwindParser` takes a `callable(string): ?string` and hands it a bare
 * token name — `accent`, `on-surface`, whatever a template wrote after
 * `bg-theme-`. Null is how the parser is told a token means nothing here, and
 * it is the answer for all but the two this surface asserts.
 *
 * Null lives inside the closure and nowhere else. C2 refuses a nullable return
 * on anything a module publishes, because null cannot say which of "not read
 * yet" and "read, and there is nothing" it means — and here the third-party
 * contract genuinely wants the one that C2 is not about. Confining it to the
 * one function written to satisfy that contract is what keeps the rule and the
 * package both intact.
 *
 * The composition root registers it and does not build it:
 *
 *     TailwindParser::setThemeResolver(Theme::resolver());
 *
 * which is a bind rather than work, reads no file and reaches no network, so a
 * frame arrives without waiting on any of it (A9).
 */
final readonly class Theme
{
    /**
     * A resolver over the tokens this surface asserts.
     *
     * @return Closure(string): (?string)
     */
    public static function resolver(): Closure
    {
        return static fn(string $token): ?string => ThemeToken::tryFrom($token)?->hex();
    }
}
