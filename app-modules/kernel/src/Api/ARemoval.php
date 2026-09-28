<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What taking somebody out of the household costs, or what it did, as the stack answered.
 *
 * Every figure is knowable before anybody is removed, which is the point of
 * the reading nobody has agreed to yet: how many of their requests go with
 * them, whether they ask through the request service at all, how far a
 * removal reached, and what the stack found on the way.
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
    ) {}

    /** What taking them out would cost, with nobody taken out; a count below none is refused. */
    public static function described(
        SomebodyInTheHousehold $who,
        int $requests,
        bool $asksThroughTheRequestService,
        HowFarTheRemovalReached $revoked,
        WhatTheRemovalFound $findings,
    ): self {
        return new self($who, carriedOut: false, requests: self::counted($requests), asks: $asksThroughTheRequestService, revoked: $revoked, findings: $findings);
    }

    /** What taking them out did; a count below none is refused. */
    public static function carriedOut(
        SomebodyInTheHousehold $who,
        int $requests,
        bool $asksThroughTheRequestService,
        HowFarTheRemovalReached $revoked,
        WhatTheRemovalFound $findings,
    ): self {
        return new self($who, carriedOut: true, requests: self::counted($requests), asks: $asksThroughTheRequestService, revoked: $revoked, findings: $findings);
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

    /** A count of requests, refused below none. */
    private static function counted(int $requests): int
    {
        if ($requests < 0) {
            throw RemovalSaysNothing::below('requests', $requests);
        }

        return $requests;
    }
}
