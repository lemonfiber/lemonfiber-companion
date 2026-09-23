<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Where putting a change back stops short: why, and what to do instead.
 *
 * One value rather than two optional fields, because that is the shape the
 * stack gives it. Both halves come from one refusal to go further, which always
 * has a reason and may have a suggestion — so a suggestion with no reason is not
 * a smaller answer but an impossible one, and this type cannot hold it.
 *
 * **This is not why the change was made.** The contract's `because` is *why it
 * could not go further*, a fact about putting it back, and it is named for that
 * here. A screen that printed it as the reason for the change would answer a
 * question the stack did not.
 */
final readonly class WhereItStopsShort
{
    private function __construct(private string $because, private ?string $instead = null) {}

    /** It stops short for this reason, and there is nothing to suggest. */
    public static function because(string $why): self
    {
        return new self(self::said($why, 'because'));
    }

    /**
     * It stops short for this reason, and this is what to do instead.
     *
     * Its own constructor rather than a nullable second parameter, which is
     * `C2`'s cure: *there is a suggestion* and *there is not* are different
     * facts, and a caller reaches one or the other.
     */
    public static function suggesting(string $why, string $instead): self
    {
        return new self(self::said($why, 'because'), self::said($instead, 'instead'));
    }

    /** Why putting it back could not go further. */
    public function why(): string
    {
        return $this->because;
    }

    /**
     * Say what to do instead, or say that there is nothing to suggest.
     *
     * Two arms rather than a nullable getter, for {@see Unattended::missing()}'s
     * reason: a screen handed a null prints a heading with nothing under it.
     *
     * @template TSaid of object
     * @template TNothing of object
     *
     * @param  Closure(string): TSaid $said
     * @param  Closure(): TNothing    $nothing
     * @return TSaid|TNothing
     */
    public function instead(Closure $said, Closure $nothing): object
    {
        return $this->instead === null ? $nothing() : $said($this->instead);
    }

    /** One sentence less the space around it, or the refusal naming which. */
    private static function said(string $sentence, string $field): string
    {
        $shown = trim($sentence);

        if ($shown === '') {
            throw ChangeSaysNothing::about($field);
        }

        return $shown;
    }
}
