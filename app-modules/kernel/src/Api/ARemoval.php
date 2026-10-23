<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What taking somebody out of the household costs, or what it did, as the stack answered.
 *
 * Every figure is knowable before anybody is removed, which is the point of
 * the reading nobody has agreed to yet: how many of their requests go with
 * them, what becomes of their watch history, whether they ask through the
 * request service at all, how far a removal reached, and what the stack found
 * on the way.
 *
 * A reading carries the name the stack gave it, which the yes quotes back so
 * the stack refuses a yes given for a household that has moved since.
 *
 * **Which constructor answers is the stack's `confirmed`**, so a reading is
 * never read as somebody taken out, and a removal carried out is never
 * offered to be agreed to again.
 */
final readonly class ARemoval
{
    private function __construct(
        private SomebodyInTheHousehold $who,
        private bool $carriedOut,
        private int $requests,
        private bool $asks,
        private HowFarTheRemovalReached $revoked,
        private WhatTheRemovalFound $findings,
        private string $watchHistory,
        private string $offer,
    ) {}

    /**
     * What taking them out would cost, with nobody taken out, under the name the stack gave the reading.
     *
     * A count below none is refused, and so is a reading the stack left unnamed, which no yes could quote.
     */
    public static function described(
        SomebodyInTheHousehold $who,
        int $requests,
        bool $asksThroughTheRequestService,
        HowFarTheRemovalReached $revoked,
        WhatTheRemovalFound $findings,
        string $watchHistory,
        string $offer,
    ): self {
        if (trim($offer) === '') {
            throw RemovalSaysNothing::about('offer');
        }

        return new self($who, carriedOut: false, requests: self::counted($requests), asks: $asksThroughTheRequestService, revoked: $revoked, findings: $findings, watchHistory: self::said($watchHistory), offer: trim($offer));
    }

    /** What taking them out did; a count below none is refused. */
    public static function carriedOut(
        SomebodyInTheHousehold $who,
        int $requests,
        bool $asksThroughTheRequestService,
        HowFarTheRemovalReached $revoked,
        WhatTheRemovalFound $findings,
        string $watchHistory,
    ): self {
        return new self($who, carriedOut: true, requests: self::counted($requests), asks: $asksThroughTheRequestService, revoked: $revoked, findings: $findings, watchHistory: self::said($watchHistory), offer: '');
    }

    /** Who it is about, by the name the media server holds their account under. */
    public function who(): SomebodyInTheHousehold
    {
        return $this->who;
    }

    /** Whether it was carried out, rather than only described. */
    public function wasCarriedOut(): bool
    {
        return $this->carriedOut;
    }

    /** How many of their requests go with them, which are destroyed rather than handed to anybody. */
    public function requests(): int
    {
        return $this->requests;
    }

    /** Whether the request service holds an account for them at all. */
    public function asksThroughTheRequestService(): bool
    {
        return $this->asks;
    }

    /** How far it reached. */
    public function revoked(): HowFarTheRemovalReached
    {
        return $this->revoked;
    }

    /** What it could not do, and anything else the stack says, in its words. */
    public function findings(): WhatTheRemovalFound
    {
        return $this->findings;
    }

    /** What it does, or did, to their watch history, in the stack's words. */
    public function watchHistory(): string
    {
        return $this->watchHistory;
    }

    /**
     * The name the stack gave the reading, which a yes quotes; `''` once carried out.
     *
     * Published because {@see ARemovalAgreed} carries it back, and for nothing else.
     */
    public function offer(): string
    {
        return $this->offer;
    }

    /** What the stack said about their watch history, refused where it said nothing. */
    private static function said(string $watchHistory): string
    {
        if (trim($watchHistory) === '') {
            throw RemovalSaysNothing::about('watch-history');
        }

        return $watchHistory;
    }

    /** A count of requests, refused below none. */
    private static function counted(int $requests): int
    {
        if ($requests < 0) {
            throw RemovalSaysNothing::below('requests', $requests);
        }

        return $requests;
    }
}
