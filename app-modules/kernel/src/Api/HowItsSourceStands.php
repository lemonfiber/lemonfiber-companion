<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Whether the source an installed plugin came from can still be reached, as the stack last asked.
 *
 * Three answers, and the two that are not *reachable* each carry the stack's
 * reason: a source the stack could not reach is not one it was set not to ask.
 */
final readonly class HowItsSourceStands
{
    private function __construct(private WhereItsSourceStands $standing, private string $why) {}

    /** The stack said nothing of it. */
    public static function notSaid(): self
    {
        return new self(WhereItsSourceStands::NotSaid, '');
    }

    /** The stack reached it. */
    public static function reachable(): self
    {
        return new self(WhereItsSourceStands::Reachable, '');
    }

    /** The stack tried and could not reach it, and this is why; blank is refused. */
    public static function unreachable(string $why): self
    {
        return new self(WhereItsSourceStands::Unreachable, self::said($why));
    }

    /** The stack did not ask it, and this is why; blank is refused. */
    public static function unasked(string $why): self
    {
        return new self(WhereItsSourceStands::Unasked, self::said($why));
    }

    /** How it stands. */
    public function standing(): WhereItsSourceStands
    {
        return $this->standing;
    }

    /** The stack's reason, or empty where it was reached or said nothing. */
    public function why(): string
    {
        return $this->why;
    }

    private static function said(string $why): string
    {
        $trimmed = trim($why);

        if ($trimmed === '') {
            throw PluginSaysNothing::about('why');
        }

        return $trimmed;
    }
}
