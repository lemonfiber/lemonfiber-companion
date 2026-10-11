<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function trim;

/**
 * What pausing or resuming every download client came to, rehearsed or carried out.
 *
 * Every client the stack runs, in the order it declares them; which of the two
 * was asked; whether it was a rehearsal; the offer a yes quotes; and, on a
 * resume, the caution where a spent cap had stopped the clients and will stop
 * them again.
 *
 * **The rehearsal is the offer.** It names every client and what each said
 * it was doing, and a yes carrying that name back acts on those clients in
 * those states or is refused, so what is paused is what was shown.
 *
 * @implements IteratorAggregate<int, ADownloadClientAsked>
 */
final readonly class WhatPausingComesTo implements IteratorAggregate
{
    /** @param list<ADownloadClientAsked> $clients */
    private function __construct(
        private WhatToDoWithTheDownloads $asked,
        private WhetherItWasRehearsed $rehearsed,
        private string $offer,
        private string $caution,
        private array $clients,
    ) {}

    /** The stack's report, the caution empty where it gave none. */
    public static function said(
        WhatToDoWithTheDownloads $asked,
        WhetherItWasRehearsed $rehearsed,
        string $offer,
        string $caution,
        ADownloadClientAsked ...$clients,
    ): self {
        if (trim($offer) === '') {
            throw PausingSaysNothing::about('offer');
        }

        return new self($asked, $rehearsed, $offer, trim($caution), array_values($clients));
    }

    /** Which of the two was asked for. */
    public function asked(): WhatToDoWithTheDownloads
    {
        return $this->asked;
    }

    /** Whether it was a rehearsal, which asked no client anything. */
    public function wasRehearsed(): WhetherItWasRehearsed
    {
        return $this->rehearsed;
    }

    /** The offer, by the name the stack gave it, which a yes quotes back. */
    public function agreement(): string
    {
        return $this->offer;
    }

    /** What a resume runs into where a spent cap had stopped the clients, or empty. */
    public function caution(): string
    {
        return $this->caution;
    }

    /** Whether the stack runs any download client at all. */
    public function hasClients(): bool
    {
        return $this->clients !== [];
    }

    /** @return Traversable<int, ADownloadClientAsked> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->clients);
    }
}
