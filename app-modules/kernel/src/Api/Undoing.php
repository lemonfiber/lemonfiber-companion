<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Whether a repair can be taken back.
 *
 * A repair offer states three things, and this is the third. It
 * is an enum rather than the contract's `reversible: bool` for two reasons that
 * both come out on a screen. A boolean crossing a signature says nothing at the
 * call site about which way round it goes (`D5`), and a boolean has no word: a
 * checkbox is not a sentence, and what an operator needs before agreeing to
 * something permanent is a sentence.
 *
 * Declared permanent first, like `Conclusion` and `Overall`, so the case that
 * needs saying is the one a reader meets first.
 */
enum Undoing: string
{
    /** Once this is done there is no putting it back. */
    case Permanent = 'permanent';

    /** This can be undone afterwards. */
    case Possible = 'possible';

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is how every word in this app reaches the
     * catalogue. Under `health.undoing.` rather than `health.` alone because
     * `permanent` and `possible` are adjectives that would read as findings on
     * their own — the group is what says they are about taking something back.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('health.undoing.%s', $this->value);
    }
}
