<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What an invitation writes on the account, in the household's own words.
 *
 * The libraries they may open, how far up the ratings they may watch, what
 * becomes of material with no rating, whether the request service was held to
 * the same decision, and what a limit here is and is not. Together they are the
 * whole of what the operator is deciding.
 */
final readonly class WhatWasGranted
{
    private function __construct(
        private TheLibraries $libraries,
        private WhatBecomesOfUnrated $unrated,
        private WhetherTheyCanAsk $requesting,
        private string $filtering,
        private string $limit,
    ) {}

    /**
     * What the stack said it wrote, or would write.
     *
     * `limit` is empty where no limit was set, and a blank one is refused, as
     * is a blank `filtering`: the sentence saying what a limit is exists for
     * the parent who has just set one.
     */
    public static function granted(
        TheLibraries $libraries,
        WhatBecomesOfUnrated $unrated,
        WhetherTheyCanAsk $requesting,
        string $filtering,
        string $limit,
    ): self {
        if (trim($filtering) === '') {
            throw InvitationSaysNothing::about('filtering');
        }

        if ($limit !== '' && trim($limit) === '') {
            throw InvitationSaysNothing::about('limit');
        }

        return new self($libraries, $unrated, $requesting, $filtering, $limit);
    }

    /** The libraries they may open; none named is every one. */
    public function libraries(): TheLibraries
    {
        return $this->libraries;
    }

    /** What becomes of material the media server has no rating for. */
    public function unrated(): WhatBecomesOfUnrated
    {
        return $this->unrated;
    }

    /** Whether the request service was held to the same decision. */
    public function requesting(): WhetherTheyCanAsk
    {
        return $this->requesting;
    }

    /** What a limit here is, and what it is not, in the stack's words. */
    public function filtering(): string
    {
        return $this->filtering;
    }

    /** How far up the ratings they may watch, in the media server's certificates, or empty where no limit was set. */
    public function limit(): string
    {
        return $this->limit;
    }
}
