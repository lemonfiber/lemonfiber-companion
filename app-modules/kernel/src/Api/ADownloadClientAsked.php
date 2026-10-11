<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One download client, as pausing or resuming the downloads found it.
 *
 * What it said it was doing before it was asked, what it read back after, and,
 * where it could not be reached, why, in its own words where it gave any. A
 * client that could not be reached is named rather than left out, so the
 * operator knows which one is still doing whatever it was doing.
 */
final readonly class ADownloadClientAsked
{
    private function __construct(
        private string $client,
        private WhatADownloadClientSaid $was,
        private WhatADownloadClientSaid $now,
        private bool $reached,
        private string $why,
    ) {}

    /** A client the stack reached, with what it said before and after. */
    public static function reached(string $client, WhatADownloadClientSaid $was, WhatADownloadClientSaid $now): self
    {
        return new self(self::named($client), $was, $now, reached: true, why: '');
    }

    /** A client the stack could not reach, with what it said before where it said, and why, or nothing where it gave no reason. */
    public static function unreached(string $client, WhatADownloadClientSaid $was, string $why): self
    {
        return new self(self::named($client), $was, WhatADownloadClientSaid::Unsaid, reached: false, why: trim($why));
    }

    /** The client, by the name the stack knows it under. */
    public function client(): string
    {
        return $this->client;
    }

    /** What it said it was doing before it was asked. */
    public function was(): WhatADownloadClientSaid
    {
        return $this->was;
    }

    /** What it read back after it was asked; unsaid on a rehearsal and where it could not be reached. */
    public function now(): WhatADownloadClientSaid
    {
        return $this->now;
    }

    /** Whether the stack reached it. */
    public function wasReached(): bool
    {
        return $this->reached;
    }

    /** Why it could not be reached, in its own words, or empty where it was reached or gave no reason. */
    public function whyItWasNotReached(): string
    {
        return $this->why;
    }

    private static function named(string $client): string
    {
        if (trim($client) === '') {
            throw PausingSaysNothing::about('client');
        }

        return $client;
    }
}
