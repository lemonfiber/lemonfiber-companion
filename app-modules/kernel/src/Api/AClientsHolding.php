<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * One download client, and what became of the limits it was given: its answer in both directions, or what it said instead.
 *
 * A client that did not answer is its own case rather than one with no
 * limits: its limits are unknown, and drawing them as none would read
 * better than the stack does.
 */
final readonly class AClientsHolding
{
    private function __construct(
        private string $client,
        private WhatADownloadClientSaid $pulling,
        private BothDirectionsHeld|string $answer,
    ) {}

    /** A client that answered for both directions. */
    public static function answered(string $client, WhatADownloadClientSaid $pulling, ADirectionHeld $down, ADirectionHeld $up): self
    {
        return new self(self::named($client), $pulling, BothDirectionsHeld::of($down, $up));
    }

    /** A client that did not answer, and what it said. */
    public static function silent(string $client, WhatADownloadClientSaid $pulling, string $said): self
    {
        return new self(self::named($client), $pulling, trim($said));
    }

    /** The client, by the name the stack knows it under. */
    public function client(): string
    {
        return $this->client;
    }

    /** Whether it is fetching at all, unsaid where no cap made that a question. */
    public function pulling(): WhatADownloadClientSaid
    {
        return $this->pulling;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TAnswered of object
     * @template TSilent of object
     *
     * @param Closure(ADirectionHeld, ADirectionHeld): TAnswered $answered
     * @param Closure(string): TSilent                           $silent
     *
     * @return TAnswered|TSilent
     */
    public function either(Closure $answered, Closure $silent): object
    {
        $answer = $this->answer;

        return $answer instanceof BothDirectionsHeld ? $answered($answer->down(), $answer->up()) : $silent($answer);
    }

    private static function named(string $client): string
    {
        $named = trim($client);

        if ($named === '') {
            throw LineSaysNothing::about('client');
        }

        return $named;
    }
}
